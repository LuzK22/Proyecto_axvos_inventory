<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['name', 'color', 'active'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function getBadgeClassAttribute(): string
    {
        return match($this->color) {
            'success'   => 'badge-success',
            'primary'   => 'badge-primary',
            'secondary' => 'badge-secondary',
            'warning'   => 'badge-warning text-dark',
            'info'      => 'badge-info',
            'danger'    => 'badge-danger',
            'light'     => 'badge-light text-dark',
            default     => 'badge-secondary',
        };
    }
}
