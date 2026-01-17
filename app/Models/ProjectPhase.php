<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase',
        'status',
        'started_at',
        'completed_at',
        'remarks',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'Completed';
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === 'In Progress';
    }

    public function getIsBlockedAttribute(): bool
    {
        return $this->status === 'Blocked';
    }

    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endDate = $this->completed_at ?? now();
        return $this->started_at->diffInDays($endDate);
    }
}
