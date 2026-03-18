<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workstation extends Model
{
    protected $fillable = [
        'name',
        'branch_id',
        'responsible_id',
        'active',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function responsible()
    {
        return $this->belongsTo(Collaborator::class, 'responsible_id');
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }
}
