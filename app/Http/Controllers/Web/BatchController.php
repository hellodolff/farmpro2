<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\CapacityExceededException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Http\Requests\BatchRequest;
use App\Http\Requests\MovementRequest;
use App\Models\Batch;
use App\Models\Commodity;
use App\Models\Coop;
use App\Models\Partner;
use App\Services\BatchService;
use App\Services\PerformanceService;
use App\Services\PopulationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BatchController extends Controller implements HasMiddleware
{
    /** Viewer hanya membaca; admin & operator boleh membuat batch, mencatat mutasi, dan menutup batch. */
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin,operator', except: ['index', 'show'])];
    }

    public function index(Request $r)
    {
        $batches = Batch::with(['commodity:id,name', 'coop:id,name'])
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->commodity_id, fn ($q, $v) => $q->where('commodity_id', $v))
            ->when($r->q, fn ($q, $v) => $q->where('code', 'like', '%'.$v.'%'))
            ->latest('start_date')->paginate(15)->withQueryString();

        return view('farmpro.batches.index', ['batches' => $batches, 'commodities' => Commodity::orderBy('name')->pluck('name', 'id')]);
    }

    public function create()
    {
        $coops = Coop::withSum(['batches as used' => fn ($q) => $q->where('status', 'active')], 'current_population')->orderBy('code')->get()
            ->mapWithKeys(fn ($c) => [$c->id => sprintf('%s — kapasitas %s, terisi %s%s', $c->name, number_format($c->capacity, 0, ',', '.'), number_format($c->used ?? 0, 0, ',', '.'), $c->status === 'maintenance' ? ' (perawatan)' : '')]);

        return view('farmpro.batches.create', [
            'commodities' => Commodity::orderBy('name')->pluck('name', 'id'),
            'coops' => $coops,
            'suppliers' => Partner::where('type', 'supplier')->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(BatchRequest $r, BatchService $svc)
    {
        try {
            $batch = $svc->create($r->validated());
        } catch (CapacityExceededException $e) {
            return back()->withInput()->with('capacity_warning', $e->getMessage());
        }

        return redirect()->route('batches.show', $batch)->with('success', "Batch {$batch->code} dibuat.");
    }

    public function show(Batch $batch, PerformanceService $perf)
    {
        $batch->load(['commodity', 'coop']);

        return view('farmpro.batches.show', [
            'batch' => $batch,
            'perf' => $perf->forBatch($batch),
            'movements' => $batch->movements()->latest('movement_date')->latest('id')->take(10)->get(),
            'suppliers' => Partner::where('type', 'supplier')->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(BatchRequest $r, Batch $batch)
    {
        $batch->update($r->validated());

        return back()->with('success', 'Data batch diperbarui.');
    }

    public function movement(MovementRequest $r, Batch $batch, PopulationService $pop)
    {
        abort_unless($batch->status === 'active', 422, 'Mutasi hanya dapat dicatat pada batch aktif.');
        $pop->record($batch, $r->type, (int) $r->quantity, $r->movement_date, $r->reason);

        return back()->with('success', 'Mutasi populasi dicatat.');
    }

    public function finish(Batch $batch, BatchService $svc)
    {
        $svc->finish($batch);

        return back()->with('success', "Batch {$batch->code} diselesaikan.");
    }

    public function cancel(Batch $batch, BatchService $svc)
    {
        $svc->cancel($batch);

        return back()->with('success', "Batch {$batch->code} dibatalkan. Biaya bibit dibalik (reversal).");
    }
}
