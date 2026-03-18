<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentType extends Model
{
    protected $fillable = [
        'name',
        'trigger_field',
        'trigger_label',
        'target',
        'requires_return',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'requires_return' => 'boolean',
        'active'          => 'boolean',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(AssignmentTemplate::class)->orderBy('sort_order');
    }

    public function activeTemplates(): HasMany
    {
        return $this->templates()->where('active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }
}
