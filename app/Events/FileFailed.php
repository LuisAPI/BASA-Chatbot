<?php

// File: app/Events/FileFailed.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fileName;
    public $error;

    /**
     * Create a new event instance.
     */
    public function __construct($fileName, $error)
    {
        $this->fileName = $fileName;
        $this->error = $error;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('files');
    }

    public function broadcastAs()
    {
        return 'FileFailed';
    }
}
