<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentTemplate extends Model
{
    protected $fillable = [
        'assignment_type_id',
        'name',
        'description',
        'trigger_value',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssignmentType::class, 'assignment_type_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssignmentTemplateItem::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order');
    }

    /**
     * Devuelve los ítems que van al colaborador (assignee)
     */
    public function itemsForAssignee(): HasMany
    {
        return $this->items()->where('goes_to', 'assignee');
    }

    /**
     * Devuelve los ítems que quedan en el área/jefe
     */
    public function itemsForArea(): HasMany
    {
        return $this->items()->whereIn('goes_to', ['area', 'jefe', 'pool']);
    }
}
