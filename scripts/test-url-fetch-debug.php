<?php
// test-url-fetch-debug.php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing URL fetching...\n\n";

// Test URL - replace with the actual URL you're trying to access
$testUrl = 'https://example.com'; // Replace with your actual URL

echo "Testing URL: $testUrl\n\n";

// Test 1: Basic HTTP request
echo "=== Test 1: Basic HTTP Request ===\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
        ->get($testUrl);
    
    echo "Status Code: " . $response->status() . "\n";
    echo "Headers: " . json_encode($response->headers(), JSON_PRETTY_PRINT) . "\n";
    echo "Body Length: " . strlen($response->body()) . " characters\n";
    echo "First 500 chars: " . substr($response->body(), 0, 500) . "\n\n";
    
    if (!$response->successful()) {
        echo "HTTP request failed with status: " . $response->status() . "\n";
        echo "Error body: " . $response->body() . "\n\n";
    }
} catch (\Exception $e) {
    echo "Exception during HTTP request: " . $e->getMessage() . "\n";
    echo "Exception type: " . get_class($e) . "\n\n";
}

// Test 2: Check if Readability library is working
echo "=== Test 2: Readability Library Test ===\n";
try {
    $readability = new \fivefilters\Readability\Readability(new \fivefilters\Readability\Configuration());
    echo "Readability library loaded successfully\n";
    
    if (isset($response) && $response->successful()) {
        $html = $response->body();
        echo "Attempting to parse HTML with Readability...\n";
        
        $readability->parse($html);
        $content = $readability->getContent();
        $title = $readability->getTitle();
        
        echo "Title: " . $title . "\n";
        echo "Content length: " . strlen($content) . " characters\n";
        echo "First 300 chars of content: " . substr(strip_tags($content), 0, 300) . "\n\n";
    } else {
        echo "Skipping Readability test - HTTP request failed\n\n";
    }
} catch (\Exception $e) {
    echo "Exception during Readability parsing: " . $e->getMessage() . "\n";
    echo "Exception type: " . get_class($e) . "\n\n";
}

// Test 3: Test with cURL directly
echo "=== Test 3: Direct cURL Test ===\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $curlResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "cURL HTTP Code: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    } else {
        echo "cURL Response Length: " . strlen($curlResponse) . " characters\n";
        echo "First 500 chars: " . substr($curlResponse, 0, 500) . "\n";
    }
} catch (\Exception $e) {
    echo "Exception during cURL test: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n"; 