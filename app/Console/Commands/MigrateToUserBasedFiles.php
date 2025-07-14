<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserFile;

class MigrateToUserBasedFiles extends Command
{
    protected $signature = 'files:migrate-to-user-based {--user-id=1 : Default user ID for existing files}';
    protected $description = 'Migrate existing files to user-based system';

    public function handle()
    {
        $defaultUserId = $this->option('user-id');
        
        // Check if default user exists
        $defaultUser = User::find($defaultUserId);
        if (!$defaultUser) {
            $this->error("User with ID {$defaultUserId} not found. Please create a user first.");
            return 1;
        }

        $this->info("Starting migration to user-based file system...");
        $this->info("Default user: {$defaultUser->name} (ID: {$defaultUserId})");

        // Get all unique file sources from rag_chunks
        $existingFiles = DB::table('rag_chunks')
            ->select('source')
            ->distinct()
            ->whereNull('user_id')
            ->get();

        if ($existingFiles->isEmpty()) {
            $this->info("No existing files to migrate.");
            return 0;
        }

        $this->info("Found {$existingFiles->count()} files to migrate.");

        $progressBar = $this->output->createProgressBar($existingFiles->count());
        $progressBar->start();

        $migratedCount = 0;
        $errorCount = 0;

        foreach ($existingFiles as $file) {
            try {
                $fileName = $file->source;
                
                // Skip system documents (they should remain global)
                if ($this->isSystemDocument($fileName)) {
                    $this->line("\nSkipping system document: {$fileName}");
                    continue;
                }

                // Create UserFile record
                $userFile = UserFile::create([
                    'user_id' => $defaultUserId,
                    'original_name' => $fileName,
                    'storage_path' => "uploads/user_{$defaultUserId}/" . uniqid() . "_{$fileName}",
                    'file_size' => 0, // Will be updated if file exists
                    'file_type' => $this->getFileType($fileName),
                    'is_public' => false,
                    'processing_status' => 'completed'
                ]);

                // Update rag_chunks with user_id
                DB::table('rag_chunks')
                    ->where('source', $fileName)
                    ->whereNull('user_id')
                    ->update(['user_id' => $defaultUserId]);

                $migratedCount++;
                
            } catch (\Exception $e) {
                $this->error("\nError migrating file {$fileName}: " . $e->getMessage());
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Migration completed!");
        $this->info("Successfully migrated: {$migratedCount} files");
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount} files");
        }

        return 0;
    }

    private function isSystemDocument(string $filename): bool
    {
        $documentsPath = public_path('documents');
        return $this->fileExistsInDirectory($documentsPath, $filename);
    }

    private function fileExistsInDirectory(string $directory, string $filename): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $items = \Illuminate\Support\Facades\File::files($directory);
        
        foreach ($items as $item) {
            if ($item->getFilename() === $filename) {
                return true;
            }
        }
        
        // Check subdirectories
        $subdirectories = \Illuminate\Support\Facades\File::directories($directory);
        foreach ($subdirectories as $subdirectory) {
            if ($this->fileExistsInDirectory($subdirectory, $filename)) {
                return true;
            }
        }
        
        return false;
    }

    private function getFileType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $typeMap = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv'
        ];
        
        return $typeMap[$extension] ?? 'application/octet-stream';
    }
} 