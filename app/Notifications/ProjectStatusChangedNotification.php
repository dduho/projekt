<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Projet {$this->project->project_code} - Statut modifie")
            ->line("Le statut du projet {$this->project->name} a ete modifie.")
            ->line("Ancien statut: {$this->oldStatus}")
            ->line("Nouveau statut: {$this->newStatus}")
            ->action('Voir le projet', url("/projects/{$this->project->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_status_changed',
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
            'project_name' => $this->project->name,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Le projet {$this->project->project_code} est passe de {$this->oldStatus} a {$this->newStatus}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'project_status_changed',
            'project_id' => $this->project->id,
            'project_code' => $this->project->project_code,
            'project_name' => $this->project->name,
            'message' => "Le projet {$this->project->project_code} est passe de {$this->oldStatus} a {$this->newStatus}",
        ]);
    }
}
