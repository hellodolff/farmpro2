<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Services\DailyRecordService;
use Illuminate\Http\Request;

class DailyRecordController extends Controller
{
    public function store(Request $r, Batch $batch, DailyRecordService $svc)
    {
        $d = $r->validate([
            'record_date' => 'required|date|before_or_equal:today',
            'deaths' => 'nullable|integer|min:0', 'culls' => 'nullable|integer|min:0',
            'feed_kg' => 'nullable|numeric|min:0', 'feed_item_id' => 'nullable|exists:inventory_items,id', 'feed_phase' => 'nullable|string|max:30',
            'avg_weight_g' => 'nullable|numeric|min:0', 'sample_count' => 'nullable|integer|min:1',
            'temperature' => 'nullable|numeric', 'humidity' => 'nullable|integer|between:0,100',
            'litter_condition' => 'nullable|string|max:30', 'notes' => 'nullable|string',
            'total_eggs' => 'nullable|integer|min:0', 'sellable_eggs' => 'nullable|integer|min:0',
            'cracked_eggs' => 'nullable|integer|min:0', 'dirty_eggs' => 'nullable|integer|min:0',
            'reject_eggs' => 'nullable|integer|min:0', 'egg_weight_kg' => 'nullable|numeric|min:0',
        ]);

        return response()->json($svc->store($batch, $d), 201);
    }
}
