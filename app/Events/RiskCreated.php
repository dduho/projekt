<?php

namespace App\Events;

use App\Models\Risk;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Risk $risk;

    /**
     * Create a new event instance.
     */
    public function __construct(Risk $risk)
    {
        $this->risk = $risk;
    }
}
