<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatbotTimeoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Ollama API connection.
     */
    public function test_ollama_connection_works(): void
    {
        $response = Http::timeout(5)->get('http://127.0.0.1:11434/api/tags');
        $this->assertTrue($response->ok(), 'Ollama API should be accessible');
    }

    /**
     * Test RAG search functionality.
     */
    public function test_rag_search_finds_relevant_chunks(): void
    {
        // Skip if no RAG chunks exist
        if (\Illuminate\Support\Facades\DB::table('rag_chunks')->count() === 0) {
            $this->markTestSkipped('No RAG chunks available for testing');
        }

        $controller = new ChatbotController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('searchRelevantFileContent');
        $method->setAccessible(true);

        $relevantContent = $method->invoke($controller, 'summarize the 2025 automation roadmap');
        
        $this->assertIsArray($relevantContent);
        $this->assertLessThanOrEqual(3, count($relevantContent), 'Should return max 3 chunks for performance');
        
        if (!empty($relevantContent)) {
            $this->assertArrayHasKey('similarity', $relevantContent[0]);
            $this->assertArrayHasKey('source', $relevantContent[0]);
            $this->assertArrayHasKey('chunk', $relevantContent[0]);
        }
    }

    /**
     * Test LLM response within reasonable timeout.
     */
    public function test_llm_response_within_timeout(): void
    {
        $startTime = microtime(true);
        
        $controller = new ChatbotController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sendToLLM');
        $method->setAccessible(true);

        $response = $method->invoke($controller, 'Hello, how are you?');
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(30, $duration, 'Response should complete within 30 seconds');
        $this->assertNotEmpty($response, 'Response should not be empty');
        $this->assertIsString($response, 'Response should be a string');
    }

    /**
     * Test LLM response with RAG context.
     */
    public function test_llm_response_with_rag_context(): void
    {
        // Skip if no RAG chunks exist
        if (\Illuminate\Support\Facades\DB::table('rag_chunks')->count() === 0) {
            $this->markTestSkipped('No RAG chunks available for testing');
        }

        $startTime = microtime(true);
        
        $controller = new ChatbotController();
        $reflection = new \ReflectionClass($controller);
        
        // Get relevant content
        $searchMethod = $reflection->getMethod('searchRelevantFileContent');
        $searchMethod->setAccessible(true);
        $relevantContent = $searchMethod->invoke($controller, 'summarize the 2025 automation roadmap');
        
        // Test LLM with RAG context
        $method = $reflection->getMethod('sendToLLM');
        $method->setAccessible(true);
        $response = $method->invoke($controller, 'What is the main topic?', null, $relevantContent);
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(60, $duration, 'RAG response should complete within 60 seconds');
        $this->assertNotEmpty($response, 'RAG response should not be empty');
    }

    /**
     * Test that RAG search respects similarity threshold.
     */
    public function test_rag_search_similarity_threshold(): void
    {
        // Skip if no RAG chunks exist
        if (\Illuminate\Support\Facades\DB::table('rag_chunks')->count() === 0) {
            $this->markTestSkipped('No RAG chunks available for testing');
        }

        $controller = new ChatbotController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('searchRelevantFileContent');
        $method->setAccessible(true);

        $relevantContent = $method->invoke($controller, 'summarize the 2025 automation roadmap');
        
        foreach ($relevantContent as $chunk) {
            $this->assertGreaterThan(0.4, $chunk['similarity'], 'Similarity should be above 0.4 threshold');
        }
    }

    /**
     * Test that context building works correctly.
     */
    public function test_context_building_works(): void
    {
        // Skip if no RAG chunks exist
        if (\Illuminate\Support\Facades\DB::table('rag_chunks')->count() === 0) {
            $this->markTestSkipped('No RAG chunks available for testing');
        }

        $controller = new ChatbotController();
        $reflection = new \ReflectionClass($controller);
        
        // Get relevant content
        $searchMethod = $reflection->getMethod('searchRelevantFileContent');
        $searchMethod->setAccessible(true);
        $relevantContent = $searchMethod->invoke($controller, 'summarize the 2025 automation roadmap');
        
        // Test context building
        $contextMethod = $reflection->getMethod('buildFileContext');
        $contextMethod->setAccessible(true);
        $context = $contextMethod->invoke($controller, array_slice($relevantContent, 0, 1));
        
        $this->assertNotEmpty($context, 'Context should not be empty');
        $this->assertIsString($context, 'Context should be a string');
        $this->assertLessThan(2000, strlen($context), 'Context should be reasonably sized');
    }
} 