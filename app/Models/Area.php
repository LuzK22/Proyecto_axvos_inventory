<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = ['name', 'description', 'branch_id', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
