<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FileProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fileName;
    public $status;
    public $timestamp;

    public function __construct($fileName, $status = 'completed')
    {
        $this->fileName = $fileName;
        $this->status = $status;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn()
    {
        Log::info('FileProcessed::broadcastOn called', [
            'fileName' => $this->fileName,
            'status' => $this->status,
            'channel' => 'files'
        ]);
        return new Channel('files');
    }

    public function broadcastAs()
    {
        return 'FileProcessed';
    }

    public function broadcastWith()
    {
        return [
            'fileName' => $this->fileName,
            'status' => $this->status,
            'timestamp' => $this->timestamp,
            'message' => "File {$this->fileName} has been {$this->status}"
        ];
    }
}
