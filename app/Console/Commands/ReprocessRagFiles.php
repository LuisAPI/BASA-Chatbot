<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessFileForRAG;

class ReprocessRagFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rag:reprocess 
                            {--file= : Specific file to reprocess}
                            {--all : Reprocess all files}
                            {--dry-run : Show what would be processed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reprocess uploaded files for RAG with improved chunking';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting RAG file reprocessing...');

        // Get files to reprocess (now returns array of ['filename', 'relativePath'])
        $files = $this->getFilesToReprocess();

        if (empty($files)) {
            $this->warn('No files found to reprocess.');
            return;
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN - Files that would be reprocessed:');
            foreach ($files as $file) {
                $this->line("- {$file['relativePath']}");
            }
            return;
        }

        $this->info("Found " . count($files) . " files to reprocess.");

        // Clear existing chunks for these files (by filename)
        $this->clearExistingChunks(array_map(fn($f) => $f['filename'], $files));

        // Reprocess files
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $fileName = $file['filename'];
            $storagePath = $file['relativePath'];
            try {
                if ($storagePath) {
                    // Dispatch job to reprocess
                    ProcessFileForRAG::dispatch($storagePath, $fileName);
                    $this->line("\nQueued: {$fileName} ({$storagePath})");
                } else {
                    $this->warn("\nStorage path not found for: {$fileName}");
                }
            } catch (\Exception $e) {
                $this->error("\nError processing {$fileName}: " . $e->getMessage());
                Log::error("Error reprocessing file {$fileName}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('File reprocessing completed!');
        $this->info('Run "php artisan queue:work" to process the queued jobs.');
    }

    /**
     * Get files to reprocess based on options.
     */
    private function getFilesToReprocess(): array
    {
        $dbFiles = DB::table('rag_chunks')
            ->distinct()
            ->pluck('source')
            ->toArray();

        $storagePath = storage_path('app/private/uploads');
        $allFiles = [];
        if (is_dir($storagePath)) {
            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($rii as $file) {
                if ($file->isFile()) {
                    // Store relative path from storage/app
                    $fullPath = $file->getRealPath();
                    $storageAppPath = storage_path('app');
                    $relativePath = ltrim(str_replace(['\\', $storageAppPath], ['/', ''], $fullPath), '/');
                    $allFiles[] = [
                        'filename' => $file->getFilename(),
                        'relativePath' => $relativePath
                    ];
                }
            }
        }

        if ($this->option('file')) {
            $fileName = $this->option('file');
            $found = array_filter($allFiles, fn($f) => $f['filename'] === $fileName);
            return array_values($found);
        }

        if ($this->option('all')) {
            // Union of files in storage and DB
            // Only files physically present in storage
            return array_values($allFiles);
        }

        // Default: files with >10 chunks OR files in storage not in DB
        $filesWithManyChunks = DB::table('rag_chunks')
            ->select('source')
            ->groupBy('source')
            ->havingRaw('COUNT(*) > 10')
            ->pluck('source')
            ->toArray();
        $storageFileNames = array_map(fn($f) => $f['filename'], $allFiles);
        $newFiles = array_filter($allFiles, fn($f) => !in_array($f['filename'], $dbFiles));
        $result = [];
        // Add files with >10 chunks (by filename)
        foreach ($allFiles as $f) {
            if (in_array($f['filename'], $filesWithManyChunks)) {
                $result[] = $f;
            }
        }
        // Add new files not in DB
        foreach ($newFiles as $f) {
            $result[] = $f;
        }
        // Remove duplicates by relativePath
        $unique = [];
        foreach ($result as $f) {
            $unique[$f['relativePath']] = $f;
        }
        return array_values($unique);
    }

    /**
     * Clear existing chunks for the specified files.
     */
    private function clearExistingChunks(array $files): void
    {
        $this->info('Clearing existing chunks...');
        
        $deleted = DB::table('rag_chunks')
            ->whereIn('source', $files)
            ->delete();
            
        $this->info("Deleted {$deleted} existing chunks.");
    }

    /**
     * Find the storage path for a file.
     */
    private function findStoragePath(string $fileName): ?string
    {
        // No longer needed: getFilesToReprocess returns relativePath directly
        return null;
    }
} 