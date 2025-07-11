<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OllamaConnectionService
{
    protected $config;
    protected $userPreferences;

    public function __construct()
    {
        $this->config = config('ollama');
        $this->loadUserPreferences();
    }

    /**
     * Get the best available Ollama endpoint
     */
    public function getActiveEndpoint(): ?string
    {
        // 1. Try user's preferred endpoint first
        if ($this->userPreferences && $this->testEndpoint($this->userPreferences)) {
            return $this->userPreferences;
        }

        // 2. Try default endpoint
        if ($this->testEndpoint($this->config['default_endpoint'])) {
            return $this->config['default_endpoint'];
        }

        // 3. Try fallback endpoints
        foreach ($this->config['fallback_endpoints'] as $endpoint) {
            if ($this->testEndpoint($endpoint)) {
                return $endpoint;
            }
        }

        // 4. Auto-discover if enabled
        if ($this->config['auto_discover']) {
            $discovered = $this->autoDiscoverEndpoints();
            if (!empty($discovered)) {
                return $discovered[0];
            }
        }

        return null;
    }

    /**
     * Test if an endpoint is reachable and responding
     */
    public function testEndpoint(string $endpoint): bool
    {
        try {
            $request = $this->createAuthenticatedRequest();
            $response = $request->timeout($this->config['timeout'])
                ->get($endpoint . '/api/tags');

            return $response->successful();
        } catch (\Exception $e) {
            Log::debug("Ollama endpoint test failed for {$endpoint}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create an HTTP request with authentication if required
     */
    public function createAuthenticatedRequest()
    {
        $request = Http::timeout($this->config['timeout']);
        
        // Add authentication if required
        if ($this->config['require_auth'] && $this->config['api_key']) {
            $request->withHeaders([
                'Authorization' => 'Bearer ' . $this->config['api_key']
            ]);
        }
        
        // Add SSL verification setting
        if (!$this->config['verify_ssl']) {
            $request->withoutVerifying();
        }
        
        return $request;
    }

    /**
     * Auto-discover Ollama instances on the network
     */
    public function autoDiscoverEndpoints(): array
    {
        $discovered = [];
        $localIPs = $this->getLocalIPs();

        foreach ($localIPs as $ip) {
            foreach ($this->config['discovery_ports'] as $port) {
                $endpoint = "http://{$ip}:{$port}";
                if ($this->testEndpoint($endpoint)) {
                    $discovered[] = $endpoint;
                }
            }
        }

        return $discovered;
    }

    /**
     * Get all available endpoints (including discovered ones)
     */
    public function getAllAvailableEndpoints(): array
    {
        $endpoints = [];

        // Add user preference
        if ($this->userPreferences) {
            $endpoints[] = $this->userPreferences;
        }

        // Add default and fallback endpoints
        $endpoints[] = $this->config['default_endpoint'];
        $endpoints = array_merge($endpoints, $this->config['fallback_endpoints']);

        // Add discovered endpoints if auto-discovery is enabled
        if ($this->config['auto_discover']) {
            $discovered = $this->autoDiscoverEndpoints();
            $endpoints = array_merge($endpoints, $discovered);
        }

        // Remove duplicates and test each endpoint
        $endpoints = array_unique($endpoints);
        $available = [];

        foreach ($endpoints as $endpoint) {
            if ($this->testEndpoint($endpoint)) {
                $available[] = $endpoint;
            }
        }

        return $available;
    }

    /**
     * Set user's preferred endpoint
     */
    public function setUserPreference(string $endpoint): bool
    {
        if (!$this->testEndpoint($endpoint)) {
            return false;
        }

        $this->userPreferences = $endpoint;
        $this->saveUserPreferences($endpoint);
        return true;
    }

    /**
     * Get connection status information
     */
    public function getConnectionStatus(): array
    {
        $activeEndpoint = $this->getActiveEndpoint();
        $allEndpoints = $this->getAllAvailableEndpoints();

        return [
            'connected' => !empty($activeEndpoint),
            'active_endpoint' => $activeEndpoint,
            'available_endpoints' => $allEndpoints,
            'user_preference' => $this->userPreferences,
            'auto_discover_enabled' => $this->config['auto_discover'],
            'fallback_endpoints' => $this->config['fallback_endpoints'],
        ];
    }

    /**
     * Handle connection failure with user-friendly error
     */
    public function handleConnectionFailure(): array
    {
        $status = $this->getConnectionStatus();
        
        return [
            'error' => 'Ollama Connection Failed',
            'message' => 'Unable to connect to any Ollama instance',
            'available_endpoints' => $status['available_endpoints'],
            'installation_url' => $this->config['installation_url'],
            'show_instructions' => $this->config['show_installation_instructions'],
            'exit_on_failure' => $this->config['exit_on_connection_failure'],
            'suggestions' => $this->getConnectionSuggestions(),
        ];
    }

    /**
     * Get suggestions for fixing connection issues
     */
    protected function getConnectionSuggestions(): array
    {
        $suggestions = [];

        if (empty($this->getAllAvailableEndpoints())) {
            $suggestions[] = 'Install Ollama from ' . $this->config['installation_url'];
            $suggestions[] = 'Start Ollama service: ollama serve';
            $suggestions[] = 'Check if Ollama is running on a different port';
        }

        if ($this->config['auto_discover']) {
            $suggestions[] = 'Enable network discovery for Ollama instances';
        }

        return $suggestions;
    }

    /**
     * Load user preferences based on storage configuration
     */
    protected function loadUserPreferences(): void
    {
        $storage = $this->config['preferences_storage'];

        switch ($storage) {
            case 'database':
                $this->userPreferences = $this->loadFromDatabase();
                break;
            case 'file':
                $this->userPreferences = $this->loadFromFile();
                break;
            case 'session':
                $this->userPreferences = session('ollama_endpoint');
                break;
            default:
                $this->userPreferences = null;
        }
    }

    /**
     * Save user preferences based on storage configuration
     */
    protected function saveUserPreferences(string $endpoint): void
    {
        $storage = $this->config['preferences_storage'];

        switch ($storage) {
            case 'database':
                $this->saveToDatabase($endpoint);
                break;
            case 'file':
                $this->saveToFile($endpoint);
                break;
            case 'session':
                session(['ollama_endpoint' => $endpoint]);
                break;
        }
    }

    /**
     * Load preferences from database
     */
    protected function loadFromDatabase(): ?string
    {
        try {
            // Check if settings table exists
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return null;
            }

            $setting = DB::table('settings')
                ->where('key', 'ollama_endpoint')
                ->first();

            return $setting ? $setting->value : null;
        } catch (\Exception $e) {
            Log::warning('Failed to load Ollama endpoint from database: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save preferences to database
     */
    protected function saveToDatabase(string $endpoint): void
    {
        try {
            // Create settings table if it doesn't exist
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                DB::statement('CREATE TABLE settings (id INTEGER PRIMARY KEY, key TEXT UNIQUE, value TEXT)');
            }

            DB::table('settings')->updateOrInsert(
                ['key' => 'ollama_endpoint'],
                ['value' => $endpoint]
            );
        } catch (\Exception $e) {
            Log::error('Failed to save Ollama endpoint to database: ' . $e->getMessage());
        }
    }

    /**
     * Load preferences from file
     */
    protected function loadFromFile(): ?string
    {
        $file = storage_path('app/ollama_preferences.json');
        
        if (!file_exists($file)) {
            return null;
        }

        try {
            $data = json_decode(file_get_contents($file), true);
            return $data['endpoint'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to load Ollama endpoint from file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save preferences to file
     */
    protected function saveToFile(string $endpoint): void
    {
        $file = storage_path('app/ollama_preferences.json');
        
        try {
            $data = ['endpoint' => $endpoint, 'updated_at' => now()->toISOString()];
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Failed to save Ollama endpoint to file: ' . $e->getMessage());
        }
    }

    /**
     * Get local IP addresses for auto-discovery
     */
    protected function getLocalIPs(): array
    {
        $ips = ['127.0.0.1', 'localhost'];
        
        // Add common local network IPs
        $hostname = gethostname();
        $localIP = gethostbyname($hostname);
        
        if ($localIP && $localIP !== $hostname) {
            $ips[] = $localIP;
            
            // Add common local network ranges
            $parts = explode('.', $localIP);
            if (count($parts) === 4) {
                $ips[] = "{$parts[0]}.{$parts[1]}.{$parts[2]}.1"; // Gateway
                $ips[] = "{$parts[0]}.{$parts[1]}.{$parts[2]}.100";
                $ips[] = "{$parts[0]}.{$parts[1]}.{$parts[2]}.200";
            }
        }

        return array_unique($ips);
    }
} 