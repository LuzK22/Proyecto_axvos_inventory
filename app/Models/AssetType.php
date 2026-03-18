<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetType extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'code',         // "POR" (corto)
        'category', // "TI", "MUEBLE"
        'prefix',    // "TI-POR" ← SE GUARDA AQUÍ 
        'active',
        'created_by',
    ];



    /**
     * Boot - Generar automáticamente el prefix si no existe
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($assetType) {
            if (empty($assetType->prefix)) {
                $assetType->prefix = $assetType->category . '-' . $assetType->code;
            }
        });
    }

    /**
     * Relacion: un tipo tiene muchos activos
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_type_id');
    }

     // Usuario que creo el tipo
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Solo tipos activos (para selects en forms)
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }


    /**
     * Generar código de activo automático
     * Ejemplo: TI-POR-00001, OTRO-SIL-00001
     */
    public function generateAssetCode($sequence)
    {
        // Usar generatePrefix() si no hay prefix
        if (empty($this->prefix)) {
            $this->prefix = $this->category . '-' . $this->code;
            $this->save();
        }
        
        return sprintf(
            "%s-%05d", 
            $this->prefix,  // Ej: "TI-POR"
            $sequence       // Ej: 1 → "00001"
        );
    }
}
