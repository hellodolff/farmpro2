<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CapacityExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchRequest;
use App\Models\Batch;
use App\Services\BatchService;
use App\Services\PerformanceService;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $r)
    {
        return Batch::with(['commodity:id,code,name', 'coop:id,code,name'])
            ->when($r->status, fn ($q, $s) => $q->where('status', $s))
            ->latest('start_date')->get()
            ->map(fn ($b) => $b->toArray() + ['age_days' => $b->age_days]);
    }

    public function show(Batch $batch, PerformanceService $perf)
    {
        $batch->load(['commodity:id,code,name', 'coop:id,code,name']);

        return [
            'batch' => $batch->toArray() + ['age_days' => $batch->age_days],
            'performance' => $perf->forBatch($batch),
            'recent_records' => $batch->dailyRecords()->latest('record_date')->take(7)->get(),
        ];
    }

    public function store(BatchRequest $r, BatchService $svc)
    {
        if (! in_array($r->user()->role, ['admin', 'operator'], true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk tindakan ini.');
        }

        try {
            return response()->json($svc->create($r->validated()), 201);
        } catch (CapacityExceededException $e) {
            return response()->json(['message' => $e->getMessage(), 'needs_confirmation' => true], 422);
        }
    }
}
