<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Ollama Endpoint
    |--------------------------------------------------------------------------
    |
    | The default endpoint where Ollama is running. This is used as the
    | primary connection point for the BASA chatbot.
    |
    */
    'default_endpoint' => env('OLLAMA_ENDPOINT', 'http://localhost:11434'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Endpoints
    |--------------------------------------------------------------------------
    |
    | List of endpoints to try if the default endpoint fails. The system
    | will attempt to connect to each endpoint in order until one succeeds.
    |
    */
    'fallback_endpoints' => [
        'http://localhost:11434',
        'http://127.0.0.1:11434',
        env('OLLAMA_CORPORATE_ENDPOINT', 'https://ollama.depdev.gov.ph'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Timeout and retry settings for Ollama API calls.
    |
    */
    'timeout' => env('OLLAMA_TIMEOUT', 30),
    'retry_attempts' => env('OLLAMA_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('OLLAMA_RETRY_DELAY', 2),

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Settings
    |--------------------------------------------------------------------------
    |
    | Settings for automatically discovering Ollama instances on the network.
    |
    */
    'auto_discover' => env('OLLAMA_AUTO_DISCOVER', true),
    'discovery_ports' => [11434, 11435, 11436],
    'discovery_timeout' => env('OLLAMA_DISCOVERY_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Default models to use for different tasks.
    |
    */
    'default_models' => [
        'chat' => env('OLLAMA_CHAT_MODEL', 'tinyllama'),
        'embedding' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Authentication and security settings for Ollama connections.
    |
    */
    'require_auth' => env('OLLAMA_REQUIRE_AUTH', false),
    'api_key' => env('OLLAMA_API_KEY'),
    'verify_ssl' => env('OLLAMA_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | User Preferences Storage
    |--------------------------------------------------------------------------
    |
    | How to store user-specific Ollama endpoint preferences.
    |
    */
    'preferences_storage' => env('OLLAMA_PREFERENCES_STORAGE', 'database'), // 'database', 'file', 'session'

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | How to handle connection failures and errors.
    |
    */
    'show_installation_instructions' => env('OLLAMA_SHOW_INSTALL_INSTRUCTIONS', true),
    'installation_url' => env('OLLAMA_INSTALLATION_URL', 'https://ollama.ai/download'),
    'exit_on_connection_failure' => env('OLLAMA_EXIT_ON_FAILURE', false),
]; 