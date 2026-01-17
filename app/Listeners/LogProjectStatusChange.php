<?php

namespace App\Listeners;

use App\Events\ProjectStatusChanged;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ProjectStatusChangedNotification;

class LogProjectStatusChange
{
    /**
     * Handle the event.
     */
    public function handle(ProjectStatusChanged $event): void
    {
        // Log the activity
        ActivityLog::create([
            'user_id' => auth()->id(),
            'loggable_type' => 'App\Models\Project',
            'loggable_id' => $event->project->id,
            'action' => 'status_changed',
            'changes' => [
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ],
        ]);

        // Send notification to project owner and admins
        $usersToNotify = User::where('id', $event->project->owner_id)
            ->orWhereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->get();

        foreach ($usersToNotify as $user) {
            // Don't notify the user who made the change
            if ($user->id !== auth()->id()) {
                $user->notify(new ProjectStatusChangedNotification(
                    $event->project,
                    $event->oldStatus,
                    $event->newStatus
                ));
            }
        }
    }
}
