<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class DailyRecord extends Model
{
    use BelongsToBatch;

    protected $guarded = [];
}
