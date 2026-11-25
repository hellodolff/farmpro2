<?php

namespace App\Services;

/** Kalkulator anggaran (PDF bagian 11): tiga skenario. Hasil = estimasi, bukan jaminan keuntungan. */
class SimulationService
{
    // [faktor mortalitas, faktor harga jual, faktor harga pakan]
    private const SCENARIOS = [
        'conservative' => [1.5, 0.90, 1.10],
        'base' => [1.0, 1.00, 1.00],
        'optimistic' => [0.7, 1.05, 0.95],
    ];

    public function __construct(private PerformanceService $perf) {}

    public function calculate(array $i): array
    {
        $result = [];
        foreach (self::SCENARIOS as $name => [$mortF, $priceF, $feedF]) {
            $alive = $i['population'] * (1 - ($i['mortality_pct'] * $mortF) / 100);
            $volume = $alive * $i['output_per_head'];
            $price = $i['sell_price'] * $priceF;
            $revenue = $volume * $price;

            $variable = $i['population'] * ($i['doc_price'] + $i['health_per_head'])
                + $i['population'] * $i['feed_kg_per_head'] * $i['feed_price'] * $feedF
                + $i['variable_other'];
            $fixed = $i['labor'] + $i['utilities'] + $i['fixed_other'];
            $capexPerCycle = $i['capex'] / max(1, $i['capex_cycles']);
            $opex = $variable + $fixed;
            $profit = $revenue - $opex - $capexPerCycle;
            $investment = $opex + $i['capex'];

            $result[$name] = [
                'volume' => round($volume, 1),
                'revenue' => round($revenue),
                'opex' => round($opex),
                'capex' => round($i['capex']),
                'profit' => round($profit),
                'hpp' => $this->perf->hpp($opex + $capexPerCycle, $volume),
                'bep_volume' => $this->perf->bepUnits($fixed + $capexPerCycle, $price, $volume > 0 ? $variable / $volume : 0),
                'roi_pct' => $this->perf->roi($profit, $investment),
            ];
        }

        return $result;
    }
}
