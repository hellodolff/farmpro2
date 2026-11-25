<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Commodity;
use App\Models\EggProduction;
use App\Models\FeedRecord;
use App\Models\FinancialTransaction;
use App\Models\HealthRecord;
use App\Models\HealthSchedule;
use App\Models\InventoryItem;
use App\Models\PopulationMovement;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/** Seluruh angka dashboard dihitung dari transaksi sumber (PDF bagian 5). Dipakai web & API mobile. */
class DashboardService
{
    public function summary(CarbonInterface $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonth()->startOfMonth();
        $prevEnd = $prevStart->copy()->endOfMonth();

        $active = Batch::with(['commodity:id,name', 'coop:id,name'])->where('status', 'active')->get();
        $live = (int) $active->sum('current_population');
        $income = $this->trx('income', $start, $end);
        $expense = $this->trx('expense', $start, $end);
        $prevLive = array_sum($this->populationAt($prevEnd));

        $byCom = $active->groupBy(fn ($b) => $b->commodity->name)->map(fn ($g) => (int) $g->sum('current_population'));

        return [
            'month' => $start->format('Y-m'),
            'kpi' => [
                'live_population' => $live,
                'live_growth' => $this->growth($live, $prevLive),
                'active_batches' => $active->count(),
                'commodity_count' => $byCom->count(),
                'income' => $income,
                'income_growth' => $this->growth($income, $this->trx('income', $prevStart, $prevEnd)),
                'expense' => $expense,
                'expense_growth' => $this->growth($expense, $this->trx('expense', $prevStart, $prevEnd)),
                'profit' => $income - $expense,
                'profit_growth' => $this->growth($income - $expense, $this->trx('income', $prevStart, $prevEnd) - $this->trx('expense', $prevStart, $prevEnd)),
                'low_stock' => InventoryItem::whereColumn('stock', '<=', 'min_stock')->where('is_active', true)->count(),
            ],
            'population_by_commodity' => $byCom->map(fn ($t, $n) => ['name' => $n, 'total' => $t, 'pct' => $live ? (int) round($t / $live * 100) : 0])->values()->all(),
            'charts' => $this->charts($start),
            'batches' => $active->sortBy('start_date')->take(5)->map(fn ($b) => [
                'id' => $b->id, 'code' => $b->code, 'commodity' => $b->commodity->name, 'coop' => $b->coop->name,
                'start_date' => $b->start_date->format('d M Y'), 'population' => $b->current_population,
                'age_days' => $b->age_days, 'status' => 'Aktif',
            ])->values()->all(),
            'activities' => $this->activities(),
            'schedules' => HealthSchedule::with('batch:id,code')->where('status', 'pending')
                ->whereDate('due_date', '>=', today())->orderBy('due_date')->take(3)->get()
                ->map(fn ($s) => ['title' => $s->title, 'sub' => 'Batch '.$s->batch->code, 'due' => Carbon::parse($s->due_date)->format('j M Y')])->all(),
        ];
    }

    private function trx(string $type, CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) FinancialTransaction::where('type', $type)->whereBetween('trx_date', [$from->toDateString(), $to->toDateString()])->sum('amount');
    }

    private function growth(float $now, float $prev): ?float
    {
        return $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : null;
    }

    /** Saldo populasi pada tanggal tertentu per komoditas (bukan penjumlahan harian). */
    public function populationAt(CarbonInterface $date): array
    {
        $batches = Batch::with('commodity:id,name')->where('start_date', '<=', $date->toDateString())->get();
        $moves = PopulationMovement::whereIn('batch_id', $batches->pluck('id'))
            ->where('movement_date', '<=', $date->toDateString())->get()->groupBy('batch_id');

        $res = [];
        foreach ($batches as $b) {
            $delta = $moves->get($b->id, collect())->sum(fn ($m) => $m->type === 'transfer_in' ? $m->quantity : -$m->quantity);
            $res[$b->commodity->name] = ($res[$b->commodity->name] ?? 0) + $b->initial_population + $delta;
        }

        return $res;
    }

    private function charts(CarbonInterface $month): array
    {
        $months = collect(range(4, 0))->map(fn ($i) => $month->copy()->subMonths($i));
        $names = Commodity::orderBy('id')->pluck('name');
        $snap = $months->map(fn ($m) => $this->populationAt($m->copy()->endOfMonth()));

        return [
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M'))->all(),
            'populasi' => $names->map(fn ($n) => ['label' => $n, 'data' => $snap->map(fn ($s) => (int) ($s[$n] ?? 0))->all()])->all(),
            'produksi' => [['label' => 'Telur (butir)', 'data' => $months->map(fn ($m) => (int) EggProduction::whereBetween('record_date', [$m->copy()->startOfMonth()->toDateString(), $m->copy()->endOfMonth()->toDateString()])->sum('total_eggs'))->all()]],
            'keuangan' => [
                ['label' => 'Pendapatan', 'data' => $months->map(fn ($m) => $this->trx('income', $m->copy()->startOfMonth(), $m->copy()->endOfMonth()))->all()],
                ['label' => 'Pengeluaran', 'data' => $months->map(fn ($m) => $this->trx('expense', $m->copy()->startOfMonth(), $m->copy()->endOfMonth()))->all()],
            ],
        ];
    }

    private function activities(): array
    {
        return collect()
            ->merge(HealthRecord::with('batch:id,code')->latest()->take(3)->get()->map(fn ($r) => ['title' => ucfirst($r->type).' '.$r->product_name.' batch '.$r->batch->code, 'sub' => 'Kegiatan kesehatan ternak', 'at' => $r->created_at]))
            ->merge(FeedRecord::with('batch:id,code')->latest()->take(3)->get()->map(fn ($r) => ['title' => 'Penggunaan pakan', 'sub' => 'Batch '.$r->batch->code.' • '.(float) $r->feed_kg.' kg', 'at' => $r->created_at]))
            ->merge(EggProduction::with('batch:id,code')->latest()->take(3)->get()->map(fn ($r) => ['title' => 'Produksi telur', 'sub' => 'Batch '.$r->batch->code.' • '.$r->total_eggs.' butir', 'at' => $r->created_at]))
            ->sortByDesc('at')->take(3)
            ->map(fn ($a) => ['title' => $a['title'], 'sub' => $a['sub'], 'time' => $a['at']->format('H.i')])
            ->values()->all();
    }
}
