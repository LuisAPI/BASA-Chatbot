<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\UserFile;
use App\Models\User;

$systemFiles = ['pdp-2023-2028.pdf', 'office-circular-2025-03.pdf'];

$testUser = User::where('email', 'test@example.com')->first();
if (!$testUser) {
    echo "Test user (test@example.com) not found!\n";
    exit(1);
}
$testUserId = $testUser->id;
echo "Test user ID: $testUserId\n";

// Get all unique sources in rag_chunks
$allSources = DB::table('rag_chunks')->select('source')->distinct()->pluck('source');

foreach ($allSources as $source) {
    if (in_array($source, $systemFiles)) {
        // Leave as system file (user_id = null or 0)
        DB::table('rag_chunks')->where('source', $source)->where(function($q){ $q->whereNull('user_id')->orWhere('user_id', 0); })->update(['user_id' => null]);
        echo "[SYSTEM] $source left as system file\n";
        continue;
    }
    // For all other files, migrate to test user
    // 1. Create user_files record if not exists
    $userFile = UserFile::where('original_name', $source)->where('user_id', $testUserId)->first();
    if (!$userFile) {
        $userFile = UserFile::create([
            'user_id' => $testUserId,
            'original_name' => $source,
            'storage_path' => 'migrated/' . $source,
            'file_size' => 0,
            'file_type' => null,
            'processing_status' => 'completed',
            'is_public' => false,
            'shared_with_users' => [],
        ]);
        echo "[MIGRATE] Created user_files for $source\n";
    } else {
        echo "[MIGRATE] user_files already exists for $source\n";
    }
    // 2. Update rag_chunks
    $updated = DB::table('rag_chunks')->where('source', $source)->update(['user_id' => $testUserId]);
    echo "[MIGRATE] Updated $updated rag_chunks for $source to user_id $testUserId\n";
}

echo "Migration complete.\n"; 