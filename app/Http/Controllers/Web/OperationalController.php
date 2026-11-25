<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureRole;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\EggProduction;
use App\Models\FeedRecord;
use App\Models\FinancialTransaction;
use App\Models\Harvest;
use App\Models\HealthRecord;
use App\Models\HealthSchedule;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Partner;
use App\Models\PopulationMovement;
use App\Models\Sale;
use App\Models\WeightRecord;
use App\Services\DailyRecordService;
use App\Services\InventoryService;
use App\Services\PopulationService;
use App\Services\SimulationService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(EnsureRole::class.':admin,operator', except: ['dailyIndex', 'feedIndex', 'weightIndex', 'mortalityIndex', 'eggIndex', 'harvestIndex', 'healthIndex', 'inventoryIndex', 'financeIndex', 'planningIndex', 'reportsIndex'])];
    }

    public function dailyIndex()
    {
        return view('farmpro.modules.operational', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'feedItems' => InventoryItem::where('category', 'feed')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function dailyStore(Request $r, DailyRecordService $svc)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'record_date' => 'required|date|before_or_equal:today',
            'deaths' => 'nullable|integer|min:0', 'culls' => 'nullable|integer|min:0',
            'feed_kg' => 'nullable|numeric|min:0', 'feed_item_id' => 'nullable|exists:inventory_items,id',
            'feed_phase' => 'nullable|string|max:30', 'avg_weight_g' => 'nullable|numeric|min:0',
            'sample_count' => 'nullable|integer|min:1', 'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|integer|between:0,100', 'litter_condition' => 'nullable|string|max:30',
            'notes' => 'nullable|string', 'total_eggs' => 'nullable|integer|min:0',
            'sellable_eggs' => 'nullable|integer|min:0', 'cracked_eggs' => 'nullable|integer|min:0',
            'dirty_eggs' => 'nullable|integer|min:0', 'reject_eggs' => 'nullable|integer|min:0',
            'egg_weight_kg' => 'nullable|numeric|min:0',
        ]);
        $batch = Batch::active()->findOrFail($d['batch_id']);
        $svc->store($batch, $d);

        return back()->with('success', 'Catatan harian berhasil disimpan.');
    }

    public function dailyReverse(Request $r, Batch $batch, PopulationService $pop)
    {
        $d = $r->validate(['record_id' => 'required|exists:daily_records,id']);
        $record = $batch->dailyRecords()->findOrFail($d['record_id']);

        DB::transaction(function () use ($record, $batch, $pop) {
            $recordDate = $record->record_date->toDateString();
            $deaths = (int) $record->deaths;
            $culls = (int) $record->culls;
            if ($deaths > 0) $pop->record($batch, 'transfer_in', $deaths, $recordDate, 'Reversal catatan harian');
            if ($culls > 0) $pop->record($batch, 'transfer_in', $culls, $recordDate, 'Reversal catatan harian');

            $feed = FeedRecord::where('batch_id', $batch->id)->whereDate('record_date', $recordDate)->first();
            if ($feed && $feed->inventory_item_id && $feed->feed_kg > 0) {
                $item = InventoryItem::find($feed->inventory_item_id);
                if ($item) {
                    $item->increment('stock', (float) $feed->feed_kg);
                    InventoryMovement::create(['inventory_item_id' => $item->id, 'direction' => 'in', 'reason' => 'return', 'quantity' => $feed->feed_kg, 'unit_cost' => $item->avg_cost, 'batch_id' => $batch->id, 'movement_date' => now()->toDateString()]);
                }
            }

            FeedRecord::where('batch_id', $batch->id)->whereDate('record_date', $recordDate)->delete();
            WeightRecord::where('batch_id', $batch->id)->whereDate('record_date', $recordDate)->delete();
            EggProduction::where('batch_id', $batch->id)->whereDate('record_date', $recordDate)->delete();
            $record->delete();
        });

        return back()->with('success', 'Catatan dibatalkan dan dampaknya dibalik.');
    }

    public function harvestStore(Request $r, PopulationService $pop)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'harvest_date' => 'required|date|before_or_equal:today',
            'heads' => 'required|integer|min:1', 'total_weight_kg' => 'required|numeric|min:0.01',
            'weight_basis' => 'required|in:live,carcass', 'customer_id' => 'nullable|exists:customers_suppliers,id',
            'price_per_unit' => 'required|numeric|min:0', 'deductions' => 'nullable|numeric|min:0',
            'transport_cost' => 'nullable|numeric|min:0', 'notes' => 'nullable|string',
        ]);
        $batch = Batch::active()->findOrFail($d['batch_id']);

        DB::transaction(function () use ($batch, $d, $pop) {
            $harvest = Harvest::create(['batch_id' => $batch->id, 'harvest_date' => $d['harvest_date'], 'heads' => $d['heads'], 'total_weight_kg' => $d['total_weight_kg'], 'weight_basis' => $d['weight_basis'], 'notes' => $d['notes'] ?? null]);
            $pop->record($batch, 'harvest', (int) $d['heads'], $d['harvest_date'], 'Panen batch '.$batch->code);
            $netWeightKg = (float) $d['total_weight_kg'] - (float) ($d['deductions'] ?? 0);
            if ($netWeightKg < 0) {
                throw ValidationException::withMessages(['deductions' => 'Potongan bobot tidak boleh melebihi bobot total kotor.']);
            }
            $total = round($netWeightKg * (float) $d['price_per_unit'], 2);
            $sale = Sale::create(['batch_id' => $batch->id, 'harvest_id' => $harvest->id, 'customer_id' => $d['customer_id'] ?? null, 'sale_date' => $d['harvest_date'], 'product' => 'meat', 'quantity' => $netWeightKg, 'unit' => 'kg', 'price_per_unit' => $d['price_per_unit'], 'deductions' => $d['deductions'] ?? 0, 'transport_cost' => $d['transport_cost'] ?? 0, 'total' => $total, 'payment_status' => 'paid']);
            FinancialTransaction::create(['trx_date' => $d['harvest_date'], 'type' => 'income', 'category' => 'penjualan', 'amount' => $total, 'batch_id' => $batch->id, 'source_type' => Sale::class, 'source_id' => $sale->id, 'description' => 'Penjualan panen '.$batch->code]);
        });

        return back()->with('success', 'Panen dan penjualan berhasil dicatat.');
    }

    public function healthIndex()
    {
        return view('farmpro.modules.health', ['batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(), 'schedules' => HealthSchedule::with('batch')->orderBy('due_date')->get(), 'records' => HealthRecord::with('batch')->latest('record_date')->take(30)->get()]);
    }

    public function healthStore(Request $r)
    {
        $d = $r->validate(['batch_id' => 'required|exists:batches,id', 'title' => 'required|string|max:255', 'type' => 'required|in:vaccine,medicine,vitamin', 'due_date' => 'required|date', 'priority' => 'required|in:low,normal,high']);
        HealthSchedule::create($d);
        return back()->with('success', 'Jadwal kesehatan dibuat.');
    }

    public function inventoryIndex()
    {
        return view('farmpro.modules.inventory', ['items' => InventoryItem::where('is_active', true)->orderBy('category')->orderBy('name')->get(), 'movements' => InventoryMovement::with('inventoryItem')->latest('movement_date')->latest('id')->take(40)->get()]);
    }

    public function inventoryPurchase(Request $r, InventoryItem $item, InventoryService $svc)
    {
        $d = $r->validate(['quantity' => 'required|numeric|gt:0', 'unit_cost' => 'required|numeric|min:0', 'movement_date' => 'required|date', 'lot_number' => 'nullable|string|max:50', 'expiry_date' => 'nullable|date']);
        $svc->purchase($item, (float) $d['quantity'], (float) $d['unit_cost'], $d['movement_date'], $d);
        return back()->with('success', 'Barang masuk berhasil dicatat.');
    }

    public function financeIndex()
    {
        $trx = FinancialTransaction::latest('trx_date')->latest('id')->take(100)->get();
        $income = (float) $trx->where('type', 'income')->sum('amount');
        $expense = (float) $trx->where('type', 'expense')->sum('amount');
        return view('farmpro.modules.finance', compact('trx', 'income', 'expense'));
    }

    public function planningIndex()
    {
        return view('farmpro.modules.planning', ['commodities' => \App\Models\Commodity::orderBy('name')->pluck('name', 'id')]);
    }

    public function planningCalculate(Request $r, SimulationService $svc)
    {
        $d = $r->validate([
            'population' => 'required|integer|min:1', 'doc_price' => 'required|numeric|min:0',
            'feed_kg_per_head' => 'required|numeric|min:0', 'feed_price' => 'required|numeric|min:0',
            'mortality_pct' => 'required|numeric|between:0,100', 'output_per_head' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0', 'capex' => 'required|numeric|min:0',
        ]);
        $d += ['health_per_head' => 0, 'labor' => 0, 'utilities' => 0, 'variable_other' => 0, 'fixed_other' => 0, 'capex_cycles' => 10];

        return back()->with('simulation', $svc->calculate($d));
    }

    public function reportsIndex(Request $r)
    {
        $r->validate(['format' => 'nullable|in:view,csv,pdf']);
        $module = app(\App\Http\Controllers\Web\ModuleController::class);
        $report = $module->filteredReports($r);
        $rows = collect($report['rows']);

        if ($r->format === 'csv') {
            $columns = $report['columns'];
            $csv = implode(',', array_map(fn ($column) => '"'.str_replace('"', '""', $column).'"', $columns))."\n";
            foreach ($rows as $row) {
                $csv .= implode(',', array_map(fn ($column) => '"'.str_replace('"', '""', (string) ($row[$column] ?? '')).'"', $columns))."\n";
            }

            return response($csv)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="laporan-'.$report['jenis'].'-'.now()->format('Y-m-d').'.csv"');
        }

        if ($r->format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('farmpro.modules.reports-pdf', [
                'title' => $report['title'],
                'columns' => $report['columns'],
                'rows' => $rows,
                'start' => $report['start'],
                'end' => $report['end'],
                'generatedAt' => now(),
            ]);

            return $pdf->download('laporan-'.$report['jenis'].'-'.now()->format('Y-m-d').'.pdf');
        }

        return view('farmpro.modules.reports-hub', $report);
    }

    public function feedIndex()
    {
        return view('farmpro.modules.feed', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'feedItems' => InventoryItem::where('category', 'feed')->where('is_active', true)->orderBy('name')->get(),
            'records' => FeedRecord::with(['batch:id,code', 'batch.commodity:id,name'])->latest('record_date')->latest('id')->take(100)->get(),
        ]);
    }

    public function feedStore(Request $r, InventoryService $inventory)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id',
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'record_date' => 'required|date|before_or_equal:today',
            'feed_phase' => 'required|in:starter,grower,finisher,layer',
            'feed_kg' => 'required|numeric|gt:0',
        ]);

        $batch = Batch::active()->findOrFail($d['batch_id']);
        $item = InventoryItem::whereKey($d['inventory_item_id'])->where('category', 'feed')->where('is_active', true)->firstOrFail();

        DB::transaction(function () use ($batch, $item, $d, $inventory) {
            $cost = $inventory->consume($item, (float) $d['feed_kg'], $d['record_date'], $batch->id);
            FeedRecord::create([
                'batch_id' => $batch->id,
                'record_date' => $d['record_date'],
                'inventory_item_id' => $item->id,
                'feed_phase' => $d['feed_phase'],
                'feed_kg' => $d['feed_kg'],
                'cost' => $cost,
            ]);
        });

        return back()->with('success', 'Konsumsi pakan berhasil dicatat dan stok diperbarui.');
    }

    public function feedReverse(FeedRecord $feed, InventoryService $inventory)
    {
        if (! in_array(auth()->user()->role, ['admin', 'operator'], true)) {
            abort(403);
        }

        DB::transaction(function () use ($feed, $inventory) {
            $item = $feed->inventory_item_id ? InventoryItem::find($feed->inventory_item_id) : null;
            if ($item) {
                $item->increment('stock', (float) $feed->feed_kg);
                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'direction' => 'in',
                    'reason' => 'return',
                    'quantity' => $feed->feed_kg,
                    'unit_cost' => $item->avg_cost,
                    'batch_id' => $feed->batch_id,
                    'movement_date' => now()->toDateString(),
                ]);
            }
            $feed->delete();
        });

        return back()->with('success', 'Konsumsi pakan dibatalkan dan stok dikembalikan.');
    }

    public function weightIndex()
    {
        return view('farmpro.modules.weight', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'records' => WeightRecord::with('batch:id,code')->latest('record_date')->latest('id')->take(100)->get(),
        ]);
    }

    public function weightStore(Request $r)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'record_date' => 'required|date|before_or_equal:today',
            'avg_weight_g' => 'required|numeric|gt:0', 'sample_count' => 'nullable|integer|min:1',
            'total_sample_weight_g' => 'nullable|numeric|min:0', 'notes' => 'nullable|string',
        ]);
        $batch = Batch::active()->findOrFail($d['batch_id']);

        WeightRecord::create([
            'batch_id' => $batch->id,
            'record_date' => $d['record_date'],
            'age_days' => (int) $batch->start_date->diffInDays($d['record_date']),
            'avg_weight_g' => $d['avg_weight_g'],
            'sample_count' => $d['sample_count'] ?? null,
            'total_sample_weight_g' => $d['total_sample_weight_g'] ?? null,
            'notes' => $d['notes'] ?? null,
        ]);

        return back()->with('success', 'Penimbangan bobot berhasil dicatat.');
    }

    public function weightReverse(WeightRecord $weight)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'operator'], true), 403);
        $weight->delete();

        return back()->with('success', 'Catatan bobot dibatalkan.');
    }

    public function mortalityIndex()
    {
        return view('farmpro.modules.mortality', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'records' => PopulationMovement::with('batch:id,code')->whereIn('type', ['death', 'cull'])->latest('movement_date')->latest('id')->take(100)->get(),
        ]);
    }

    public function mortalityStore(Request $r, PopulationService $population)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'type' => 'required|in:death,cull',
            'quantity' => 'required|integer|min:1', 'movement_date' => 'required|date|before_or_equal:today',
            'reason' => 'nullable|string|max:255',
        ]);
        $batch = Batch::active()->findOrFail($d['batch_id']);
        $population->record($batch, $d['type'], (int) $d['quantity'], $d['movement_date'], $d['reason'] ?? null);

        return back()->with('success', 'Kematian atau afkir berhasil dicatat dan populasi diperbarui.');
    }

    public function mortalityReverse(PopulationMovement $movement)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'operator'], true), 403);
        abort_unless(in_array($movement->type, ['death', 'cull'], true), 422);

        DB::transaction(function () use ($movement) {
            $batch = Batch::whereKey($movement->batch_id)->lockForUpdate()->firstOrFail();
            $batch->increment('current_population', $movement->quantity);
            $movement->delete();
        });

        return back()->with('success', 'Catatan kematian atau afkir dibatalkan.');
    }

    public function eggIndex()
    {
        return view('farmpro.modules.eggs', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'records' => EggProduction::with('batch:id,code')->latest('record_date')->latest('id')->take(100)->get(),
        ]);
    }

    public function eggStore(Request $r)
    {
        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id', 'record_date' => 'required|date|before_or_equal:today',
            'total_eggs' => 'required|integer|min:0', 'sellable_eggs' => 'nullable|integer|min:0',
            'cracked_eggs' => 'nullable|integer|min:0', 'dirty_eggs' => 'nullable|integer|min:0',
            'reject_eggs' => 'nullable|integer|min:0', 'egg_weight_kg' => 'nullable|numeric|min:0',
        ]);
        $batch = Batch::active()->findOrFail($d['batch_id']);
        $classified = (int) ($d['sellable_eggs'] ?? 0) + (int) ($d['cracked_eggs'] ?? 0) + (int) ($d['dirty_eggs'] ?? 0) + (int) ($d['reject_eggs'] ?? 0);
        if ($classified > (int) $d['total_eggs']) {
            throw ValidationException::withMessages(['total_eggs' => 'Jumlah klasifikasi telur melebihi total telur.']);
        }
        if (EggProduction::where('batch_id', $batch->id)->whereDate('record_date', $d['record_date'])->exists()) {
            throw ValidationException::withMessages(['record_date' => 'Produksi telur untuk tanggal ini sudah ada.']);
        }

        EggProduction::create([
            'batch_id' => $batch->id,
            'record_date' => $d['record_date'],
            'total_eggs' => $d['total_eggs'],
            'sellable_eggs' => $d['sellable_eggs'] ?? 0,
            'cracked_eggs' => $d['cracked_eggs'] ?? 0,
            'dirty_eggs' => $d['dirty_eggs'] ?? 0,
            'reject_eggs' => $d['reject_eggs'] ?? 0,
            'egg_weight_kg' => $d['egg_weight_kg'] ?? 0,
            'productive_hens' => $batch->fresh()->current_population,
        ]);

        return back()->with('success', 'Produksi telur berhasil dicatat.');
    }

    public function eggReverse(EggProduction $egg)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'operator'], true), 403);
        $egg->delete();

        return back()->with('success', 'Catatan produksi telur dibatalkan.');
    }

    public function harvestIndex()
    {
        return view('farmpro.modules.harvest', [
            'batches' => Batch::active()->with('commodity')->orderByDesc('start_date')->get(),
            'customers' => Partner::where('type', 'customer')->orderBy('name')->pluck('name', 'id'),
            'harvests' => Harvest::with(['batch:id,code', 'batch.commodity:id,name', 'sale'])->latest('harvest_date')->latest('id')->take(100)->get(),
        ]);
    }

    public function harvestReverse(Harvest $harvest, PopulationService $population)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'operator'], true), 403);

        DB::transaction(function () use ($harvest, $population) {
            $batch = Batch::whereKey($harvest->batch_id)->lockForUpdate()->firstOrFail();
            $population->record($batch, 'transfer_in', (int) $harvest->heads, now()->toDateString(), 'Reversal panen '.$harvest->id);

            Sale::where('harvest_id', $harvest->id)->get()->each(function (Sale $sale) {
                FinancialTransaction::where('source_type', Sale::class)->where('source_id', $sale->id)->get()->each(function (FinancialTransaction $trx) {
                    $trx->delete();
                });
                $sale->delete();
            });
            $harvest->delete();
        });

        return back()->with('success', 'Panen dan penjualan dibatalkan; populasi dikembalikan.');
    }

    public function harvestUpdate(Request $r, Harvest $harvest, PopulationService $population)
    {
        abort_unless(in_array(auth()->user()->role, ['admin', 'operator'], true), 403);

        $d = $r->validate([
            'batch_id' => 'required|exists:batches,id',
            'harvest_date' => 'required|date|before_or_equal:today',
            'heads' => 'required|integer|min:1',
            'total_weight_kg' => 'required|numeric|gt:0',
            'weight_basis' => 'required|in:live,carcass',
            'customer_id' => 'nullable|exists:customers_suppliers,id',
            'deductions' => 'nullable|numeric|min:0',
            'price_per_unit' => 'required|numeric|min:0',
            'transport_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $netWeightKg = (float) $d['total_weight_kg'] - (float) ($d['deductions'] ?? 0);
        if ($netWeightKg < 0) {
            throw ValidationException::withMessages(['deductions' => 'Potongan bobot tidak boleh melebihi bobot total kotor.']);
        }

        $newBatch = Batch::active()->findOrFail($d['batch_id']);

        DB::transaction(function () use ($harvest, $d, $population, $newBatch, $netWeightKg) {
            $oldBatch = Batch::whereKey($harvest->batch_id)->lockForUpdate()->firstOrFail();

            if ((int) $d['heads'] !== (int) $harvest->heads || $newBatch->id !== $oldBatch->id) {
                $population->record($oldBatch, 'transfer_in', (int) $harvest->heads, $harvest->harvest_date, 'Reversal koreksi panen #'.$harvest->id);

                if ($newBatch->id !== $oldBatch->id) {
                    $newBatch->refresh();
                }
                $population->record($newBatch, 'harvest', (int) $d['heads'], $d['harvest_date'], 'Perubahan panen batch '.$newBatch->code.' #'.$harvest->id);
            } elseif ($d['harvest_date'] !== \Illuminate\Support\Carbon::parse($harvest->harvest_date)->toDateString()) {
                PopulationMovement::where('batch_id', $oldBatch->id)
                    ->where('type', 'harvest')
                    ->where('movement_date', \Illuminate\Support\Carbon::parse($harvest->harvest_date)->toDateString())
                    ->where('quantity', $harvest->heads)
                    ->latest('id')
                    ->first()?->update(['movement_date' => $d['harvest_date'], 'reason' => 'Perubahan panen batch '.$newBatch->code.' #'.$harvest->id]);
            }

            $oldDate = \Illuminate\Support\Carbon::parse($harvest->harvest_date)->toDateString();
            $harvest->update([
                'batch_id' => $newBatch->id,
                'harvest_date' => $d['harvest_date'],
                'heads' => $d['heads'],
                'total_weight_kg' => $d['total_weight_kg'],
                'weight_basis' => $d['weight_basis'],
                'notes' => $d['notes'] ?? null,
            ]);

            $total = round($netWeightKg * (float) $d['price_per_unit'], 2);
            $sale = Sale::where('harvest_id', $harvest->id)->first();
            $saleData = [
                'batch_id' => $newBatch->id,
                'customer_id' => $d['customer_id'] ?? null,
                'sale_date' => $d['harvest_date'],
                'product' => 'meat',
                'quantity' => $netWeightKg,
                'unit' => 'kg',
                'price_per_unit' => $d['price_per_unit'],
                'deductions' => $d['deductions'] ?? 0,
                'transport_cost' => $d['transport_cost'] ?? 0,
                'total' => $total,
                'payment_status' => 'paid',
            ];

            if ($sale) {
                $oldSale = $sale->only(['total', 'sale_date', 'quantity', 'price_per_unit', 'deductions', 'transport_cost', 'customer_id']);
                $oldTransactions = FinancialTransaction::where('source_type', Sale::class)->where('source_id', $sale->id)->whereNull('reversed_of_id')->get();

                foreach ($oldTransactions as $oldTransaction) {
                    if (! FinancialTransaction::where('reversed_of_id', $oldTransaction->id)->exists()) {
                        FinancialTransaction::create([
                            'trx_date' => $d['harvest_date'],
                            'type' => 'income',
                            'category' => 'penjualan',
                            'amount' => -(float) abs($oldTransaction->amount),
                            'batch_id' => $oldBatch->id,
                            'source_type' => Sale::class,
                            'source_id' => $sale->id,
                            'reversed_of_id' => $oldTransaction->id,
                            'description' => 'Reversal koreksi panen #'.$harvest->id,
                        ]);
                    }
                }

                $sale->update($saleData);
                AuditLog::record('update_harvest', $harvest, ['harvest_date' => $oldDate, 'heads' => $d['heads']], $harvest->only(['harvest_date', 'heads', 'total_weight_kg', 'weight_basis', 'notes']));
            } else {
                $sale = Sale::create($saleData + ['harvest_id' => $harvest->id]);
            }

            if ($total > 0) {
                FinancialTransaction::create([
                    'trx_date' => $d['harvest_date'],
                    'type' => 'income',
                    'category' => 'penjualan',
                    'amount' => $total,
                    'batch_id' => $newBatch->id,
                    'source_type' => Sale::class,
                    'source_id' => $sale->id,
                    'description' => 'Penjualan panen batch '.$newBatch->code.' (koreksi #'.$harvest->id.')',
                ]);
            }
        });

        return back()->with('success', 'Data panen dan penjualan berhasil diperbarui.');
    }
}
