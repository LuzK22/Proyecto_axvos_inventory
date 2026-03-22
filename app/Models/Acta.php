<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ActaSignature;
use App\Models\ActaExcelTemplate;
use App\Models\ActaFieldValue;
use App\Models\Assignment;
use App\Models\User;

class Acta extends Model
{
    protected $fillable = [
        'assignment_id',
        'acta_number',
        'acta_type',
        'asset_category',
        'status',
        'pdf_path',
        'xlsx_draft_path',
        'xlsx_final_path',
        'generated_by',
        'notes',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'completed_at'  => 'datetime',
    ];

    // ─── Constantes de estado ──────────────────────────────────
    const STATUS_BORRADOR             = 'borrador';
    const STATUS_ENVIADA              = 'enviada';
    const STATUS_FIRMADA_COLABORADOR  = 'firmada_colaborador';
    const STATUS_FIRMADA_RESPONSABLE  = 'firmada_responsable';
    const STATUS_COMPLETADA           = 'completada';
    const STATUS_ANULADA              = 'anulada';

    // ─── Relaciones ────────────────────────────────────────────

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ActaSignature::class);
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(ActaFieldValue::class);
    }

    public function collaboratorSignature()
    {
        return $this->hasOne(ActaSignature::class)
                    ->where('signer_role', 'collaborator');
    }

    public function responsibleSignature()
    {
        return $this->hasOne(ActaSignature::class)
                    ->where('signer_role', 'responsible');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_COMPLETADA;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_BORRADOR,
            self::STATUS_ENVIADA,
            self::STATUS_FIRMADA_COLABORADOR,
            self::STATUS_FIRMADA_RESPONSABLE,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_BORRADOR            => 'Borrador',
            self::STATUS_ENVIADA             => 'Enviada para firma',
            self::STATUS_FIRMADA_COLABORADOR => 'Firmada por colaborador',
            self::STATUS_FIRMADA_RESPONSABLE => 'Firmada por responsable',
            self::STATUS_COMPLETADA          => 'Completada',
            self::STATUS_ANULADA             => 'Anulada',
            default                          => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_BORRADOR            => 'secondary',
            self::STATUS_ENVIADA             => 'info',
            self::STATUS_FIRMADA_COLABORADOR,
            self::STATUS_FIRMADA_RESPONSABLE => 'warning',
            self::STATUS_COMPLETADA          => 'success',
            self::STATUS_ANULADA             => 'danger',
            default                          => 'secondary',
        };
    }

    // ─── Tipos de acta ────────────────────────────────────────
    const TYPE_ENTREGA       = 'entrega';
    const TYPE_DEVOLUCION    = 'devolucion';
    const TYPE_BAJA          = 'baja';
    const TYPE_DONACION      = 'donacion';
    const TYPE_VENTA         = 'venta';
    const TYPE_ACTUALIZACION = 'actualizacion';

    public static function typePrefix(string $type): string
    {
        return match($type) {
            self::TYPE_ENTREGA       => 'ENT',
            self::TYPE_DEVOLUCION    => 'DEV',
            self::TYPE_BAJA          => 'BAJ',
            self::TYPE_DONACION      => 'DON',
            self::TYPE_VENTA         => 'VEN',
            self::TYPE_ACTUALIZACION => 'ACT',
            default                  => 'ACT',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->acta_type) {
            self::TYPE_ENTREGA       => 'Entrega',
            self::TYPE_DEVOLUCION    => 'Devolución',
            self::TYPE_BAJA          => 'Baja',
            self::TYPE_DONACION      => 'Donación',
            self::TYPE_VENTA         => 'Venta',
            self::TYPE_ACTUALIZACION => 'Actualización',
            default                  => ucfirst($this->acta_type),
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->acta_type) {
            self::TYPE_ENTREGA       => 'primary',
            self::TYPE_DEVOLUCION    => 'warning',
            self::TYPE_BAJA          => 'danger',
            self::TYPE_DONACION      => 'info',
            self::TYPE_VENTA         => 'dark',
            self::TYPE_ACTUALIZACION => 'secondary',
            default                  => 'secondary',
        };
    }

    /**
     * Genera el número de acta secuencial.
     * Formato: ACT-TI-ENT-2026-0012  /  ACT-OT-ENT-2026-0012
     */
    public static function generateActaNumber(string $category = 'TI', string $type = 'entrega'): string
    {
        $year      = now()->year;
        $catPrefix = match ($category) {
            'OTRO' => 'OT',
            'ALL'  => 'MX',
            default => 'TI',
        };
        $prefix  = self::typePrefix($type);
        $pattern = "ACT-{$catPrefix}-{$prefix}-{$year}-%";
        $lastSeq = self::whereYear('created_at', $year)
                       ->where('acta_number', 'like', $pattern)
                       ->lockForUpdate()
                       ->max(\Illuminate\Support\Facades\DB::raw(
                           'CAST(SUBSTRING_INDEX(acta_number, \'-\', -1) AS UNSIGNED)'
                       ));
        $seq = str_pad(($lastSeq ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        return "ACT-{$catPrefix}-{$prefix}-{$year}-{$seq}";
    }

    /**
     * Genera (o reutiliza) un Acta de ENTREGA para una asignación y categoría (TI/OTRO/ALL).
     *
     * - No genera si la asignación no tiene activos de esa categoría (no devueltos).
     * - No duplica: si ya existe un acta activa (no anulada) para assignment+category, la retorna.
     * - Mantiene la lógica de firmas: collaborator + responsible.
     * - Si la asignación es a un área (sin colaborador), la firma "collaborator" representa al área.
     */
    public static function generateDeliveryForAssignment(Assignment $assignment, string $category, User $responsibleUser): ?self
    {
        $category = strtoupper($category);
        if (!in_array($category, ['TI', 'OTRO', 'ALL'], true)) {
            $category = 'TI';
        }

        $assetsQuery = $assignment->assignmentAssets()
            ->whereNull('returned_at')
            ->whereHas('asset.type');

        // Verificar que la asignación tiene activos de esa categoría (aún no devueltos)
        $hasAssets = $category === 'ALL'
      ? (clone $assetsQuery)
            ->with('asset.type')
            ->get()
            ->pluck('asset.type.category')
            ->filter()
            ->unique()
            ->count() >= 2
            : (clone $assetsQuery)
                ->whereHas('asset.type', fn($q) => $q->where('category', $category))
                ->exists();

        if (!$hasAssets) {
            return null;
        }

        // No duplicar: si ya existe un acta activa para esta asignación+categoría, reutilizarla
        $existing = $assignment->actas()
            ->where('asset_category', $category)
            ->whereNotIn('status', [self::STATUS_ANULADA])
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $acta = self::create([
            'assignment_id'  => $assignment->id,
            'acta_number'    => self::generateActaNumber($category, self::TYPE_ENTREGA),
            'acta_type'      => self::TYPE_ENTREGA,
            'asset_category' => $category,
            'status'         => self::STATUS_BORRADOR,
            'generated_by'   => $responsibleUser->id,
        ]);

        $assignment->loadMissing(['collaborator', 'area']);

        // Firma del "receptor": colaborador o área
        $recipientName  = $assignment->collaborator?->full_name
            ?? ($assignment->area ? ('Área: ' . $assignment->area->name) : '—');
        $recipientEmail = $assignment->collaborator?->email; // puede ser null si es área

        ActaSignature::create([
            'acta_id'          => $acta->id,
            'signer_role'      => 'collaborator',
            'signer_name'      => $recipientName,
            'signer_email'     => $recipientEmail,
            'token'            => ActaSignature::generateToken(),
            'token_expires_at' => now()->addDays(7),
        ]);

        // Firma del responsable (usuario autenticado que genera)
        ActaSignature::create([
            'acta_id'          => $acta->id,
            'signer_role'      => 'responsible',
            'signer_name'      => $responsibleUser->name,
            'signer_email'     => $responsibleUser->email,
            'signer_user_id'   => $responsibleUser->id,
            'token'            => ActaSignature::generateToken(),
            'token_expires_at' => now()->addDays(7),
        ]);

        return $acta;
    }

    /**
     * Obtiene la plantilla Excel activa para esta acta (por tipo + categoría).
     * Fallback: plantilla con asset_category = 'ALL' para el mismo acta_type.
     */
    public function activeExcelTemplate(): ?ActaExcelTemplate
    {
        $type = $this->acta_type ?? self::TYPE_ENTREGA;
        $cat  = strtoupper($this->asset_category ?? 'TI');

        $template = ActaExcelTemplate::where('active', true)
            ->where('acta_type', $type)
            ->where('asset_category', $cat)
            ->latest()
            ->first();

        if ($template) {
            return $template;
        }

        return ActaExcelTemplate::where('active', true)
            ->where('acta_type', $type)
            ->where('asset_category', 'ALL')
            ->latest()
            ->first();
    }

    public function getAssetCategoryLabelAttribute(): string
    {
        return match (strtoupper($this->asset_category ?? 'TI')) {
            'TI' => 'TI',
            'OTRO' => 'OTRO',
            'ALL' => 'MIXTA',
            default => strtoupper((string) $this->asset_category),
        };
    }

    public function scopedAssignmentAssets()
    {
        $category = strtoupper($this->asset_category ?? 'TI');

        $query = $this->assignment->assignmentAssets()
            ->whereNull('returned_at')
            ->with('asset.type');

        if (in_array($category, ['TI', 'OTRO'], true)) {
            $query->whereHas('asset.type', fn ($q) => $q->where('category', $category));
        }

        return $query->get();
    }

    public function hasTechAssets(): bool
    {
        return $this->scopedAssignmentAssets()
            ->contains(fn ($aa) => strtoupper($aa->asset?->type?->category ?? '') === 'TI');
    }

    /**
     * Actualiza el estado del acta según las firmas presentes
     */
    public function refreshStatus(): void
    {
        $collSigned = $this->collaboratorSignature?->signed_at !== null;
        $respSigned = $this->responsibleSignature?->signed_at  !== null;

        if ($collSigned && $respSigned) {
            $this->update([
                'status'       => self::STATUS_COMPLETADA,
                'completed_at' => now(),
            ]);
        } elseif ($collSigned) {
            $this->update(['status' => self::STATUS_FIRMADA_COLABORADOR]);
        } elseif ($respSigned) {
            $this->update(['status' => self::STATUS_FIRMADA_RESPONSABLE]);
        }
    }
}
