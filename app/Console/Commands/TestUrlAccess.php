<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestUrlAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan url:test {url} {--raw}
     */
    protected $signature = 'url:test {url? : The URL to test} {--raw : Output only the raw HTML, do not extract readable content}';

    /**
     * The console command description.
     */
    protected $description = 'Test access to a URL from the server, print HTTP status, headers, and extract readable content (default) or raw HTML (--raw)';

    public function handle()
    {
        $url = $this->argument('url');
        $raw = $this->option('raw');

        if (!$url) {
            $this->info('Usage: php artisan url:test {url} {--raw}');
            $this->info('  {url}   The URL to test (required)');
            $this->info('  --raw   Output only the raw HTML, do not extract readable content');
            $this->info('Example: php artisan url:test https://newsinfo.inquirer.net/2062508/balisacan-to-submit-courtesy-resignation-as-depdev-chief');
            return 1;
        }

        $this->info("Testing URL: $url\n");

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
                ])
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false
                    ]
                ])
                ->get($url);

            $this->info('HTTP Status: ' . $response->status());
            $this->info('Headers:');
            foreach ($response->headers() as $key => $values) {
                $this->line("  $key: " . implode('; ', $values));
            }
            $body = $response->body();
            $this->info('Body length: ' . strlen($body) . ' characters');
            $this->line('');

            if ($raw) {
                $this->info('--- RAW HTML (first 1000 chars) ---');
                $this->line(substr($body, 0, 1000));
                if (strlen($body) > 1000) {
                    $this->line('... [truncated]');
                }
                return 0;
            }

            // Try to extract readable content
            try {
                if (!class_exists('fivefilters\\Readability\\Readability')) {
                    $this->warn('Readability library not installed. Showing raw HTML instead.');
                    $this->line(substr($body, 0, 1000));
                    if (strlen($body) > 1000) {
                        $this->line('... [truncated]');
                    }
                    return 0;
                }
                $this->info('--- Extracting readable content with Readability ---');
                $readability = new \fivefilters\Readability\Readability(new \fivefilters\Readability\Configuration());
                $readability->parse($body);
                $content = $readability->getContent();
                $title = $readability->getTitle();
                $content = strip_tags($content);
                $this->info('Title: ' . $title);
                $this->info('Content length: ' . strlen($content) . ' characters');
                $this->line('--- Content Preview (first 1000 chars) ---');
                $this->line(substr($content, 0, 1000));
                if (strlen($content) > 1000) {
                    $this->line('... [truncated]');
                }
            } catch (\Exception $e) {
                $this->error('Error extracting readable content: ' . $e->getMessage());
                $this->info('--- RAW HTML (first 1000 chars) ---');
                $this->line(substr($body, 0, 1000));
                if (strlen($body) > 1000) {
                    $this->line('... [truncated]');
                }
            }
        } catch (\Exception $e) {
            $this->error('Failed to fetch URL: ' . $e->getMessage());
            return 2;
        }
        return 0;
    }
} 