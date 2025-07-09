<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧠 LLM Prompt Test\n";
echo "=================\n\n";

$controller = new \App\Http\Controllers\ChatbotController();

// Test the same query that failed
$userMessage = "tell me about imperial's accomplishment record for week 1";

echo "User message: " . $userMessage . "\n\n";

// Get relevant content
$reflection = new ReflectionClass($controller);
$searchMethod = $reflection->getMethod('searchRelevantFileContent');
$searchMethod->setAccessible(true);
$relevantContent = $searchMethod->invoke($controller, $userMessage);

echo "Found " . count($relevantContent) . " relevant chunks\n\n";

// Build file context
$buildMethod = $reflection->getMethod('buildFileContext');
$buildMethod->setAccessible(true);
$fileContext = $buildMethod->invoke($controller, $relevantContent);

echo "File context length: " . strlen($fileContext) . " characters\n\n";

// Get system prompt
$systemMethod = $reflection->getMethod('getSystemPrompt');
$systemMethod->setAccessible(true);
$systemPrompt = $systemMethod->invoke($controller);

// Build the full system prompt with context
$fullSystemPrompt = $fileContext . "\n\n" . $systemPrompt;

echo "=== FULL SYSTEM PROMPT ===\n";
echo $fullSystemPrompt;
echo "\n\n=== END SYSTEM PROMPT ===\n\n";

echo "Total system prompt length: " . strlen($fullSystemPrompt) . " characters\n";
echo "User prompt: " . $userMessage . "\n";

echo "\n✨ Test completed!\n"; 