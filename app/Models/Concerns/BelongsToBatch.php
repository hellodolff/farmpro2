<?php

namespace App\Models\Concerns;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBatch
{
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }
}
