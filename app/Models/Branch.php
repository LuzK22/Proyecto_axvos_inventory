<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',        // Cali, Palmira, etc
        'city',
        'address',
        'active',
    ];
    public function collaborators()
    {
        return $this->hasMany(Collaborator::class);
    }
}
