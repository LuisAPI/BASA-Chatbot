<?php

namespace App\Console\Commands;

use App\Services\OllamaConnectionService;
use Illuminate\Console\Command;

class CheckOllamaConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:status {--detailed : Show detailed connection information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Ollama connection status and available endpoints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Ollama connections...');
        $this->newLine();

        $ollamaService = new OllamaConnectionService();
        $status = $ollamaService->getConnectionStatus();

        // Display connection status
        if ($status['connected']) {
            $this->info('✅ Ollama connection successful!');
            $this->line("Active endpoint: {$status['active_endpoint']}");
        } else {
            $this->error('❌ No Ollama connection found');
            $this->newLine();
            
            // Show failure information
            $failure = $ollamaService->handleConnectionFailure();
            $this->error($failure['message']);
            $this->newLine();
            
            // Show suggestions
            if (!empty($failure['suggestions'])) {
                $this->warn('💡 Suggestions:');
                foreach ($failure['suggestions'] as $suggestion) {
                    $this->line("  • {$suggestion}");
                }
                $this->newLine();
            }
        }

        // Show detailed information if detailed flag is used
        if ($this->option('detailed')) {
            $this->showDetailedInformation($status, $ollamaService);
        }

        // Show available endpoints
        if (!empty($status['available_endpoints'])) {
            $this->info('📡 Available endpoints:');
            foreach ($status['available_endpoints'] as $endpoint) {
                $icon = $endpoint === $status['active_endpoint'] ? '✅' : '🔗';
                $this->line("  {$icon} {$endpoint}");
            }
        }

        // Show user preference
        if ($status['user_preference']) {
            $this->newLine();
            $this->info('👤 User preference:');
            $this->line("  {$status['user_preference']}");
        }

        // Show configuration summary
        $this->newLine();
        $this->info('⚙️  Configuration:');
        $this->line("  Auto-discovery: " . ($status['auto_discover_enabled'] ? 'Enabled' : 'Disabled'));
        $this->line("  Fallback endpoints: " . count($status['fallback_endpoints']));

        return $status['connected'] ? 0 : 1;
    }

    /**
     * Show detailed connection information
     */
    protected function showDetailedInformation(array $status, OllamaConnectionService $ollamaService): void
    {
        $this->newLine();
        $this->info('🔧 Detailed Information:');

        // Test each fallback endpoint individually
        $this->line('Testing fallback endpoints:');
        foreach ($status['fallback_endpoints'] as $endpoint) {
            $isWorking = $ollamaService->testEndpoint($endpoint);
            $icon = $isWorking ? '✅' : '❌';
            $this->line("  {$icon} {$endpoint}");
        }

        // Show auto-discovery results if enabled
        if ($status['auto_discover_enabled']) {
            $this->newLine();
            $this->line('🔍 Auto-discovery results:');
            $discovered = $ollamaService->autoDiscoverEndpoints();
            
            if (!empty($discovered)) {
                foreach ($discovered as $endpoint) {
                    $this->line("  🔗 {$endpoint}");
                }
            } else {
                $this->line("  ❌ No instances discovered");
            }
        }

        // Show configuration details
        $this->newLine();
        $this->line('📋 Configuration details:');
        $config = config('ollama');
        $this->line("  Timeout: {$config['timeout']}s");
        $this->line("  Retry attempts: {$config['retry_attempts']}");
        $this->line("  Discovery ports: " . implode(', ', $config['discovery_ports']));
        $this->line("  Preferences storage: {$config['preferences_storage']}");
    }
} 