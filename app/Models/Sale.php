<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use BelongsToBatch;

    protected $guarded = [];
}
