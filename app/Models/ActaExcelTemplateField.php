<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaExcelTemplateField extends Model
{
    protected $table = 'acta_excel_template_fields';

    protected $fillable = [
        'acta_excel_template_id',
        'field_key',
        'field_label',
        'cell_ref',
        'is_iterable',
        'sort_order',
    ];

    protected $casts = [
        'is_iterable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ActaExcelTemplate::class, 'acta_excel_template_id');
    }

    /**
     * True si AXVOS puede resolver este campo automáticamente desde la BD.
     * False = el gestor debe completarlo manualmente desde la web.
     */
    public function getIsAutoAttribute(): bool
    {
        $info = ActaExcelTemplate::KNOWN_FIELDS[$this->field_key] ?? null;
        return $info ? (bool) ($info['auto'] ?? false) : false;
    }
}

