<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    protected $ollamaService;
    protected $model;

    public function __construct()
    {
        $this->ollamaService = new OllamaConnectionService();
        $this->model = config('ollama.default_models.embedding', 'nomic-embed-text');
    }

    /**
     * Get embedding vector for a chunk of text using Ollama.
     */
    public function getEmbedding(string $text): ?array
    {
        $endpoint = $this->ollamaService->getActiveEndpoint();
        
        if (!$endpoint) {
            Log::error('No Ollama endpoint available for embedding generation');
            return null;
        }

        $embeddingEndpoint = $endpoint . '/api/embeddings';
        
        try {
            $request = $this->ollamaService->createAuthenticatedRequest();
            $response = $request->timeout(config('ollama.timeout', 30))
                ->post($embeddingEndpoint, [
                    'model' => $this->model,
                    'prompt' => $text
                ]);

            if ($response->ok() && isset($response['embedding'])) {
                return $response['embedding'];
            }
            
            Log::warning('Embedding generation failed', [
                'endpoint' => $embeddingEndpoint,
                'model' => $this->model,
                'response' => $response->body()
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Embedding generation exception', [
                'endpoint' => $embeddingEndpoint,
                'model' => $this->model,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
