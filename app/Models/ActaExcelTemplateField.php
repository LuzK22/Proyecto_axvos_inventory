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
}

