<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loggable_type',
        'loggable_id',
        'action',
        'changes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFormattedActionAttribute(): string
    {
        $actions = [
            'created' => 'a créé',
            'updated' => 'a modifié',
            'deleted' => 'a supprimé',
            'status_changed' => 'a changé le statut de',
            'phase_updated' => 'a mis à jour la phase de',
            'comment_added' => 'a commenté sur',
            'approved' => 'a approuvé',
            'rejected' => 'a rejeté',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    public function getSubjectNameAttribute(): string
    {
        if ($this->loggable) {
            return $this->loggable->name ?? $this->loggable->project_code ?? $this->loggable->risk_code ?? $this->loggable->change_code ?? 'N/A';
        }
        return 'N/A';
    }

    public function getSubjectTypeAttribute(): string
    {
        $types = [
            Project::class => 'Projet',
            Risk::class => 'Risque',
            ChangeRequest::class => 'Changement',
            User::class => 'Utilisateur',
        ];

        return $types[$this->loggable_type] ?? class_basename($this->loggable_type);
    }
}
