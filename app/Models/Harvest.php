<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    use BelongsToBatch;

    protected $guarded = [];

    protected $casts = ['harvest_date' => 'date'];

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }
}
