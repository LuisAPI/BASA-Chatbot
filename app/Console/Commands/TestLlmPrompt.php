<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Log;

class TestLlmPrompt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llm:test-prompt 
                            {prompt : The prompt to test}
                            {--rag : Enable RAG search for relevant content}
                            {--stream : Test streaming mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test LLM prompt processing and response generation';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $prompt = $this->argument('prompt');
        $useRag = $this->option('rag');
        $useStream = $this->option('stream');

        $this->info("Testing LLM prompt: {$prompt}");
        $this->info("RAG enabled: " . ($useRag ? 'Yes' : 'No'));
        $this->info("Streaming mode: " . ($useStream ? 'Yes' : 'No'));

        try {
            $controller = new ChatbotController();
            
            if ($useStream) {
                $this->testStreamingMode($controller, $prompt);
            } else {
                $this->testNonStreamingMode($controller, $prompt, $useRag);
            }

        } catch (\Exception $e) {
            $this->error("Error testing LLM prompt: " . $e->getMessage());
            Log::error("LLM prompt test failed: " . $e->getMessage());
        }
    }

    /**
     * Test non-streaming mode.
     */
    private function testNonStreamingMode(ChatbotController $controller, string $prompt, bool $useRag): void
    {
        $this->info("\n=== Testing Non-Streaming Mode ===");
        
        $startTime = microtime(true);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendToLLM');
        $method->setAccessible(true);

        // Get relevant content if RAG is enabled
        $relevantContent = [];
        if ($useRag) {
            $searchMethod = $reflection->getMethod('searchRelevantFileContent');
            $searchMethod->setAccessible(true);
            $relevantContent = $searchMethod->invoke($controller, $prompt);
            
            $this->info("Found " . count($relevantContent) . " relevant chunks");
        }

        // Test the LLM
        $response = $method->invoke($controller, $prompt, null, $useRag ? $relevantContent : null);
        
        $duration = microtime(true) - $startTime;
        
        $this->info("Response received in " . round($duration, 2) . " seconds");
        $this->info("Response length: " . strlen($response) . " characters");
        $this->info("\n=== Response ===");
        $this->line($response);
        $this->info("=== End Response ===");
    }

    /**
     * Test streaming mode.
     */
    private function testStreamingMode(ChatbotController $controller, string $prompt): void
    {
        $this->info("\n=== Testing Streaming Mode ===");
        $this->warn("Streaming mode testing is not fully implemented in this command.");
        $this->warn("Use the web interface or create a proper streaming test.");
        
        // For now, just test the non-streaming mode
        $this->testNonStreamingMode($controller, $prompt, false);
    }
} 