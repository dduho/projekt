<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'change_code',
        'project_id',
        'change_type',
        'description',
        'requested_by_id',
        'approved_by_id',
        'status',
        'requested_at',
        'resolved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // =====================
    // BOOT
    // =====================

    protected static function booted(): void
    {
        static::creating(function (ChangeRequest $change) {
            if (empty($change->change_code)) {
                $lastCode = static::where('change_code', 'like', 'CHG-%')
                    ->orderByRaw("CAST(SUBSTRING(change_code, 5) AS UNSIGNED) DESC")
                    ->value('change_code');

                $nextNumber = $lastCode ? (int) substr($lastCode, 4) + 1 : 1;
                $change->change_code = 'CHG-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            $change->requested_at = $change->requested_at ?? now();
        });
    }

    // =====================
    // RELATIONS
    // =====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
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

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['Pending', 'Under Review']);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'Approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'Rejected');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('change_type', $type);
    }

    // =====================
    // ACCESSORS
    // =====================

    public function getIsPendingAttribute(): bool
    {
        return in_array($this->status, ['Pending', 'Under Review']);
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'Approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'Rejected';
    }

    public function getDaysPendingAttribute(): ?int
    {
        if ($this->resolved_at) {
            return null;
        }
        return $this->requested_at->diffInDays(now());
    }

    // =====================
    // METHODS
    // =====================

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_by_id' => $approver->id,
            'resolved_at' => now(),
        ]);
    }

    public function reject(User $approver): void
    {
        $this->update([
            'status' => 'Rejected',
            'approved_by_id' => $approver->id,
            'resolved_at' => now(),
        ]);
    }
}
