<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    protected $fillable = [
        'asset_id',
        'from_status',
        'to_status',
        'event_type',
        'assignment_id',
        'acta_id',
        'to_branch_id',
        'user_id',
        'collaborator_id',
        'notes',
    ];

    // ─── Etiquetas de evento ───────────────────────────────────
    const TYPES = [
        'asignacion'     => 'Asignación',
        'devolucion'     => 'Devolución',
        'baja'           => 'Baja',
        'mantenimiento'  => 'Envío a Mantenimiento',
        'garantia'       => 'Envío a Garantía',
        'traslado'       => 'Traslado de Sede',
        'donacion'       => 'Donación',
        'venta'          => 'Venta',
        'actualizacion'  => 'Actualización',
        'disponible'     => 'Disponible',
        'creacion'       => 'Registro de Activo',
        'prestamo'       => 'Préstamo',
    ];

    public function getEventLabelAttribute(): string
    {
        return self::TYPES[$this->event_type] ?? ucfirst($this->event_type);
    }

    public function getEventColorAttribute(): string
    {
        return match($this->event_type) {
            'asignacion'    => 'primary',
            'devolucion'    => 'warning',
            'baja'          => 'danger',
            'mantenimiento' => 'warning',
            'garantia'      => 'info',
            'traslado'      => 'info',
            'donacion'      => 'dark',
            'venta'         => 'dark',
            'disponible'    => 'success',
            'creacion'      => 'success',
            'prestamo'      => 'primary',
            default         => 'secondary',
        };
    }

    // ─── Relaciones ────────────────────────────────────────────

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function acta()
    {
        return $this->belongsTo(Acta::class);
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    // ─── Inmutabilidad: los eventos del historial no se pueden modificar ──

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('AssetEvent records are immutable and cannot be updated.');
        }
        return parent::save($options);
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('AssetEvent records are immutable and cannot be updated.');
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException('AssetEvent records are immutable and cannot be deleted.');
    }

    // ─── Helper estático para registrar evento ─────────────────

    public static function log(
        Asset  $asset,
        string $eventType,
        string $toStatus,
        array  $extra = []
    ): self {
        return self::create(array_merge([
            'asset_id'    => $asset->id,
            'from_status' => $asset->status?->name,
            'to_status'   => $toStatus,
            'event_type'  => $eventType,
            'user_id'     => auth()->id(),
        ], $extra));
    }
}
