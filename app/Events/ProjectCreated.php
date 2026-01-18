<?php

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Project $project,
        public User $user
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('projects'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->project->id,
            'project_code' => $this->project->project_code,
            'name' => $this->project->name,
            'rag_status' => $this->project->rag_status,
            'created_by' => $this->user->name,
            'created_at' => $this->project->created_at->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.created';
    }
}
