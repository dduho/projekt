<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'dev_status',
        'rag_status',
        'completion_percent',
        'active_risks_count',
        'pending_changes_count',
        'completed_phases_count',
        'total_phases_count',
        'snapshot_date',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'completion_percent' => 'float',
    ];

    /**
     * Get the project that owns this snapshot.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
