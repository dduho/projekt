<?php

namespace App\Listeners;

use App\Events\ChangeRequestApproved;
use App\Models\ActivityLog;

class LogChangeRequestApproval
{
    /**
     * Handle the event.
     */
    public function handle(ChangeRequestApproved $event): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'loggable_type' => 'App\Models\ChangeRequest',
            'loggable_id' => $event->changeRequest->id,
            'action' => 'approved',
            'description' => sprintf(
                'Change request "%s" approved for project %s',
                $event->changeRequest->title,
                $event->changeRequest->project->code
            ),
            'changes' => [
                'status' => 'Approved',
                'approved_at' => now()->toDateTimeString(),
                'approved_by' => auth()->user()->name,
            ],
        ]);
    }
}
