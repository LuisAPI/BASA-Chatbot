<?php
// test-specific-url.php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing specific URL with chatbot logic...\n\n";

// Replace this with the actual URL you're trying to access
$testUrl = 'https://depdev.gov.ph/organizational-structure/'; // DEPDev organizational structure URL

echo "Testing URL: $testUrl\n\n";

// Step 1: Parse URL and check robots.txt
echo "=== Step 1: Robots.txt Check ===\n";
$parsedUrl = parse_url($testUrl);
$robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';

echo "Robots.txt URL: $robotsUrl\n";

try {
    $robotsResponse = \Illuminate\Support\Facades\Http::timeout(5)
        ->withOptions([
            'verify' => false, // Disable SSL certificate verification
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ])
        ->get($robotsUrl);
    $robotsTxt = $robotsResponse->successful() ? $robotsResponse->body() : '';
    $isAllowed = true;
    
    echo "Robots.txt status: " . ($robotsResponse->successful() ? "Found" : "Not found") . "\n";
    
    if ($robotsTxt) {
        echo "Robots.txt content (first 200 chars): " . substr($robotsTxt, 0, 200) . "\n";
        
        $lines = preg_split('/\r?\n/', $robotsTxt);
        $userAgent = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (stripos($line, 'User-agent:') === 0) {
                $userAgent = (stripos($line, 'User-agent: *') === 0);
                echo "Found User-agent line: $line (matches wildcard: " . ($userAgent ? "yes" : "no") . ")\n";
            } elseif ($userAgent && stripos($line, 'Disallow:') === 0) {
                $disallowedPath = trim(substr($line, 9));
                echo "Found Disallow line: $line (path: '$disallowedPath')\n";
                if ($disallowedPath && strpos($parsedUrl['path'] ?? '/', $disallowedPath) === 0) {
                    $isAllowed = false;
                    echo "URL path '" . ($parsedUrl['path'] ?? '/') . "' matches disallowed path '$disallowedPath'\n";
                    break;
                }
            } elseif (stripos($line, 'User-agent:') === 0) {
                $userAgent = false;
                echo "Found User-agent line (not wildcard): $line\n";
            }
        }
    }
    
    echo "Final robots.txt result: " . ($isAllowed ? "ALLOWED" : "DISALLOWED") . "\n\n";
    
    if (!$isAllowed) {
        echo "ERROR: URL is disallowed by robots.txt\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "Exception during robots.txt check: " . $e->getMessage() . "\n";
    echo "Proceeding anyway (fail open)...\n\n";
}

// Step 2: Fetch the webpage
echo "=== Step 2: Fetch Webpage ===\n";
try {
    $webResponse = \Illuminate\Support\Facades\Http::timeout(10)
        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'])
        ->withOptions([
            'verify' => false, // Disable SSL certificate verification
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ])
        ->get($testUrl);
    
    echo "HTTP Status: " . $webResponse->status() . "\n";
    echo "Content-Type: " . $webResponse->header('Content-Type') . "\n";
    echo "Content Length: " . strlen($webResponse->body()) . " characters\n";
    
    if (!$webResponse->successful()) {
        echo "ERROR: HTTP request failed with status " . $webResponse->status() . "\n";
        echo "Response body: " . $webResponse->body() . "\n";
        exit(1);
    }
    
    $html = $webResponse->body();
    echo "HTML preview (first 500 chars): " . substr($html, 0, 500) . "\n\n";
    
} catch (\Exception $e) {
    echo "Exception during webpage fetch: " . $e->getMessage() . "\n";
    echo "Exception type: " . get_class($e) . "\n";
    exit(1);
}

// Step 3: Parse with Readability
echo "=== Step 3: Parse with Readability ===\n";
try {
    $readability = new \fivefilters\Readability\Readability(new \fivefilters\Readability\Configuration());
    echo "Readability library loaded successfully\n";
    
    $readability->parse($html);
    $content = $readability->getContent();
    $title = $readability->getTitle();
    
    echo "Title: " . $title . "\n";
    echo "Raw content length: " . strlen($content) . " characters\n";
    echo "Raw content preview: " . substr($content, 0, 300) . "\n\n";
    
    // Strip tags and truncate as in the original code
    $content = strip_tags($content);
    echo "Stripped content length: " . strlen($content) . " characters\n";
    
    if (strlen($content) > 4000) {
        $content = substr($content, 0, 1000) . '... [truncated]';
        echo "Content truncated to 1000 characters\n";
    }
    
    echo "Final content preview: " . substr($content, 0, 500) . "\n\n";
    
    echo "SUCCESS: URL processed successfully!\n";
    echo "Final result:\n";
    echo "- Title: $title\n";
    echo "- Content length: " . strlen($content) . " characters\n";
    
} catch (\Exception $e) {
    echo "Exception during Readability parsing: " . $e->getMessage() . "\n";
    echo "Exception type: " . get_class($e) . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n"; 