<?php

namespace App\Notifications;

use App\Models\ChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangeRequestPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ChangeRequest $changeRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Demande de changement: {$this->changeRequest->change_code}")
            ->line("Une nouvelle demande de changement necessite votre approbation.")
            ->line("Projet: {$this->changeRequest->project->name}")
            ->line("Type: {$this->changeRequest->change_type}")
            ->line("Description: {$this->changeRequest->description}")
            ->action('Voir la demande', url("/change-requests/{$this->changeRequest->id}"))
            ->line('Veuillez examiner et approuver ou rejeter cette demande.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'change_request_pending',
            'change_request_id' => $this->changeRequest->id,
            'change_code' => $this->changeRequest->change_code,
            'project_id' => $this->changeRequest->project_id,
            'project_name' => $this->changeRequest->project->name ?? 'N/A',
            'change_type' => $this->changeRequest->change_type,
            'requested_by' => $this->changeRequest->requestedBy->name ?? 'N/A',
            'message' => "Nouvelle demande de changement {$this->changeRequest->change_code} en attente d'approbation",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'change_request_pending',
            'change_request_id' => $this->changeRequest->id,
            'change_code' => $this->changeRequest->change_code,
            'message' => "Nouvelle demande de changement {$this->changeRequest->change_code}",
        ]);
    }
}
