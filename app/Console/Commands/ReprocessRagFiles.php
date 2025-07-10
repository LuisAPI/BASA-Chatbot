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

        // Get files to reprocess
        $files = $this->getFilesToReprocess();

        if (empty($files)) {
            $this->warn('No files found to reprocess.');
            return;
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN - Files that would be reprocessed:');
            foreach ($files as $file) {
                $this->line("- {$file}");
            }
            return;
        }

        $this->info("Found " . count($files) . " files to reprocess.");

        // Clear existing chunks for these files
        $this->clearExistingChunks($files);

        // Reprocess files
        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $fileName) {
            try {
                // Find the storage path for this file
                $storagePath = $this->findStoragePath($fileName);
                
                if ($storagePath) {
                    // Dispatch job to reprocess
                    ProcessFileForRAG::dispatch($storagePath, $fileName);
                    $this->line("\nQueued: {$fileName}");
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
        if ($this->option('file')) {
            $fileName = $this->option('file');
            $files = DB::table('rag_chunks')
                ->where('source', $fileName)
                ->distinct()
                ->pluck('source')
                ->toArray();
            
            if (empty($files)) {
                $this->warn("No chunks found for file: {$fileName}");
            }
            
            return $files;
        }

        if ($this->option('all')) {
            return DB::table('rag_chunks')
                ->distinct()
                ->pluck('source')
                ->toArray();
        }

        // Default: reprocess files with more than 10 chunks (likely need optimization)
        return DB::table('rag_chunks')
            ->select('source')
            ->groupBy('source')
            ->havingRaw('COUNT(*) > 10')
            ->pluck('source')
            ->toArray();
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
        // This is a simplified approach - in a real implementation,
        // you might want to store the storage path in the database
        // or have a more sophisticated file tracking system
        
        $storage = storage_path('app/uploads');
        $files = glob($storage . '/*' . $fileName);
        
        if (!empty($files)) {
            return str_replace(storage_path('app/'), '', $files[0]);
        }
        
        return null;
    }
} 