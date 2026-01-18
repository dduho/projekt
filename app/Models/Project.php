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
        'target_date',
        'submission_date',
        'rag_status',
        'completion_percent',
    ];

    protected $casts = [
        'target_date' => 'date',
        'submission_date' => 'date',
        'completion_percent' => 'integer',
    ];

    protected $appends = [
        'calculated_completion_percent',
        'calculated_rag_status',
        'health_status',
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
            $q->where('dev_status', 'Not Started')
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

    /**
     * Calcule le pourcentage de completion basé sur les phases
     * Deployment ✓ → 100%, UAT ✓ → 85%, Testing ✓ → 60%, Development ✓ → 35%, FRS ✓ → 20%, sinon 10%
     */
    public function getCalculatedCompletionPercentAttribute(): int
    {
        $phases = $this->phases;
        
        if ($phases->isEmpty()) {
            return 10;
        }

        // Vérifier chaque phase dans l'ordre inverse (la plus avancée d'abord)
        $phaseValues = [
            'Deployment' => 100,
            'UAT' => 85,
            'Testing' => 60,
            'Development' => 35,
            'FRS' => 20,
        ];

        foreach ($phaseValues as $phaseName => $value) {
            $phase = $phases->firstWhere('phase', $phaseName);
            if ($phase && $phase->status === 'Completed') {
                return $value;
            }
        }

        // Si aucune phase n'est complétée, vérifier si une est en cours
        foreach ($phaseValues as $phaseName => $value) {
            $phase = $phases->firstWhere('phase', $phaseName);
            if ($phase && $phase->status === 'In Progress') {
                // Retourner la valeur de la phase précédente + un peu
                return max(10, $value - 10);
            }
        }

        return 10;
    }

    /**
     * Calcule le RAG Status basé sur le dev_status
     * Deploy/Configuration Done → Green, Waiting/Not Start → Red, sinon Amber
     */
    public function getCalculatedRagStatusAttribute(): string
    {
        $devStatus = strtolower($this->dev_status ?? '');
        $frsStatus = strtolower($this->frs_status ?? '');

        // Green si Deploy ou Configuration Done
        if (str_contains($devStatus, 'deploy') || str_contains($devStatus, 'configuration done')) {
            return 'Green';
        }

        // Red si Waiting ou Not Start
        if (str_contains($devStatus, 'waiting') || str_contains($frsStatus, 'not start')) {
            return 'Red';
        }

        // Sinon Amber
        return 'Amber';
    }

    /**
     * Calcule le Health basé sur le completion %
     * >= 80% → Green, >= 50% → Amber, sinon Red
     */
    public function getHealthStatusAttribute(): string
    {
        $completion = $this->calculated_completion_percent;

        if ($completion >= 80) {
            return 'Green';
        } elseif ($completion >= 50) {
            return 'Amber';
        }

        return 'Red';
    }

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
