<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Chunker;
use App\Services\EmbeddingService;
use App\Services\VectorSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileProcessingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test file chunking functionality.
     */
    public function test_file_chunking_works(): void
    {
        $chunker = new Chunker();
        $testContent = "This is a test document with multiple sentences. It should be chunked properly. Each chunk should be reasonable in size.";
        
        $chunks = $chunker->chunkText($testContent);
        
        $this->assertNotEmpty($chunks, 'Chunks should not be empty');
        $this->assertIsArray($chunks, 'Chunks should be an array');
        $this->assertGreaterThan(0, count($chunks), 'Should have at least one chunk');
        
        foreach ($chunks as $chunk) {
            $this->assertIsString($chunk, 'Each chunk should be a string');
            $this->assertNotEmpty($chunk, 'Each chunk should not be empty');
            $this->assertLessThan(1000, strlen($chunk), 'Each chunk should be reasonably sized');
        }
    }

    /**
     * Test embedding generation.
     */
    public function test_embedding_generation(): void
    {
        $embeddingService = new EmbeddingService();
        $testText = 'Test text for embedding generation';
        
        $embedding = $embeddingService->getEmbedding($testText);
        
        $this->assertNotEmpty($embedding, 'Embedding should not be empty');
        $this->assertIsArray($embedding, 'Embedding should be an array');
        $this->assertGreaterThan(0, count($embedding), 'Embedding should have dimensions');
        
        // Check that all values are numeric
        foreach ($embedding as $value) {
            $this->assertIsNumeric($value, 'Embedding values should be numeric');
        }
    }

    /**
     * Test vector search functionality.
     */
    public function test_vector_search_functionality(): void
    {
        $vectorSearch = new VectorSearchService();
        $embeddingService = new EmbeddingService();
        
        $testQuery = 'test query';
        $queryEmbedding = $embeddingService->getEmbedding($testQuery);
        
        $this->assertNotEmpty($queryEmbedding, 'Query embedding should not be empty');
        
        // Test search (may return empty if no chunks exist)
        $results = $vectorSearch->searchSimilar($queryEmbedding, 5);
        
        $this->assertIsArray($results, 'Search results should be an array');
        $this->assertLessThanOrEqual(5, count($results), 'Should return at most 5 results');
    }

    /**
     * Test chunking with different content types.
     */
    public function test_chunking_with_different_content_types(): void
    {
        $chunker = new Chunker();
        
        // Test with short content
        $shortContent = "Short content.";
        $shortChunks = $chunker->chunkText($shortContent);
        $this->assertNotEmpty($shortChunks, 'Short content should still be chunked');
        
        // Test with long content
        $longContent = str_repeat("This is a sentence. ", 100);
        $longChunks = $chunker->chunkText($longContent);
        $this->assertNotEmpty($longChunks, 'Long content should be chunked');
        $this->assertGreaterThan(1, count($longChunks), 'Long content should have multiple chunks');
        
        // Test with empty content
        $emptyChunks = $chunker->chunkText('');
        $this->assertIsArray($emptyChunks, 'Empty content should return array');
    }

    /**
     * Test embedding service with different text lengths.
     */
    public function test_embedding_service_with_different_text_lengths(): void
    {
        $embeddingService = new EmbeddingService();
        
        // Test with short text
        $shortText = "Short";
        $shortEmbedding = $embeddingService->getEmbedding($shortText);
        $this->assertNotEmpty($shortEmbedding, 'Short text should generate embedding');
        
        // Test with long text
        $longText = str_repeat("This is a longer text for testing. ", 50);
        $longEmbedding = $embeddingService->getEmbedding($longText);
        $this->assertNotEmpty($longEmbedding, 'Long text should generate embedding');
        
        // Test with empty text
        $emptyEmbedding = $embeddingService->getEmbedding('');
        $this->assertIsArray($emptyEmbedding, 'Empty text should return array');
    }

    /**
     * Test that chunking preserves important content.
     */
    public function test_chunking_preserves_important_content(): void
    {
        $chunker = new Chunker();
        $testContent = "Important keyword: automation. Another important term: roadmap. Final important word: 2025.";
        
        $chunks = $chunker->chunkText($testContent);
        
        $allChunksText = implode(' ', $chunks);
        
        // Check that important keywords are preserved
        $this->assertStringContainsString('automation', $allChunksText, 'Important keyword should be preserved');
        $this->assertStringContainsString('roadmap', $allChunksText, 'Important keyword should be preserved');
        $this->assertStringContainsString('2025', $allChunksText, 'Important keyword should be preserved');
    }
} 