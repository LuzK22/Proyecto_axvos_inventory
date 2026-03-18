<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentTemplateItem extends Model
{
    protected $fillable = [
        'assignment_template_id',
        'asset_type_id',
        'quantity',
        'goes_to',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Etiqueta legible del destino
     */
    public function getGoesToLabelAttribute(): string
    {
        return match($this->goes_to) {
            'assignee' => 'Colaborador',
            'area'     => 'Área',
            'jefe'     => 'Jefe / Responsable',
            'pool'     => 'Pool compartido',
            default    => $this->goes_to,
        };
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssignmentTemplate::class, 'assignment_template_id');
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }
}
