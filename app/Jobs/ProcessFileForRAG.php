<?php

namespace App\Jobs;

use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use App\Events\FileProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFileForRAG implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fileContent;
    public $fileName;

    public function __construct($fileContent, $fileName)
    {
        $this->fileContent = $fileContent;
        $this->fileName = $fileName;
    }

    public function handle()
    {
        $chunker = new Chunker();
        $embedder = new EmbeddingService();
        $vectorSearch = new VectorSearchService();
        $chunks = $chunker->chunkText($this->fileContent);
        foreach ($chunks as $chunk) {
            $embedding = $embedder->getEmbedding($chunk);
            if ($embedding) {
                $vectorSearch->storeChunk($this->fileName, $chunk, $embedding);
            }
        }
        // Broadcast event when done
        event(new FileProcessed($this->fileName));
    }
}
