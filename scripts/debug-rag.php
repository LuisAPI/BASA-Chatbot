<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 RAG System Debug\n";
echo "==================\n\n";

// Check what files we have
echo "📁 Files in database:\n";
$files = DB::table('rag_chunks')->select('source')->distinct()->get();
foreach ($files as $file) {
    echo "   - " . $file->source . "\n";
}

echo "\n📊 Total chunks: " . DB::table('rag_chunks')->count() . "\n";

// Test embedding service
echo "\n🧠 Testing embedding service...\n";
try {
    $embeddingService = new \App\Services\EmbeddingService();
    $queryEmbedding = $embeddingService->getEmbedding('imperial accomplishment record week 1');
    
    if ($queryEmbedding) {
        echo "✅ Embedding generated successfully\n";
        
        // Test vector search
        echo "\n🔍 Testing vector search...\n";
        $vectorSearch = new \App\Services\VectorSearchService();
        $results = $vectorSearch->searchSimilar($queryEmbedding, 5);
        
        echo "Found " . count($results) . " similar chunks:\n";
        foreach ($results as $i => $chunk) {
            echo "\nChunk " . ($i+1) . " (similarity: " . round($chunk['similarity'], 3) . "):\n";
            echo "Source: " . $chunk['source'] . "\n";
            echo "Content: " . substr($chunk['chunk'], 0, 200) . "...\n";
        }
        
        // Test the actual search method from controller
        echo "\n🎯 Testing controller search method...\n";
        $controller = new \App\Http\Controllers\ChatbotController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('searchRelevantFileContent');
        $method->setAccessible(true);
        $relevantContent = $method->invoke($controller, 'imperial accomplishment record week 1');
        
        echo "Controller found " . count($relevantContent) . " relevant chunks\n";
        if (!empty($relevantContent)) {
            echo "First chunk source: " . $relevantContent[0]['source'] . "\n";
            echo "First chunk content: " . substr($relevantContent[0]['chunk'], 0, 200) . "...\n";
        }
        
    } else {
        echo "❌ Failed to generate embedding\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✨ Debug completed!\n"; 