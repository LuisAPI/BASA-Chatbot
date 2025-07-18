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
            $webpageId = md5($this->url);
            // Insert or get webpage metadata
            $webpageRow = \Illuminate\Support\Facades\DB::table('webpages')->where('webpage_id', $webpageId)->first();
            if (!$webpageRow) {
                $webpages_id = \Illuminate\Support\Facades\DB::table('webpages')->insertGetId([
                    'webpage_id' => $webpageId,
                    'url' => $this->url,
                    'title' => $this->title,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                $webpages_id = $webpageRow->id;
            }
            $chunker = new Chunker();
            $embedder = new EmbeddingService();
            $vectorSearch = new VectorSearchService();
            $chunks = $chunker->chunkText($this->content);
            foreach ($chunks as $chunk) {
                $embedding = $embedder->getEmbedding($chunk);
                if ($embedding) {
                    // Store using VectorSearchService for webpages
                    $vectorSearch->storeChunk('webpage', $webpages_id, $chunk, $embedding);
                }
            }
            event(new WebpageProcessed($this->url, 'completed'));
        } catch (\Exception $e) {
            event(new WebpageProcessed($this->url, 'failed', $e->getMessage()));
        }
    }
}
