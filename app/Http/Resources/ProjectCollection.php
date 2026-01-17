<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn($project) => [
                'id' => $project->id,
                'project_code' => $project->project_code,
                'name' => $project->name,
                'category' => $project->category ? [
                    'id' => $project->category->id,
                    'name' => $project->category->name,
                    'color' => $project->category->color,
                ] : null,
                'priority' => $project->priority,
                'rag_status' => $project->rag_status,
                'dev_status' => $project->dev_status,
                'frs_status' => $project->frs_status,
                'completion_percent' => $project->completion_percent,
                'owner' => $project->owner ? [
                    'id' => $project->owner->id,
                    'name' => $project->owner->name,
                    'avatar_url' => $project->owner->avatar_url,
                    'initials' => $project->owner->initials,
                ] : null,
                'target_date' => $project->target_date?->format('Y-m-d'),
                'is_blocked' => $project->is_blocked,
                'risks_count' => $project->risks_count ?? 0,
                'change_requests_count' => $project->change_requests_count ?? 0,
            ]),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}
