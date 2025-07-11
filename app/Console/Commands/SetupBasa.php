<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SetupBasa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:basa {--skip-documents : Skip processing default documents} {--force : Force reprocessing of existing documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete setup for BASA chatbot including database, queues, and default documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting BASA Chatbot Setup...');
        $this->newLine();

        // Step 1: Check if database exists and is accessible
        $this->info('📊 Step 1: Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            $this->error('Please ensure your database is configured in .env file');
            return 1;
        }

        // Step 2: Run migrations
        $this->info('🔄 Step 2: Running database migrations...');
        try {
            $this->call('migrate', ['--force' => true]);
            $this->info('✅ Database migrations completed');
        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Step 3: Create queue tables (if using database queue)
        $this->info('📋 Step 3: Setting up queue tables...');
        try {
            $this->call('queue:table');
            $this->call('migrate', ['--force' => true]);
            $this->info('✅ Queue tables created');
        } catch (\Exception $e) {
            $this->warn('⚠️  Queue table creation failed (may already exist): ' . $e->getMessage());
        }

        // Step 4: Generate application key if not set
        $this->info('🔑 Step 4: Checking application key...');
        if (empty(config('app.key')) || config('app.key') === 'base64:') {
            $this->call('key:generate', ['--force' => true]);
            $this->info('✅ Application key generated');
        } else {
            $this->info('✅ Application key already set');
        }

        // Step 5: Process default documents (unless skipped)
        if (!$this->option('skip-documents')) {
            $this->info('📚 Step 5: Processing default government documents...');
            try {
                $forceFlag = $this->option('force') ? ['--force' => true] : [];
                $this->call('documents:process-default', $forceFlag);
                $this->info('✅ Default documents processed');
            } catch (\Exception $e) {
                $this->warn('⚠️  Document processing failed: ' . $e->getMessage());
                $this->warn('You can run this manually later with: php artisan documents:process-default');
            }
        } else {
            $this->info('⏭️  Step 5: Skipping document processing (--skip-documents flag used)');
        }

        // Step 6: Clear caches
        $this->info('🧹 Step 6: Clearing application caches...');
        try {
            $this->call('config:clear');
            $this->call('cache:clear');
            $this->call('view:clear');
            $this->call('route:clear');
            $this->info('✅ Application caches cleared');
        } catch (\Exception $e) {
            $this->warn('⚠️  Cache clearing failed: ' . $e->getMessage());
        }

        // Step 7: Check for required directories
        $this->info('📁 Step 7: Checking required directories...');
        $requiredDirs = [
            storage_path('app/private/uploads'),
            public_path('documents'),
        ];

        foreach ($requiredDirs as $dir) {
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
                $this->info("✅ Created directory: {$dir}");
            } else {
                $this->info("✅ Directory exists: {$dir}");
            }
        }

        // Step 8: Display next steps
        $this->newLine();
        $this->info('🎉 BASA Chatbot Setup Complete!');
        $this->newLine();
        
        $this->info('📋 Next Steps:');
        $this->line('1. Start the Laravel development server:');
        $this->line('   php artisan serve --port=8080');
        $this->newLine();
        
        $this->line('2. Start the queue worker (in a separate terminal):');
        $this->line('   php artisan queue:work');
        $this->newLine();
        
        $this->line('3. Start the Vite development server (in another terminal):');
        $this->line('   npm run dev');
        $this->newLine();
        
        $this->line('4. Make sure Ollama is running with your models:');
        $this->line('   ollama run tinyllama');
        $this->line('   ollama pull nomic-embed-text');
        $this->newLine();
        
        $this->line('5. Access the chatbot at: http://localhost:8080/chatbot');
        $this->newLine();
        
        $this->info('📖 For detailed setup instructions, see the README.md file');
        $this->info('🐛 If you encounter issues, try the step-by-step setup in README.md');

        return 0;
    }
} 