<?php

namespace App\Listeners;

use App\Events\RiskCreated;
use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\RiskCreatedNotification;

class LogRiskCreation
{
    /**
     * Handle the event.
     */
    public function handle(RiskCreated $event): void
    {
        $risk = $event->risk;

        // Log the activity
        ActivityLog::create([
            'user_id' => auth()->user()?->id,
            'loggable_type' => 'App\Models\Risk',
            'loggable_id' => $risk->id,
            'action' => 'created',
            'changes' => [
                'risk_code' => $risk->risk_code,
                'impact' => $risk->impact,
                'probability' => $risk->probability,
                'risk_score' => $risk->risk_score,
            ],
        ]);

        // Send notification to admins for high/critical risks
        if (in_array($risk->risk_score, ['High', 'Critical'])) {
            $usersToNotify = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
                ->get();

            foreach ($usersToNotify as $user) {
                if ($user->id !== auth()->user()?->id) {
                    $user->notify(new RiskCreatedNotification($risk));
                }
            }
        }
    }
}
