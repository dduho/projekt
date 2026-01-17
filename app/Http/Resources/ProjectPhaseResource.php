<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectPhaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phase' => $this->phase,
            'status' => $this->status,
            'started_at' => $this->started_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'remarks' => $this->remarks,
            'is_completed' => $this->is_completed,
            'is_in_progress' => $this->is_in_progress,
            'is_blocked' => $this->is_blocked,
            'duration_days' => $this->duration_days,
        ];
    }
}
