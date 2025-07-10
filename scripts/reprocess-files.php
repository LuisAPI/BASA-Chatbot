<?php
// reprocess-files.php - Reprocess all files in uploads directory

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\ProcessFileForRAG;
use Illuminate\Support\Facades\Storage;

echo "🔄 Reprocessing all files in uploads directory...\n";

// Get all files in the uploads directory
$uploadPath = storage_path('app/private/uploads/');
$files = glob($uploadPath . '*');

if (empty($files)) {
    echo "❌ No files found in uploads directory.\n";
    exit(1);
}

echo "📁 Found " . count($files) . " files to reprocess:\n";

$processedCount = 0;
$errorCount = 0;

foreach ($files as $filePath) {
    $fileName = basename($filePath);
    $relativePath = str_replace(storage_path('app/private/'), '', $filePath);
    
    // Extract the original filename (remove the unique ID prefix)
    $originalName = preg_replace('/^[a-f0-9]+_/', '', $fileName);
    
    echo "  📄 Processing: {$originalName}\n";
    
    try {
        // Delete any existing chunks for this file
        \Illuminate\Support\Facades\DB::table('rag_chunks')
            ->where('source', $originalName)
            ->delete();
        
        // Dispatch the job
        ProcessFileForRAG::dispatch($relativePath, $originalName);
        
        $processedCount++;
        echo "    ✅ Queued for processing\n";
        
    } catch (\Exception $e) {
        $errorCount++;
        echo "    ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Summary:\n";
echo "   ✅ Successfully queued: {$processedCount} files\n";
echo "   ❌ Errors: {$errorCount} files\n";

if ($processedCount > 0) {
    echo "\n🚀 Next steps:\n";
    echo "   1. Run: php -d memory_limit=1G artisan queue:work\n";
    echo "   2. Or run: php -d memory_limit=1G artisan queue:work --once (for each file)\n";
    echo "   3. Check the file gallery at /chatbot/files to verify results\n";
}

echo "\n✨ Script completed!\n"; 