<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class HealthRecord extends Model
{
    use BelongsToBatch;

    protected $guarded = [];
}
