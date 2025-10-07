<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentStageMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $documentType;
    public $stage;
    public $count;

    /**
     * Create a new event instance.
     */
    public function __construct($documentType, $stage, $count)
    {
        $this->documentType = $documentType;
        $this->stage = $stage;
        $this->count = $count;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // Public channel for all clients
        return new Channel('special-permit-' . $this->stage);
    }

    /**
     * Optional: event name to listen for in JS
     */
    public function broadcastAs()
    {
        return 'document.stage_moved';
    }
}
