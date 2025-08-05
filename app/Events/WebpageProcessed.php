<?php
// File: app/Events/WebpageProcessed.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WebpageProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $url;
    public $status;
    public $timestamp;
    public $error;

    public function __construct($url, $status = 'completed', $error = null)
    {
        $this->url = $url;
        $this->status = $status;
        $this->timestamp = now()->toISOString();
        $this->error = $error;
    }

    public function broadcastOn()
    {
        Log::info('WebpageProcessed::broadcastOn called', [
            'url' => $this->url,
            'status' => $this->status,
            'channel' => 'webpages'
        ]);
        return new Channel('webpages');
    }

    public function broadcastAs()
    {
        return 'WebpageProcessed';
    }

    public function broadcastWith()
    {
        return [
            'url' => $this->url,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'error' => $this->error,
            'message' => $this->error
                ? "Webpage {$this->url} failed: {$this->error}"
                : "Webpage {$this->url} has been {$this->status}"
        ];
    }
}
