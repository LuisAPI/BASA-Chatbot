<?php
// test-file-processing.php - Test processing a single file

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Jobs\ProcessFileForRAG;

echo "🧪 Testing file processing...\n";

// Test with the 2025 Automation Roadmap file
$filePath = 'uploads/68677891eded7_2025 Automation Roadmap (2).pdf';
$fileName = '2025 Automation Roadmap (2).pdf';

echo "📄 Testing file: {$fileName}\n";

try {
    // Delete any existing chunks
    \Illuminate\Support\Facades\DB::table('rag_chunks')
        ->where('source', $fileName)
        ->delete();
    
    echo "✅ Deleted existing chunks\n";
    
    // Create the job and run it directly
    $job = new ProcessFileForRAG($filePath, $fileName);
    $job->handle();
    
    echo "✅ File processed successfully!\n";
    
    // Check if chunks were created
    $chunkCount = \Illuminate\Support\Facades\DB::table('rag_chunks')
        ->where('source', $fileName)
        ->count();
    
    echo "📊 Created {$chunkCount} chunks\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📋 Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "✨ Test completed!\n"; 