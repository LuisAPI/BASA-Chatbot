<?php

namespace App\Console\Commands;

use App\Jobs\ProcessFileForRAG;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ProcessDefaultDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:process-default {--force : Force reprocessing of existing documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process default government documents for RAG system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentsPath = public_path('documents');
        
        if (!File::exists($documentsPath)) {
            $this->error("Documents directory not found: {$documentsPath}");
            return 1;
        }

        $this->info("Processing default documents from: {$documentsPath}");
        
        // Get all files recursively from the documents directory and subdirectories
        $files = collect();
        $this->getAllFilesRecursively($documentsPath, $files);
        
        if ($files->isEmpty()) {
            $this->warn("No documents found in {$documentsPath} or its subdirectories");
            $this->info("Please add PDF files to the documents directory and run this command again.");
            return 0;
        }

        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            $filePath = $file->getPathname();
            
            // Skip non-PDF files and README
            if ($file->getExtension() !== 'pdf' || $fileName === 'README.md') {
                $progressBar->advance();
                continue;
            }

            try {
                // Check if document is already processed
                $existingChunks = DB::table('rag_chunks')
                    ->where('source', $fileName)
                    ->count();

                if ($existingChunks > 0 && !$this->option('force')) {
                    $this->line("\nSkipping {$fileName} (already processed with {$existingChunks} chunks)");
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // If forcing reprocessing, delete existing chunks
                if ($existingChunks > 0 && $this->option('force')) {
                    DB::table('rag_chunks')
                        ->where('source', $fileName)
                        ->delete();
                    $this->line("\nReprocessing {$fileName} (deleted {$existingChunks} existing chunks)");
                }

                // Create a relative path for the job (preserve subdirectory structure)
                $relativePath = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $filePath);
                $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
                
                // Dispatch the processing job
                ProcessFileForRAG::dispatch($relativePath, $fileName);
                
                $this->line("\nQueued {$fileName} for processing");
                $processedCount++;

            } catch (\Exception $e) {
                $this->error("\nError processing {$fileName}: " . $e->getMessage());
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Processing complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Processed', $processedCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
            ]
        );

        if ($processedCount > 0) {
            $this->info("\nDocuments are being processed in the background.");
            $this->info("Run 'php artisan queue:work' to process the jobs.");
            $this->info("You can check processing status in the File Gallery.");
        }

        return 0;
    }

    /**
     * Recursively collect all files from a directory and its subdirectories.
     */
    private function getAllFilesRecursively(string $directory, \Illuminate\Support\Collection &$files): void
    {
        $items = File::files($directory);
        
        foreach ($items as $item) {
            // Skip README files
            if ($item->getFilename() === 'README.md') {
                continue;
            }
            
            $files->push($item);
        }
        
        // Get subdirectories and recurse
        $subdirectories = File::directories($directory);
        foreach ($subdirectories as $subdirectory) {
            $this->getAllFilesRecursively($subdirectory, $files);
        }
    }
} 