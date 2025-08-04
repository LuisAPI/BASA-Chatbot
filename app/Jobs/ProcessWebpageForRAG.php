<?php
// File: app/Jobs/ProcessWebpageForRAG.php

namespace App\Jobs;

use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use App\Events\WebpageProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWebpageForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $url;
    public $title;
    public $content;

    public function __construct($url, $title, $content)
    {
        $this->url = $url;
        $this->title = $title;
        $this->content = $content;
    }

    public function handle()
    {
        try {
            // If content is not provided, fetch and parse the webpage
            $title = $this->title;
            $content = $this->content;
            if (empty($content)) {
                $result = $this->fetchAndParseWebpage($this->url);
                if (isset($result['error'])) {
                    event(new WebpageProcessed($this->url, 'failed', $result['error']));
                    return;
                }
                $title = $result['title'];
                $content = $result['content'];
            }

            $webpageId = md5($this->url);
            // Insert or get webpage metadata
            $webpageRow = \Illuminate\Support\Facades\DB::table('webpages')->where('webpage_id', $webpageId)->first();
            if (!$webpageRow) {
                $webpages_id = \Illuminate\Support\Facades\DB::table('webpages')->insertGetId([
                    'webpage_id' => $webpageId,
                    'url' => $this->url,
                    'title' => $title,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $webpages_id = $webpageRow->id;
            }

            // Safeguard: Prevent duplicate chunk insertion
            $existingChunks = \Illuminate\Support\Facades\DB::table('webpage_chunks')->where('webpages_id', $webpages_id)->exists();
            if ($existingChunks) {
                // Already processed, verify we have some chunks before marking as complete
                $hasChunks = \Illuminate\Support\Facades\DB::table('webpage_chunks')
                    ->where('webpages_id', $webpages_id)
                    ->count() > 0;
                    
                if ($hasChunks) {
                    event(new WebpageProcessed($this->url, 'completed'));
                    return;
                }
                // If no chunks found, reprocess
            }

            $chunker = new Chunker();
            $embedder = new EmbeddingService();
            $vectorSearch = new VectorSearchService();
            $chunks = $chunker->chunkText($content);
            
            if (empty($chunks)) {
                event(new WebpageProcessed($this->url, 'failed', 'No content chunks could be extracted from the webpage'));
                return;
            }
            
            $storedChunks = 0;
            foreach ($chunks as $chunk) {
                $embedding = $embedder->getEmbedding($chunk);
                if ($embedding) {
                    // Store using VectorSearchService for webpages
                    $vectorSearch->storeChunk('webpage', $webpages_id, $chunk, $embedding);
                    $storedChunks++;
                }
            }
            
            if ($storedChunks > 0) {
                event(new WebpageProcessed($this->url, 'completed'));
            } else {
                event(new WebpageProcessed($this->url, 'failed', 'Failed to store any chunks with embeddings'));
            }
        } catch (\Exception $e) {
            event(new WebpageProcessed($this->url, 'failed', $e->getMessage()));
        }
    }

    /**
     * Fetch and parse a webpage, returning ['title' => ..., 'content' => ...] or ['error' => ...]
     */
    private function fetchAndParseWebpage(string $url): ?array
    {
        try {
            // Enhanced Browsershot arguments and headers for anti-bot evasion
            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            ];
            $userAgent = $userAgents[array_rand($userAgents)];
            $viewportSizes = [
                [1920, 1080],
                [1366, 768],
                [1536, 864],
                [1280, 800],
            ];
            $viewport = $viewportSizes[array_rand($viewportSizes)];
            $headers = [
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Referer' => 'https://www.google.com/',
                'DNT' => '1',
            ];
            $args = [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-blink-features=AutomationControlled',
                '--disable-automation',
                '--disable-infobars',
                '--disable-web-security',
                '--disable-features=IsolateOrigins,site-per-process',
                '--window-size=' . $viewport[0] . ',' . $viewport[1],
                '--lang=en-US',
                '--start-maximized',
                '--hide-scrollbars',
                '--disable-extensions',
                '--user-agent=' . $userAgent,
            ];
            $html = \Spatie\Browsershot\Browsershot::url($url)
                ->setChromePath(env('CHROME_PATH', 'C:\Program Files\Google\Chrome\Application\chrome.exe'))
                ->setOption('args', $args)
                ->setOption('waitUntil', env('BROWSERSHOT_WAIT_UNTIL', 'networkidle0'))
                ->setOption('headers', $headers)
                ->setViewport($viewport[0], $viewport[1])
                ->timeout(env('BROWSERSHOT_TIMEOUT', 120)) // Increased timeout to 120 seconds
                ->bodyHtml();

            // Use Readability to parse the HTML
            $readability = new \fivefilters\Readability\Readability(new \fivefilters\Readability\Configuration());
            $readability->parse($html);
            $content = $readability->getContent();
            $title = $readability->getTitle();
            $content = strip_tags($content);
            if (strlen($content) > 4000) {
                $content = substr($content, 0, 1000) . '... [truncated]';
            }
            return [
                'title' => $title,
                'content' => $content
            ];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $context = '';
            
            if ($e instanceof \Spatie\Browsershot\Exceptions\CouldNotTakeScreenshot) {
                $context = 'Chrome/Browsershot error: ';
            } elseif ($e instanceof \GuzzleHttp\Exception\RequestException) {
                $context = 'Network error: ';
            } elseif ($e instanceof \RuntimeException) {
                $context = 'Content processing error: ';
            }
            
            \Log::error($context . $errorMessage, [
                'url' => $url,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return ['error' => $context . $errorMessage];
        }
    }
}
