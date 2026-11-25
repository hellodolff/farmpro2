<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class EggProduction extends Model
{
    use BelongsToBatch;

    protected $guarded = [];
}
