<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class EmbeddingService
{
    protected $endpoint;
    protected $model;

    public function __construct($endpoint = 'http://127.0.0.1:11434/api/embeddings', $model = null)
    {
        $this->endpoint = $endpoint;
        // Read embedding model from .env or use default
        $this->model = $model ?? env('LLM_EMBED_MODEL', 'nomic-embed-text');
    }

    /**
     * Get embedding vector for a chunk of text using Ollama.
     */
    public function getEmbedding(string $text): ?array
    {
        $response = Http::timeout(30)->post($this->endpoint, [
            'model' => $this->model,
            'prompt' => $text
        ]);
        if ($response->ok() && isset($response['embedding'])) {
            return $response['embedding'];
        }
        return null;
    }
}
