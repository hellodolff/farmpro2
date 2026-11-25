<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getIsLowAttribute(): bool
    {
        return (float) $this->stock <= (float) $this->min_stock;
    }
}
