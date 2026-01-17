<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'change_code' => $this->change_code,
            'project' => $this->when($this->relationLoaded('project'), fn() => [
                'id' => $this->project->id,
                'project_code' => $this->project->project_code,
                'name' => $this->project->name,
            ]),
            'change_type' => $this->change_type,
            'description' => $this->description,
            'impact_analysis' => $this->impact_analysis,
            'requested_by' => new UserResource($this->whenLoaded('requestedBy')),
            'approved_by' => new UserResource($this->whenLoaded('approvedBy')),
            'status' => $this->status,
            'requested_at' => $this->requested_at?->format('Y-m-d H:i'),
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i'),
            'is_pending' => $this->is_pending,
            'is_approved' => $this->is_approved,
            'is_rejected' => $this->is_rejected,
            'days_pending' => $this->days_pending,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
