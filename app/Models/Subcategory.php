<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['name', 'category'];

    public function assetTypes()
    {
        return AssetType::where('subcategory', $this->name)
            ->where('category', $this->category);
    }
}
