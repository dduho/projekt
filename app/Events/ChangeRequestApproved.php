<?php

namespace App\Events;

use App\Models\ChangeRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChangeRequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChangeRequest $changeRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(ChangeRequest $changeRequest)
    {
        $this->changeRequest = $changeRequest;
    }
}
