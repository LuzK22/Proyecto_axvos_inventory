<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',   // Nombre sucursal (Cali - Oficina Principal)
        'city',   // Ciudad (Cali)
        'active', // Activa / inactiva
    ];

    /**
     * Una sucursal tiene muchos activos
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
