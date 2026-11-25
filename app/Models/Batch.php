<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function movements()
    {
        return $this->hasMany(PopulationMovement::class);
    }

    public function dailyRecords()
    {
        return $this->hasMany(DailyRecord::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    /** Umur dihitung otomatis dari tanggal mulai (PDF bagian 6). */
    public function getAgeDaysAttribute(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date ?? now()->startOfDay());
    }
}
