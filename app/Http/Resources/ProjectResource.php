<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_code' => $this->project_code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'business_area' => $this->business_area,
            'priority' => $this->priority,
            'frs_status' => $this->frs_status,
            'dev_status' => $this->dev_status,
            'current_progress' => $this->current_progress,
            'blockers' => $this->blockers,
            'is_blocked' => $this->is_blocked,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'planned_release' => $this->planned_release,
            'submission_date' => $this->submission_date?->format('Y-m-d'),
            'target_date' => $this->target_date?->format('Y-m-d'),
            'go_live_date' => $this->go_live_date?->format('Y-m-d'),
            'rag_status' => $this->rag_status,
            'completion_percent' => $this->completion_percent,
            'service_type' => $this->service_type,
            'remarks' => $this->remarks,
            'last_update' => $this->last_update?->format('Y-m-d H:i'),
            'phases' => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'risks' => RiskResource::collection($this->whenLoaded('risks')),
            'risks_count' => $this->whenCounted('risks'),
            'critical_risks_count' => $this->when($this->relationLoaded('risks'), fn() => $this->critical_risks_count),
            'open_risks_count' => $this->when($this->relationLoaded('risks'), fn() => $this->open_risks_count),
            'change_requests' => ChangeRequestResource::collection($this->whenLoaded('changeRequests')),
            'change_requests_count' => $this->whenCounted('changeRequests'),
            'pending_changes_count' => $this->when($this->relationLoaded('changeRequests'), fn() => $this->pending_changes_count),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'current_phase' => $this->when($this->relationLoaded('phases'), fn() => $this->current_phase),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        ];
    }
}
