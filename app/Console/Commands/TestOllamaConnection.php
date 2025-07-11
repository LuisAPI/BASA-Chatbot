<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestOllamaConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:test-connection 
                            {--model=tinyllama : Model to test with}
                            {--timeout=30 : Timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test connection to Ollama API and basic functionality';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $model = $this->option('model');
        $timeout = (int) $this->option('timeout');

        $this->info("Testing Ollama connection...");
        $this->info("Model: {$model}");
        $this->info("Timeout: {$timeout} seconds");

        $this->testApiConnection();
        $this->testModelInfo($model);
        $this->testSimpleGeneration($model, $timeout);
    }

    /**
     * Test basic API connection.
     */
    private function testApiConnection(): void
    {
        $this->info("\n=== Testing API Connection ===");
        
        try {
            $response = Http::timeout(10)->get('http://127.0.0.1:11434/api/tags');
            
            if ($response->ok()) {
                $this->info("✓ API connection successful");
                $data = $response->json();
                $models = $data['models'] ?? [];
                $this->info("Available models: " . count($models));
                
                foreach ($models as $model) {
                    $this->line("  - {$model['name']} ({$model['size']})");
                }
            } else {
                $this->error("✗ API connection failed: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("✗ API connection failed: " . $e->getMessage());
        }
    }

    /**
     * Test model information retrieval.
     */
    private function testModelInfo(string $model): void
    {
        $this->info("\n=== Testing Model Info ===");
        
        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:11434/api/show', [
                'name' => $model
            ]);
            
            if ($response->ok()) {
                $this->info("✓ Model info retrieved successfully");
                $data = $response->json();
                $this->info("Model: " . (isset($data['name']) ? $data['name'] : 'Unknown'));
                $this->info("Size: " . (isset($data['size']) ? $data['size'] : 'Unknown'));
            } else {
                $this->error("✗ Model info retrieval failed: HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->error("✗ Model info retrieval failed: " . $e->getMessage());
        }
    }

    /**
     * Test simple text generation.
     */
    private function testSimpleGeneration(string $model, int $timeout): void
    {
        $this->info("\n=== Testing Text Generation ===");
        
        try {
            $startTime = microtime(true);
            
            $data = [
                'model' => $model,
                'prompt' => 'Hello, how are you?',
                'stream' => false
            ];
            
            $response = Http::timeout($timeout)->post('http://127.0.0.1:11434/api/generate', $data);
            
            $duration = microtime(true) - $startTime;
            
            if ($response->ok()) {
                $result = $response->json();
                $this->info("✓ Text generation successful");
                $this->info("Response time: " . round($duration, 2) . " seconds");
                $responseText = $result['response'] ?? '';
                $this->info("Response length: " . strlen($responseText) . " characters");
                $this->info("Response preview: " . substr($responseText, 0, 100) . "...");
            } else {
                $this->error("✗ Text generation failed: HTTP {$response->status()}");
                $this->error("Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("✗ Text generation failed: " . $e->getMessage());
        }
    }
} 