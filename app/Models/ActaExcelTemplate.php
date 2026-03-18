<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaExcelTemplate extends Model
{
    protected $table = 'acta_excel_templates';

    protected $fillable = [
        'name',
        'file_path',
        'acta_type',
        'asset_category',
        'active',
        'assets_start_row',
        'uploaded_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'assets_start_row' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(ActaExcelTemplateField::class, 'acta_excel_template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

