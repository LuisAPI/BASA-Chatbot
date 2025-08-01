<?php

namespace App\Console\Commands;

use App\Events\FileProcessed;
use App\Events\FileFailed;
use App\Events\WebpageProcessed;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestProcessingEvents extends Command
{
    protected $signature = 'test:processing-events 
                            {type=all : Type of event to test (file/webpage/all)} 
                            {--status=completed : Status to emit (completed/failed)} 
                            {--name=test.txt : Name of the file for file events}
                            {--url=https://example.com : URL for webpage events}';

    protected $description = 'Test file and webpage processing events';

    public function handle()
    {
        $type = $this->argument('type');
        $status = $this->option('status');
        $fileName = $this->option('name');
        $url = $this->option('url');

        if ($type === 'all' || $type === 'file') {
            $this->testFileEvents($fileName, $status);
        }

        if ($type === 'all' || $type === 'webpage') {
            $this->testWebpageEvents($url, $status);
        }
    }

    protected function testFileEvents($fileName, $status)
    {
        $this->info("Testing file processing events...");
        
        if ($status === 'completed') {
            event(new FileProcessed($fileName));
            $this->info("✓ Emitted FileProcessed event for '{$fileName}'");
        } else {
            event(new FileFailed($fileName, "Test failure message"));
            $this->info("✓ Emitted FileFailed event for '{$fileName}'");
        }
    }

    protected function testWebpageEvents($url, $status)
    {
        $this->info("Testing webpage processing events...");

        // First ensure we have a webpage record
        $webpageId = md5($url);
        DB::table('webpages')->updateOrInsert(
            ['webpage_id' => $webpageId],
            [
                'url' => $url,
                'title' => 'Test Page',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // Create a test chunk if completed
        if ($status === 'completed') {
            DB::table('webpage_chunks')->updateOrInsert(
                ['webpages_id' => DB::table('webpages')->where('webpage_id', $webpageId)->first()->id],
                [
                    'chunk' => 'Test chunk content',
                    'embedding' => json_encode([0.1, 0.2, 0.3]), // dummy embeddings
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        event(new WebpageProcessed($url, $status, $status === 'failed' ? "Test failure message" : null));
        $this->info("✓ Emitted WebpageProcessed event for '{$url}' with status '{$status}'");
    }
}
