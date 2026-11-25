<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\EggProduction;
use App\Models\FeedRecord;
use App\Models\PopulationMovement;
use App\Models\WeightRecord;

/**
 * Rumus kinerja (PDF bagian 12). Definisi yang dipakai:
 * - Livability = populasi hidup ÷ populasi awal × 100
 * - FCR pedaging = total pakan ÷ bobot hidup total (bobot rata-rata × ayam yang pernah hidup tanpa mati/afkir)
 * - IP = livability × bobot rata-rata (kg) × 100 ÷ (umur × FCR)
 * Tampilkan definisi ini di laporan agar angka antarbatch adil dibandingkan.
 */
class PerformanceService
{
    public function livability(int $initial, int $alive): float
    {
        return $initial > 0 ? round($alive / $initial * 100, 2) : 0.0;
    }

    public function mortality(int $initial, int $deaths): float
    {
        return $initial > 0 ? round($deaths / $initial * 100, 2) : 0.0;
    }

    public function fcr(float $feedKg, float $outputKg): ?float
    {
        return $outputKg > 0 ? round($feedKg / $outputKg, 2) : null;
    }

    public function ip(float $livabilityPct, float $avgWeightKg, ?float $fcr, int $ageDays): ?float
    {
        if (! $fcr || $ageDays <= 0) {
            return null;
        }

        return round(($livabilityPct * $avgWeightKg * 100) / ($ageDays * $fcr), 0);
    }

    public function hdp(int $eggs, int $hens): ?float
    {
        return $hens > 0 ? round($eggs / $hens * 100, 2) : null;
    }

    public function hpp(float $totalCost, float $units): ?float
    {
        return $units > 0 ? round($totalCost / $units, 2) : null;
    }

    public function bepUnits(float $fixedCost, float $pricePerUnit, float $variablePerUnit): ?float
    {
        $margin = $pricePerUnit - $variablePerUnit;

        return $margin > 0 ? round($fixedCost / $margin, 1) : null;
    }

    public function roi(float $netProfit, float $investment): ?float
    {
        return $investment > 0 ? round($netProfit / $investment * 100, 2) : null;
    }

    /** Ringkasan performa satu batch, dihitung dari transaksi sumber. */
    public function forBatch(Batch $b): array
    {
        $b->loadMissing('commodity');
        $feed = (float) FeedRecord::where('batch_id', $b->id)->sum('feed_kg');
        $deaths = (int) PopulationMovement::where('batch_id', $b->id)->where('type', 'death')->sum('quantity');
        $culls = (int) PopulationMovement::where('batch_id', $b->id)->where('type', 'cull')->sum('quantity');
        $alive = (int) $b->fresh()->current_population;

        $out = [
            'age_days' => $b->age_days,
            'feed_kg' => $feed,
            'deaths' => $deaths,
            'culls' => $culls,
            'mortality_pct' => $this->mortality($b->initial_population, $deaths),
            'livability_pct' => $this->livability($b->initial_population, $alive),
        ];

        if ($b->commodity->code === 'layer') {
            $eggKg = (float) EggProduction::where('batch_id', $b->id)->sum('egg_weight_kg');
            $last = EggProduction::where('batch_id', $b->id)->latest('record_date')->first();
            $out['hdp_pct'] = $last ? $this->hdp($last->total_eggs, $last->productive_hens) : null;
            $out['fcr'] = $this->fcr($feed, $eggKg);
        } else {
            $w = WeightRecord::where('batch_id', $b->id)->latest('record_date')->first();
            $avgKg = $w ? (float) $w->avg_weight_g / 1000 : 0.0;
            $out['avg_weight_g'] = $w?->avg_weight_g;
            $out['fcr'] = $this->fcr($feed, $avgKg * $alive);
            $out['ip'] = $this->ip($out['livability_pct'], $avgKg, $out['fcr'], $b->age_days);
        }

        return $out;
    }
}
