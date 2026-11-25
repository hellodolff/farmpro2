<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBatch;
use Illuminate\Database\Eloquent\Model;

class PopulationMovement extends Model
{
    use BelongsToBatch;

    protected $table = 'batch_population_movements';

    protected $guarded = [];
}
