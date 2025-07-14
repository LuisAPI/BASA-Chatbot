<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing getAvailableFiles method...\n";

try {
    $controller = new \App\Http\Controllers\ChatbotController();
    
    // Mock authentication
    $user = \App\Models\User::first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        echo "Authenticated as: " . $user->name . "\n";
    } else {
        echo "No users found in database\n";
    }
    
    $result = $controller->getAvailableFiles();
    $data = $result->getData();
    
    echo "Success! Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 