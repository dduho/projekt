<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'title', 'description', 'completed', 'order'];

    protected $casts = [
        'completed' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the project that owns this checklist item.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
