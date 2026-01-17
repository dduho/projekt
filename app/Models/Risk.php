<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Risk extends Model
{
    use HasFactory;

    protected $fillable = [
        'risk_code',
        'project_id',
        'type',
        'description',
        'impact',
        'probability',
        'risk_score',
        'mitigation_plan',
        'owner_id',
        'status',
        'identified_at',
        'resolved_at',
    ];

    protected $casts = [
        'identified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // =====================
    // BOOT
    // =====================

    protected static function booted(): void
    {
        static::saving(function (Risk $risk) {
            $risk->risk_score = $risk->calculateRiskScore();
        });

        static::creating(function (Risk $risk) {
            if (empty($risk->risk_code)) {
                $lastCode = static::where('risk_code', 'like', 'RISK-%')
                    ->orderByRaw("CAST(SUBSTRING(risk_code, 6) AS UNSIGNED) DESC")
                    ->value('risk_code');

                $nextNumber = $lastCode ? (int) substr($lastCode, 5) + 1 : 1;
                $risk->risk_code = 'RISK-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $risk->identified_at = $risk->identified_at ?? now();
        });
    }

    // =====================
    // METHODS
    // =====================

    public function calculateRiskScore(): string
    {
        $matrix = [
            'Low' => ['Low' => 'Low', 'Medium' => 'Low', 'High' => 'Medium'],
            'Medium' => ['Low' => 'Medium', 'Medium' => 'Medium', 'High' => 'High'],
            'High' => ['Low' => 'Medium', 'Medium' => 'High', 'High' => 'Critical'],
            'Critical' => ['Low' => 'High', 'Medium' => 'Critical', 'High' => 'Critical'],
        ];

        return $matrix[$this->impact][$this->probability] ?? 'Medium';
    }

    // =====================
    // RELATIONS
    // =====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('risk_score', 'Critical');
    }

    public function scopeHigh(Builder $query): Builder
    {
        return $query->whereIn('risk_score', ['High', 'Critical']);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'Open');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['Open', 'In Progress']);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getIsCriticalAttribute(): bool
    {
        return $this->risk_score === 'Critical';
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['Open', 'In Progress']);
    }

    public function getDaysOpenAttribute(): int
    {
        if ($this->resolved_at) {
            return $this->identified_at->diffInDays($this->resolved_at);
        }
        return $this->identified_at->diffInDays(now());
    }
}
