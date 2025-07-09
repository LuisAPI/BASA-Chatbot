<?php

return [
    /*
     * Laravel Framework Service Providers...
     */
    Illuminate\Filesystem\FilesystemServiceProvider::class,
    Illuminate\Foundation\Providers\FoundationServiceProvider::class,
    Illuminate\Cache\CacheServiceProvider::class,
    Illuminate\Database\DatabaseServiceProvider::class,
    Illuminate\Encryption\EncryptionServiceProvider::class,
    Illuminate\Hashing\HashServiceProvider::class,
    Illuminate\Queue\QueueServiceProvider::class,
    Illuminate\Session\SessionServiceProvider::class,
    Illuminate\View\ViewServiceProvider::class,
    
    /*
     * Package Service Providers...
     */
    Laravel\Reverb\ReverbServiceProvider::class,
    
    /*
     * Application Service Providers...
     */
    App\Providers\AppServiceProvider::class,
    Illuminate\Broadcasting\BroadcastServiceProvider::class,
];
