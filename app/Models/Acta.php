<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ActaSignature;
use App\Models\ActaExcelTemplate;
use App\Models\ActaFieldValue;
use App\Models\Assignment;
use App\Models\Collaborator;
use App\Models\User;

class Acta extends Model
{
    protected $fillable = [
        'assignment_id',
        'loan_id',              // Préstamo de origen (actas tipo prestamo)
        'collaborator_id',      // FASE 2: destinatario directo (actas consolidadas)
        'area_id',              // Para actas consolidadas de área/pool
        'destination_type',     // FASE 2: collaborator | area | pool
        'acta_number',
        'acta_type',
        'asset_category',
        'asset_scope',
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
        'asset_scope'   => 'array',
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

    public function loan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Loan::class);
    }

    /** FASE 2: destinatario directo del acta consolidada */
    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    /** Área destinataria (actas consolidadas de área/pool) */
    public function area(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Area::class);
    }

    /**
     * FASE 2: todas las asignaciones incluidas en esta acta (N:M).
     * Para actas simples hay 1 entrada; para consolidadas hay N.
     */
    public function assignments(): BelongsToMany
    {
        return $this->belongsToMany(Assignment::class, 'acta_assignments')
                    ->withTimestamps();
    }

    /** True si el acta agrupa activos de más de una asignación */
    public function isConsolidated(): bool
    {
        return $this->destination_type !== null
            || $this->collaborator_id !== null;
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
            'prestamo'               => 'PRE',
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
            'prestamo'               => 'Préstamo',
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

        ActaSignature::createCollaboratorSignature($acta, $recipientName, $recipientEmail, 7);

        // Firma del responsable (usuario autenticado que genera)
        ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

        return $acta;
    }

    /**
     * FASE 3 — Genera un acta de ENTREGA consolidada para un colaborador.
     *
     * Puede incluir:
     * - assignment_asset_ids específicos (acta parcial con activos seleccionados)
     * - Todos los activos activos del colaborador si $aaIds está vacío (acta consolidada)
     *
     * Crea la relación en acta_assignments para cada Assignment involucrado.
     * No duplica si ya existe un acta activa con los mismos assignment_asset_ids.
     *
     * @param  Collaborator       $collaborator
     * @param  string             $category       TI | OTRO | ALL
     * @param  User               $responsibleUser
     * @param  array<int>         $aaIds           IDs de AssignmentAsset a incluir (vacío = todos activos)
     * @return self
     */
    public static function generateConsolidatedForCollaborator(
        Collaborator $collaborator,
        string $category,
        User $responsibleUser,
        array $aaIds = []
    ): self {
        $category = strtoupper($category);
        if (!in_array($category, ['TI', 'OTRO', 'ALL'], true)) {
            $category = 'TI';
        }

        // Resolver los AssignmentAssets a incluir
        $aaQuery = \App\Models\AssignmentAsset::whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
            );

        if (in_array($category, ['TI', 'OTRO'], true)) {
            $aaQuery->whereHas('asset.type', fn($q) => $q->where('category', $category));
        }

        if (!empty($aaIds)) {
            $aaQuery->whereIn('id', $aaIds);
        }

        $assignmentAssets = $aaQuery->with('assignment')->get();
        $resolvedAaIds    = $assignmentAssets->pluck('id')->values()->all();

        // IDs únicos de asignaciones involucradas
        $assignmentIds = $assignmentAssets
            ->pluck('assignment_id')
            ->unique()
            ->values()
            ->all();

        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $collaborator, $category, $responsibleUser, $resolvedAaIds, $assignmentIds
        ) {
            $acta = self::create([
                'assignment_id'   => count($assignmentIds) === 1 ? $assignmentIds[0] : null,
                'collaborator_id' => $collaborator->id,
                'destination_type'=> 'collaborator',
                'acta_number'     => self::generateActaNumber($category, self::TYPE_ENTREGA),
                'acta_type'       => self::TYPE_ENTREGA,
                'asset_category'  => $category,
                'asset_scope'     => ['assignment_asset_ids' => $resolvedAaIds],
                'status'          => self::STATUS_BORRADOR,
                'generated_by'    => $responsibleUser->id,
            ]);

            // Registrar en pivot acta_assignments
            if (!empty($assignmentIds)) {
                $acta->assignments()->sync($assignmentIds);
            }

            // Firmas
            ActaSignature::createCollaboratorSignature(
                $acta,
                $collaborator->full_name,
                $collaborator->email,
                7
            );
            ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

            return $acta;
        });
    }

    /**
     * FASE 3 — Genera acta de DEVOLUCIÓN consolidada desde el expediente.
     *
     * @param  Collaborator  $collaborator
     * @param  string        $category
     * @param  User          $responsibleUser
     * @param  array<int>    $aaIds  IDs de AssignmentAsset YA devueltos en esta operación
     * @return self
     */
    public static function generateReturnForCollaborator(
        Collaborator $collaborator,
        string $category,
        User $responsibleUser,
        array $aaIds
    ): self {
        $category      = strtoupper($category);
        $assignmentIds = \App\Models\AssignmentAsset::whereIn('id', $aaIds)
            ->pluck('assignment_id')
            ->unique()
            ->values()
            ->all();

        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $collaborator, $category, $responsibleUser, $aaIds, $assignmentIds
        ) {
            $acta = self::create([
                'assignment_id'   => count($assignmentIds) === 1 ? $assignmentIds[0] : null,
                'collaborator_id' => $collaborator->id,
                'destination_type'=> 'collaborator',
                'acta_number'     => self::generateActaNumber($category, self::TYPE_DEVOLUCION),
                'acta_type'       => self::TYPE_DEVOLUCION,
                'asset_category'  => $category,
                'asset_scope'     => ['assignment_asset_ids' => array_values($aaIds)],
                'status'          => self::STATUS_BORRADOR,
                'generated_by'    => $responsibleUser->id,
            ]);

            if (!empty($assignmentIds)) {
                $acta->assignments()->sync($assignmentIds);
            }

            ActaSignature::createCollaboratorSignature(
                $acta,
                $collaborator->full_name,
                $collaborator->email,
                7
            );
            ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

            return $acta;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTAS DE ÁREA (OTROS ACTIVOS)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Genera acta de ENTREGA consolidada para un Área (OTROS ACTIVOS).
     *
     * @param  \App\Models\Area  $area
     * @param  string            $category  'OTRO'
     * @param  User              $responsibleUser
     * @param  array<int>        $aaIds  IDs de AssignmentAsset a incluir (vacío = todos activos)
     */
    public static function generateConsolidatedForArea(
        \App\Models\Area $area,
        string $category,
        User $responsibleUser,
        array $aaIds = []
    ): self {
        $category = strtoupper($category);
        if (!in_array($category, ['TI', 'OTRO', 'ALL'], true)) {
            $category = 'OTRO';
        }

        $aaQuery = \App\Models\AssignmentAsset::whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('area_id', $area->id)
                  ->whereIn('destination_type', ['area', 'pool'])
            );

        if (in_array($category, ['TI', 'OTRO'], true)) {
            $aaQuery->whereHas('asset.type', fn($q) => $q->where('category', $category));
        }

        if (!empty($aaIds)) {
            $aaQuery->whereIn('id', $aaIds);
        }

        $assignmentAssets = $aaQuery->with('assignment')->get();
        $resolvedAaIds    = $assignmentAssets->pluck('id')->values()->all();
        $assignmentIds    = $assignmentAssets->pluck('assignment_id')->unique()->values()->all();

        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $area, $category, $responsibleUser, $resolvedAaIds, $assignmentIds
        ) {
            $acta = self::create([
                'assignment_id'   => count($assignmentIds) === 1 ? $assignmentIds[0] : null,
                'area_id'         => $area->id,
                'destination_type'=> 'area',
                'acta_number'     => self::generateActaNumber($category, self::TYPE_ENTREGA),
                'acta_type'       => self::TYPE_ENTREGA,
                'asset_category'  => $category,
                'asset_scope'     => ['assignment_asset_ids' => $resolvedAaIds],
                'status'          => self::STATUS_BORRADOR,
                'generated_by'    => $responsibleUser->id,
            ]);

            if (!empty($assignmentIds)) {
                $acta->assignments()->sync($assignmentIds);
            }

            // Para área: firma del responsable únicamente (sin colaborador individual)
            ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

            return $acta;
        });
    }

    /**
     * Genera acta de DEVOLUCIÓN consolidada para un Área (OTROS ACTIVOS).
     */
    public static function generateReturnForArea(
        \App\Models\Area $area,
        string $category,
        User $responsibleUser,
        array $aaIds
    ): self {
        $category      = strtoupper($category);
        $assignmentIds = \App\Models\AssignmentAsset::whereIn('id', $aaIds)
            ->pluck('assignment_id')
            ->unique()
            ->values()
            ->all();

        return \Illuminate\Support\Facades\DB::transaction(function () use (
            $area, $category, $responsibleUser, $aaIds, $assignmentIds
        ) {
            $acta = self::create([
                'assignment_id'   => count($assignmentIds) === 1 ? $assignmentIds[0] : null,
                'area_id'         => $area->id,
                'destination_type'=> 'area',
                'acta_number'     => self::generateActaNumber($category, self::TYPE_DEVOLUCION),
                'acta_type'       => self::TYPE_DEVOLUCION,
                'asset_category'  => $category,
                'asset_scope'     => ['assignment_asset_ids' => array_values($aaIds)],
                'status'          => self::STATUS_BORRADOR,
                'generated_by'    => $responsibleUser->id,
            ]);

            if (!empty($assignmentIds)) {
                $acta->assignments()->sync($assignmentIds);
            }

            ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

            return $acta;
        });
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
        $scopeIds = collect($this->asset_scope['assignment_asset_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values();

        // ── Actas de PRÉSTAMO: el activo viene del Loan directamente ──────
        if ($this->loan_id && $this->assignment_id === null) {
            $this->loadMissing(['loan.asset.type', 'loan.asset.branch']);
            $asset = $this->loan?->asset;
            if ($asset) {
                return collect([(object)[
                    'asset'       => $asset,
                    'returned_at' => $this->loan->returned_at ?? null,
                    'created_at'  => $this->loan->start_date ?? $this->created_at,
                ]]);
            }
            return collect();
        }

        // FASE 2: actas consolidadas no tienen una sola assignment raíz —
        // resolvemos por asset_scope (siempre presente en actas del expediente)
        if ($this->isConsolidated() && $scopeIds->isNotEmpty()) {
            $query = \App\Models\AssignmentAsset::whereIn('id', $scopeIds->all())
                ->with('asset.type');

            if ($this->acta_type === self::TYPE_DEVOLUCION) {
                $query->whereNotNull('returned_at');
            }

            if (in_array($category, ['TI', 'OTRO'], true)) {
                $query->whereHas('asset.type', fn($q) => $q->where('category', $category));
            }

            return $query->get();
        }

        // Guard: si la acta es consolidada pero sin scope resuelto, colección vacía
        if ($this->isConsolidated() || $this->assignment === null) {
            return collect();
        }

        // Actas simples (una sola asignación): comportamiento original
        $query = $this->assignment->assignmentAssets()
            ->with('asset.type');

        if ($scopeIds->isNotEmpty()) {
            $query->whereIn('id', $scopeIds->all());
        }

        if ($this->acta_type === self::TYPE_DEVOLUCION) {
            $query->whereNotNull('returned_at');
        } else {
            $query->whereNull('returned_at');
        }

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
     * Genera (o reutiliza) un Acta de PRÉSTAMO para un Loan.
     * No duplica si ya existe una acta activa (no anulada) para el mismo préstamo.
     */
    public static function generateForLoan(\App\Models\Loan $loan, \App\Models\User $responsibleUser): self
    {
        // No duplicar
        $existing = self::where('loan_id', $loan->id)
            ->whereNotIn('status', [self::STATUS_ANULADA])
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        $category = strtoupper($loan->asset?->type?->category ?? 'TI');
        if (! in_array($category, ['TI', 'OTRO'], true)) {
            $category = 'TI';
        }

        $acta = self::create([
            'loan_id'        => $loan->id,
            'acta_number'    => self::generateActaNumber($category, 'prestamo'),
            'acta_type'      => 'prestamo',
            'asset_category' => $category,
            'status'         => self::STATUS_BORRADOR,
            'generated_by'   => $responsibleUser->id,
        ]);

        // Firma del receptor (colaborador o destino por nombre)
        $recipientName  = $loan->collaborator?->full_name
            ?? ($loan->destinationBranch?->name ?? 'Destinatario');
        $recipientEmail = $loan->collaborator?->email ?? null;

        ActaSignature::createCollaboratorSignature($acta, $recipientName, $recipientEmail, 7);

        // Firma del responsable
        ActaSignature::createResponsibleSignature($acta, $responsibleUser, 7);

        return $acta;
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
