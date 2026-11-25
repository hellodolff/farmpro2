<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coop extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
