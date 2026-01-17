<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user' => new UserResource($this->whenLoaded('user')),
            'parent_id' => $this->parent_id,
            'is_reply' => $this->is_reply,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
