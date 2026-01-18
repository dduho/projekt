<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_translations',
        'slug',
        'color',
        'description',
    ];

    protected $casts = [
        'name_translations' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function getNameAttribute($value)
    {
        $locale = app()->getLocale();
        if ($this->name_translations && isset($this->name_translations[$locale])) {
            return $this->name_translations[$locale];
        }
        return $value;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getProjectsCountAttribute(): int
    {
        return $this->projects()->count();
    }
}
