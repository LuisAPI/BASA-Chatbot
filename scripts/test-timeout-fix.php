<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Http;

echo "Testing chatbot timeout fixes...\n\n";

// Test 1: Check if Ollama is running
echo "1. Checking Ollama connection...\n";
try {
    $response = Http::timeout(5)->get('http://127.0.0.1:11434/api/tags');
    if ($response->ok()) {
        echo "   ✓ Ollama is running\n";
    } else {
        echo "   ✗ Ollama returned status: " . $response->status() . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Cannot connect to Ollama: " . $e->getMessage() . "\n";
    echo "   Please start Ollama first: ollama serve\n";
    exit(1);
}

// Test 2: Test RAG search
echo "\n2. Testing RAG search...\n";
$controller = new ChatbotController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('searchRelevantFileContent');
$method->setAccessible(true);

try {
    $relevantContent = $method->invoke($controller, 'summarize the 2025 automation roadmap');
    echo "   Found " . count($relevantContent) . " relevant chunks\n";
    
    if (!empty($relevantContent)) {
        echo "   Files: " . implode(', ', array_unique(array_column($relevantContent, 'source'))) . "\n";
        echo "   Top similarity: " . round($relevantContent[0]['similarity'], 3) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ RAG search failed: " . $e->getMessage() . "\n";
}

// Test 3: Test LLM response with timeout
echo "\n3. Testing LLM response (with timeout)...\n";
$startTime = time();

try {
    $method = $reflection->getMethod('sendToLLM');
    $method->setAccessible(true);
    
    $response = $method->invoke($controller, 'What is the main topic of the 2025 automation roadmap?', null, $relevantContent);
    
    $duration = time() - $startTime;
    echo "   ✓ Response received in {$duration} seconds\n";
    echo "   Response length: " . strlen($response) . " characters\n";
    echo "   Preview: " . substr($response, 0, 100) . "...\n";
    
    if ($duration > 60) {
        echo "   ⚠ Warning: Response took longer than 60 seconds\n";
    } else {
        echo "   ✓ Response time is acceptable\n";
    }
    
} catch (Exception $e) {
    $duration = time() - $startTime;
    echo "   ✗ LLM request failed after {$duration} seconds: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n"; 