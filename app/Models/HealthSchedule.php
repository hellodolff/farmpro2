<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class HealthSchedule extends Model
{
    use BelongsToBatch;

    protected $guarded = [];
}
