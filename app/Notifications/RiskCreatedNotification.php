<?php

namespace App\Notifications;

use App\Models\Risk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RiskCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Risk $risk
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database', 'broadcast'];

        // Send email only for critical risks
        if ($this->risk->risk_score === 'Critical') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Risque Critique: {$this->risk->risk_code}")
            ->line("Un nouveau risque critique a ete identifie.")
            ->line("Projet: {$this->risk->project->name}")
            ->line("Description: {$this->risk->description}")
            ->line("Impact: {$this->risk->impact} | Probabilite: {$this->risk->probability}")
            ->action('Voir le risque', url("/risks"))
            ->line('Veuillez prendre les mesures necessaires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'risk_created',
            'risk_id' => $this->risk->id,
            'risk_code' => $this->risk->risk_code,
            'project_id' => $this->risk->project_id,
            'project_name' => $this->risk->project->name ?? 'N/A',
            'risk_score' => $this->risk->risk_score,
            'message' => "Nouveau risque {$this->risk->risk_code} ({$this->risk->risk_score}) sur {$this->risk->project->project_code}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'risk_created',
            'risk_id' => $this->risk->id,
            'risk_code' => $this->risk->risk_code,
            'risk_score' => $this->risk->risk_score,
            'message' => "Nouveau risque {$this->risk->risk_code} ({$this->risk->risk_score})",
        ]);
    }
}
