<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'risk_code' => $this->risk_code,
            'project' => $this->when($this->relationLoaded('project'), fn() => [
                'id' => $this->project->id,
                'project_code' => $this->project->project_code,
                'name' => $this->project->name,
            ]),
            'type' => $this->type,
            'description' => $this->description,
            'impact' => $this->impact,
            'probability' => $this->probability,
            'risk_score' => $this->risk_score,
            'mitigation_plan' => $this->mitigation_plan,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'status' => $this->status,
            'identified_at' => $this->identified_at?->format('Y-m-d H:i'),
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i'),
            'is_critical' => $this->is_critical,
            'is_open' => $this->is_open,
            'days_open' => $this->days_open,
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
