<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\File;

echo "=== SECURITY TEST: What a server admin could potentially access ===\n\n";

// Test 1: Direct database access (what a server admin with DB access could see)
echo "1. DATABASE ACCESS (Server admin with database credentials):\n";
echo "------------------------------------------------------------\n";

$users = User::all();
foreach ($users as $user) {
    echo "User ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Email: {$user->email}\n";
    echo "  Password Hash: {$user->password}\n";
    echo "  Created: {$user->created_at}\n";
    echo "  Updated: {$user->updated_at}\n";
    echo "  Email Verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
    echo "  Remember Token: " . ($user->remember_token ? 'SET' : 'NULL') . "\n";
    echo "\n";
}

// Test 2: File system access (what files might contain user data)
echo "2. FILE SYSTEM ACCESS (Server admin with file system access):\n";
echo "------------------------------------------------------------\n";

// Check .env file
$envPath = base_path('.env');
if (File::exists($envPath)) {
    echo "✓ .env file exists and is readable\n";
    $envContent = File::get($envPath);
    if (strpos($envContent, 'DB_PASSWORD') !== false) {
        echo "⚠️  .env contains database password\n";
    }
    if (strpos($envContent, 'APP_KEY') !== false) {
        echo "⚠️  .env contains application key\n";
    }
} else {
    echo "✗ .env file not found\n";
}

// Check storage logs
$logPath = storage_path('logs');
if (File::exists($logPath)) {
    echo "✓ Log directory exists\n";
    $logFiles = File::files($logPath);
    echo "  Found " . count($logFiles) . " log files\n";
    
    // Check recent log files for sensitive data
    foreach ($logFiles as $logFile) {
        if (strpos($logFile->getFilename(), 'laravel-') === 0) {
            $logContent = File::get($logFile->getPathname());
            if (strpos($logContent, 'password') !== false || 
                strpos($logContent, 'email') !== false ||
                strpos($logContent, 'UndueMarmot') !== false) {
                echo "⚠️  Log file {$logFile->getFilename()} may contain sensitive data\n";
            }
        }
    }
}

// Test 3: Session data access
echo "\n3. SESSION DATA ACCESS:\n";
echo "----------------------\n";

$sessionPath = storage_path('framework/sessions');
if (File::exists($sessionPath)) {
    echo "✓ Session directory exists\n";
    $sessionFiles = File::files($sessionPath);
    echo "  Found " . count($sessionFiles) . " session files\n";
    
    // Check if any session files contain user data
    foreach ($sessionFiles as $sessionFile) {
        $sessionContent = File::get($sessionFile->getPathname());
        if (strpos($sessionContent, 'user_id') !== false || 
            strpos($sessionContent, 'email') !== false) {
            echo "⚠️  Session file {$sessionFile->getFilename()} contains user data\n";
        }
    }
}

// Test 4: Cache access
echo "\n4. CACHE ACCESS:\n";
echo "---------------\n";

$cachePath = storage_path('framework/cache');
if (File::exists($cachePath)) {
    echo "✓ Cache directory exists\n";
    $cacheFiles = File::files($cachePath);
    echo "  Found " . count($cacheFiles) . " cache files\n";
}

// Test 5: Configuration files
echo "\n5. CONFIGURATION FILES:\n";
echo "----------------------\n";

$configPath = config_path();
if (File::exists($configPath)) {
    echo "✓ Config directory exists\n";
    $configFiles = File::files($configPath);
    echo "  Found " . count($configFiles) . " config files\n";
    
    // Check specific config files for sensitive data
    $sensitiveConfigs = ['database.php', 'auth.php', 'session.php'];
    foreach ($sensitiveConfigs as $config) {
        $configFile = config_path($config);
        if (File::exists($configFile)) {
            echo "✓ {$config} exists\n";
        }
    }
}

// Test 6: User files access
echo "\n6. USER FILES ACCESS:\n";
echo "--------------------\n";

$userFiles = DB::table('user_files')->get();
echo "Found " . $userFiles->count() . " user files in database\n";

foreach ($userFiles as $file) {
    echo "  File: {$file->original_name}\n";
    echo "    Owner: User ID {$file->user_id}\n";
    echo "    Storage Path: {$file->storage_path}\n";
    echo "    Public: " . ($file->is_public ? 'Yes' : 'No') . "\n";
    echo "    Shared With: " . json_encode($file->shared_with_users) . "\n";
    echo "\n";
}

// Test 7: RAG chunks access
echo "\n7. RAG CHUNKS ACCESS:\n";
echo "--------------------\n";

$ragChunks = DB::table('rag_chunks')->select('source', 'user_id', DB::raw('COUNT(*) as chunk_count'))
    ->groupBy('source', 'user_id')
    ->get();

echo "Found " . $ragChunks->count() . " unique file/user combinations in RAG chunks\n";

foreach ($ragChunks as $chunk) {
    echo "  File: {$chunk->source}\n";
    echo "    User ID: " . ($chunk->user_id ?: 'SYSTEM') . "\n";
    echo "    Chunks: {$chunk->chunk_count}\n";
    echo "\n";
}

echo "\n=== SECURITY ASSESSMENT SUMMARY ===\n";
echo "✅ Database contains: Names, emails, password hashes, timestamps\n";
echo "✅ Log files may contain: User actions, errors, potentially sensitive data\n";
echo "✅ Session files may contain: User IDs, authentication data\n";
echo "✅ Configuration files contain: Database settings, app configuration\n";
echo "✅ User files contain: File metadata, sharing settings\n";
echo "✅ RAG chunks contain: File content, user associations\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Ensure .env file has restricted permissions (600)\n";
echo "2. Regularly rotate database passwords\n";
echo "3. Implement log rotation and sanitization\n";
echo "4. Use encrypted sessions\n";
echo "5. Implement proper file access controls\n";
echo "6. Consider data anonymization for logs\n"; 