<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessFileForRAG;

class FixPdfParsingErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:fix-parsing-errors {--dry-run : Show what would be fixed without making changes} {--reprocess : Reprocess all files with improved chunking}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PDF parsing errors by re-processing files with correct file paths';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 PDF Parsing Errors Fix Command');
        $this->info('================================');
        $this->newLine();

        // Check if user wants to reprocess all files with improved chunking
        if ($this->option('reprocess')) {
            $this->reprocessForBetterStructure();
            return 0;
        }

        try {
            // Find all files with PDF parsing errors
            $this->info('📋 Searching for files with PDF parsing errors...');
            
            $filesWithErrors = DB::table('rag_chunks')
                ->where('chunk', 'like', '%PDF parsing failed%')
                ->select('source')
                ->distinct()
                ->get();
            
            if ($filesWithErrors->isEmpty()) {
                $this->info('✅ No files with PDF parsing errors found!');
                return 0;
            }
            
            $this->warn('❌ Found ' . $filesWithErrors->count() . ' files with parsing errors:');
            foreach ($filesWithErrors as $file) {
                $this->line('   - ' . $file->source);
            }
            
            if ($this->option('dry-run')) {
                $this->newLine();
                $this->info('🔍 DRY RUN MODE - No changes will be made');
                $this->info('Run without --dry-run to actually fix the errors');
                return 0;
            }
            
            $this->newLine();
            $this->info('🔄 Starting fix process...');
            
            $fixedCount = 0;
            $errorCount = 0;
            
            $progressBar = $this->output->createProgressBar($filesWithErrors->count());
            $progressBar->start();
            
            foreach ($filesWithErrors as $file) {
                $fileName = $file->source;
                try {
                    // Step 1: Delete existing chunks for this file
                    $deletedChunks = DB::table('rag_chunks')
                        ->where('source', $fileName)
                        ->delete();

                    // Step 2: Find the actual file in storage (recursive search)
                    $basePath = storage_path('app/private/uploads');
                    $foundPath = null;
                    $rii = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($rii as $f) {
                        if ($f->isFile() && $f->getFilename() === $fileName) {
                            $foundPath = $f->getRealPath();
                            break;
                        }
                    }

                    if (!$foundPath) {
                        $this->error("File not found in storage: " . $fileName);
                        $errorCount++;
                        $progressBar->advance();
                        continue;
                    }

                    // Normalize slashes
                    $normalizedPath = str_replace('\\', '/', $foundPath);
                    // Strip everything before and including 'storage/app/'
                    $needle = '/storage/app/';
                    $pos = strpos($normalizedPath, $needle);
                    if ($pos !== false) {
                        $relativePath = substr($normalizedPath, $pos + strlen($needle));
                        // Ensure it starts with private/uploads/
                        if (strpos($relativePath, 'private/uploads/') === 0) {
                            ProcessFileForRAG::dispatch($relativePath, $fileName);
                            $fixedCount++;
                        } else {
                            $this->error("Relative path incorrect for: " . $fileName . " (got: $relativePath)");
                            $errorCount++;
                        }
                    } else {
                        $this->error("Could not find storage/app/ in path for: " . $fileName . " (got: $normalizedPath)");
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    $this->error("Error processing {$fileName}: " . $e->getMessage());
                    $errorCount++;
                }
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info('📊 Summary:');
            $this->line('   ✅ Successfully queued for re-processing: ' . $fixedCount . ' files');
            $this->line('   ❌ Errors: ' . $errorCount . ' files');
            
            if ($fixedCount > 0) {
                $this->newLine();
                $this->info('🚀 Next steps:');
                $this->line('   1. Run: php artisan queue:work --once (for each file)');
                $this->line('   2. Or run: php artisan queue:work (to process all jobs)');
                $this->line('   3. Check the file gallery at /chatbot/files to verify results');
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Command error: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->newLine();
        $this->info('✨ Command completed!');
        return 0;
    }

    /**
     * Reprocess files with improved chunking for better structured content
     */
    public function reprocessForBetterStructure()
    {
        $this->info('🔄 Reprocessing files with improved chunking for better structured content...');
        
        // Get all processed files
        $processedFiles = DB::table('rag_chunks')
            ->select('source')
            ->distinct()
            ->get();
        
        if ($processedFiles->isEmpty()) {
            $this->warn('No processed files found to reprocess.');
            return;
        }
        
        $this->info('Found ' . $processedFiles->count() . ' files to reprocess.');
        
        $progressBar = $this->output->createProgressBar($processedFiles->count());
        $progressBar->start();
        
        $reprocessedCount = 0;
        $errorCount = 0;
        
        foreach ($processedFiles as $file) {
            $fileName = $file->source;
            try {
                // Step 1: Delete existing chunks for this file
                $deletedChunks = DB::table('rag_chunks')
                    ->where('source', $fileName)
                    ->delete();

                // Step 2: Find the actual file in storage (recursive search)
                $basePath = storage_path('app/private/uploads');
                $foundPath = null;
                $rii = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($rii as $f) {
                    if ($f->isFile() && $f->getFilename() === $fileName) {
                        $foundPath = $f->getRealPath();
                        break;
                    }
                }

                if (!$foundPath) {
                    $this->error("File not found in storage: " . $fileName);
                    $errorCount++;
                    $progressBar->advance();
                    continue;
                }

                // Normalize slashes
                $normalizedPath = str_replace('\\', '/', $foundPath);
                // Strip everything before and including 'storage/app/'
                $needle = '/storage/app/';
                $pos = strpos($normalizedPath, $needle);
                if ($pos !== false) {
                    $relativePath = substr($normalizedPath, $pos + strlen($needle));
                    // Ensure it starts with private/uploads/
                    if (strpos($relativePath, 'private/uploads/') === 0) {
                        ProcessFileForRAG::dispatch($relativePath, $fileName);
                        $reprocessedCount++;
                    } else {
                        $this->error("Relative path incorrect for: " . $fileName . " (got: $relativePath)");
                        $errorCount++;
                    }
                } else {
                    $this->error("Could not find storage/app/ in path for: " . $fileName . " (got: $normalizedPath)");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->error("Error reprocessing {$fileName}: " . $e->getMessage());
                $errorCount++;
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info('📊 Reprocessing Summary:');
        $this->line('   ✅ Successfully queued for reprocessing: ' . $reprocessedCount . ' files');
        $this->line('   ❌ Errors: ' . $errorCount . ' files');
        $this->line('   🔄 Files will be reprocessed with improved chunking for better structured content');
    }
}
