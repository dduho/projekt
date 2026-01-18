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
        'description_translations',
        'impact',
        'probability',
        'risk_score',
        'mitigation_plan',
        'mitigation_plan_translations',
        'response_plan',
        'response_plan_translations',
        'owner',
        'status',
    ];

    protected $casts = [
        'description_translations' => 'array',
        'mitigation_plan_translations' => 'array',
        'response_plan_translations' => 'array',
    ];

    // =====================
    // BOOT
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
        });
    }

    // =====================
    // ACCESSORS FOR TRANSLATIONS
    // =====================

    public function getDescriptionAttribute($value)
    {
        $locale = app()->getLocale();
        if ($this->description_translations && isset($this->description_translations[$locale])) {
            return $this->description_translations[$locale];
        }
        return $value;
    }

    public function getMitigationPlanAttribute($value)
    {
        $locale = app()->getLocale();
        if ($this->mitigation_plan_translations && isset($this->mitigation_plan_translations[$locale])) {
            return $this->mitigation_plan_translations[$locale];
        }
        return $value;
    }

    public function getResponsePlanAttribute($value)
    {
        $locale = app()->getLocale();
        if ($this->response_plan_translations && isset($this->response_plan_translations[$locale])) {
            return $this->response_plan_translations[$locale];
        }
        return $value;
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

}
