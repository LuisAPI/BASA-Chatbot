<?php

/**
 * Demo script for the enhanced File Gallery features
 * 
 * This script demonstrates the new static previews and list/grid view toggle
 * functionality added to the File Gallery.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BASA File Gallery Enhancement Demo ===\n\n";

// Check if we have any files in the database
$totalChunks = DB::table('rag_chunks')->count();
$totalFiles = DB::table('rag_chunks')->distinct('source')->count();

echo "Current Database Status:\n";
echo "- Total chunks: {$totalChunks}\n";
echo "- Total files: {$totalFiles}\n\n";

if ($totalFiles === 0) {
    echo "No files found in database. Creating sample data for demonstration...\n\n";
    
    // Create sample data
    $sampleFiles = [
        [
            'source' => 'DEPDev_Annual_Report_2024.pdf',
            'chunks' => [
                'The Department of Economy, Planning and Development (DEPDev) is pleased to present its annual report for 2024. This comprehensive document outlines our achievements, challenges, and strategic initiatives throughout the year.',
                'Key highlights include the successful implementation of infrastructure projects worth PHP 2.5 trillion, the launch of 15 new economic development programs, and the establishment of partnerships with 8 international organizations.',
                'Our digital transformation initiatives have resulted in a 40% improvement in service delivery efficiency, with 95% of citizen transactions now processed online.'
            ]
        ],
        [
            'source' => 'Economic_Policies_Presentation.pptx',
            'chunks' => [
                'Economic Policy Framework 2024-2028: This presentation outlines the comprehensive economic policies designed to promote sustainable growth and inclusive development.',
                'Key Policy Areas: 1) Infrastructure Development, 2) Digital Economy Promotion, 3) Green Energy Transition, 4) SME Support Programs, 5) Regional Economic Integration.',
                'Implementation Timeline: Phase 1 (2024-2025): Foundation Building, Phase 2 (2026-2027): Scaling Up, Phase 3 (2028): Full Implementation and Optimization.'
            ]
        ],
        [
            'source' => 'Infrastructure_Projects_2024.xlsx',
            'chunks' => [
                'Infrastructure Project Portfolio 2024: Total investment: PHP 3.2 trillion across 1,247 projects nationwide.',
                'Transportation Projects: 45 major road networks, 12 airport expansions, 8 seaport developments, 3 railway systems.',
                'Energy Projects: 25 renewable energy facilities, 15 transmission line upgrades, 8 smart grid implementations.',
                'Social Infrastructure: 150 new schools, 75 healthcare facilities, 45 community centers, 30 sports complexes.'
            ]
        ],
        [
            'source' => 'Regional_Development_Plan.txt',
            'chunks' => [
                'Regional Development Strategy 2024-2028: Focus on balanced regional growth and reducing development disparities.',
                'Priority Regions: 1) Mindanao Development Corridor, 2) Visayas Economic Zone, 3) Northern Luzon Growth Area, 4) Metro Manila Decongestion Program.',
                'Investment Allocation: Mindanao (35%), Visayas (25%), Luzon (30%), Metro Manila (10%).',
                'Expected Outcomes: 15% increase in regional GDP, 20% reduction in poverty rates, 25% improvement in infrastructure accessibility.'
            ]
        ]
    ];
    
    foreach ($sampleFiles as $file) {
        foreach ($file['chunks'] as $index => $chunk) {
            DB::table('rag_chunks')->insert([
                'source' => $file['source'],
                'chunk' => $chunk,
                'embedding' => json_encode(array_fill(0, 384, 0.1)), // Dummy embedding
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30)),
            ]);
        }
        echo "Created file: {$file['source']} with " . count($file['chunks']) . " chunks\n";
    }
    
    echo "\nSample data created successfully!\n\n";
}

// Now demonstrate the enhanced features
echo "=== Enhanced File Gallery Features ===\n\n";

// Get files with previews (simulating the controller logic)
$files = DB::table('rag_chunks')
    ->select('source', DB::raw('COUNT(*) as chunk_count'))
    ->groupBy('source')
    ->orderBy('source')
    ->get();

$filesWithPreviews = $files->map(function ($file) {
    $firstChunk = DB::table('rag_chunks')
        ->where('source', $file->source)
        ->select('chunk', 'created_at')
        ->orderBy('id')
        ->first();
    
    // Generate preview
    $preview = '';
    if ($firstChunk) {
        $preview = strip_tags($firstChunk->chunk);
        $preview = preg_replace('/\s+/', ' ', $preview);
        $preview = trim($preview);
        if (strlen($preview) > 200) {
            $preview = substr($preview, 0, 200) . '...';
        }
    }
    
    // Get file type
    $extension = strtolower(pathinfo($file->source, PATHINFO_EXTENSION));
    $typeMap = [
        'pdf' => 'PDF Document',
        'doc' => 'Word Document',
        'docx' => 'Word Document',
        'txt' => 'Text File',
        'ppt' => 'PowerPoint Presentation',
        'pptx' => 'PowerPoint Presentation',
        'xls' => 'Excel Spreadsheet',
        'xlsx' => 'Excel Spreadsheet',
        'csv' => 'CSV File'
    ];
    $fileType = $typeMap[$extension] ?? 'Document';
    
    // Calculate file size
    $totalSize = DB::table('rag_chunks')
        ->where('source', $file->source)
        ->sum(DB::raw('LENGTH(chunk)'));
    
    if ($totalSize < 1024) {
        $fileSize = $totalSize . ' B';
    } elseif ($totalSize < 1024 * 1024) {
        $fileSize = round($totalSize / 1024, 1) . ' KB';
    } else {
        $fileSize = round($totalSize / (1024 * 1024), 1) . ' MB';
    }
    
    return [
        'source' => $file->source,
        'chunk_count' => $file->chunk_count,
        'preview' => $preview,
        'file_type' => $fileType,
        'file_size' => $fileSize,
        'uploaded_at' => $firstChunk ? $firstChunk->created_at : null
    ];
});

echo "1. Static File Previews:\n";
echo "   Each file now shows a preview of its content (first 200 characters)\n\n";

foreach ($filesWithPreviews as $file) {
    echo "File: {$file['source']}\n";
    echo "Type: {$file['file_type']}\n";
    echo "Size: {$file['file_size']}\n";
    echo "Chunks: {$file['chunk_count']}\n";
    echo "Preview: {$file['preview']}\n";
    echo "Uploaded: " . ($file['uploaded_at'] ? date('M j, Y', strtotime($file['uploaded_at'])) : 'Unknown') . "\n";
    echo str_repeat('-', 80) . "\n";
}

echo "\n2. View Toggle Features:\n";
echo "   - Grid View: Card-based layout with file previews (default)\n";
echo "   - List View: Table format with all file details\n";
echo "   - Toggle buttons in the top-right corner\n\n";

echo "3. Enhanced File Information:\n";
echo "   - File type detection based on extension\n";
echo "   - Estimated file size calculation\n";
echo "   - Upload date display\n";
echo "   - Chunk count badges\n\n";

echo "4. Improved User Experience:\n";
echo "   - Hover effects on file cards\n";
echo "   - Responsive design for mobile/desktop\n";
echo "   - Clean, modern interface similar to Google Drive\n\n";

echo "To see the enhanced File Gallery in action:\n";
echo "1. Start the Laravel server: php artisan serve\n";
echo "2. Navigate to: http://localhost:8000/chatbot/files\n";
echo "3. Try switching between Grid and List views\n";
echo "4. Click 'View Chunks' to see detailed file content\n\n";

echo "=== Demo Complete ===\n"; 