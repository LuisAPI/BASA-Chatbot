<?php

echo "Testing simple Ollama API call...\n\n";

// Test 1: Simple API call
echo "1. Testing simple API call...\n";
$startTime = time();

try {
    $data = [
        'model' => 'tinyllama',
        'prompt' => 'Hello, how are you?',
        'stream' => false
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:11434/api/generate');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $duration = time() - $startTime;
    echo "   HTTP Code: {$httpCode}\n";
    echo "   Duration: {$duration} seconds\n";
    
    if ($response !== false) {
        $result = json_decode($response, true);
        if ($result && isset($result['response'])) {
            echo "   ✓ Success! Response: " . substr($result['response'], 0, 50) . "...\n";
        } else {
            echo "   ✗ Invalid response format\n";
            echo "   Raw response: " . substr($response, 0, 200) . "...\n";
        }
    } else {
        echo "   ✗ cURL failed\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

// Test 2: Check model info
echo "\n2. Checking model info...\n";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:11434/api/show');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'tinyllama']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response !== false) {
        $result = json_decode($response, true);
        if ($result) {
            echo "   ✓ Model info retrieved\n";
            echo "   Model size: " . ($result['modelfile'] ?? 'Unknown') . "\n";
        } else {
            echo "   ✗ Invalid model info response\n";
        }
    } else {
        echo "   ✗ Failed to get model info\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n"; 