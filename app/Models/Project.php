<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_code',
        'name',
        'description',
        'category_id',
        'business_area',
        'priority',
        'frs_status',
        'dev_status',
        'current_progress',
        'blockers',
        'owner_id',
        'planned_release',
        'submission_date',
        'target_date',
        'go_live_date',
        'rag_status',
        'completion_percent',
        'service_type',
        'remarks',
        'last_update',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'target_date' => 'date',
        'go_live_date' => 'date',
        'last_update' => 'datetime',
        'completion_percent' => 'integer',
    ];

    // =====================
    // RELATIONS
    // =====================

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderByRaw("
            CASE phase
                WHEN 'FRS' THEN 1
                WHEN 'Development' THEN 2
                WHEN 'Testing' THEN 3
                WHEN 'UAT' THEN 4
                WHEN 'Deployment' THEN 5
            END
        ");
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeByRagStatus(Builder $query, string $status): Builder
    {
        return $query->where('rag_status', $status);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDevStatus(Builder $query, string $status): Builder
    {
        return $query->where('dev_status', $status);
    }

    public function scopeDeployed(Builder $query): Builder
    {
        return $query->where('dev_status', 'Deployed');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('dev_status', 'In Development');
    }

    public function scopeAwaitingAction(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('dev_status', ['On Hold', 'Not Started'])
              ->orWhereNotNull('blockers');
        });
    }

    public function scopeWithFrsSignoff(Builder $query): Builder
    {
        return $query->where('frs_status', 'Signoff');
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('rag_status', 'Red')
            ->orWhereHas('risks', fn($q) => $q->critical()->open());
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('project_code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getIsBlockedAttribute(): bool
    {
        return !empty($this->blockers);
    }

    public function getCriticalRisksCountAttribute(): int
    {
        return $this->risks()
            ->where('risk_score', 'Critical')
            ->where('status', 'Open')
            ->count();
    }

    public function getOpenRisksCountAttribute(): int
    {
        return $this->risks()
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();
    }

    public function getPendingChangesCountAttribute(): int
    {
        return $this->changeRequests()
            ->whereIn('status', ['Pending', 'Under Review'])
            ->count();
    }

    public function getCurrentPhaseAttribute(): ?string
    {
        $phase = $this->phases()
            ->where('status', 'In Progress')
            ->first();

        return $phase?->phase;
    }

    // =====================
    // BOOT
    // =====================

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->project_code)) {
                $lastCode = static::withTrashed()
                    ->where('project_code', 'like', 'PRISM-%')
                    ->orderByRaw("CAST(SUBSTRING(project_code, 7) AS UNSIGNED) DESC")
                    ->value('project_code');

                $nextNumber = $lastCode
                    ? (int) substr($lastCode, 6) + 1
                    : 1;

                $project->project_code = 'PRISM-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });

        static::updating(function (Project $project) {
            $project->last_update = now();
        });

        static::created(function (Project $project) {
            $phases = ['FRS', 'Development', 'Testing', 'UAT', 'Deployment'];
            foreach ($phases as $phase) {
                $project->phases()->create([
                    'phase' => $phase,
                    'status' => 'Pending',
                ]);
            }
        });
    }
}
