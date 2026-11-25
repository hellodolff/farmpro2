<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetSimulation;
use App\Services\SimulationService;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function calculate(Request $r, SimulationService $svc)
    {
        $d = $r->validate([
            'population' => 'required|integer|min:1', 'doc_price' => 'required|numeric|min:0',
            'feed_kg_per_head' => 'required|numeric|min:0', 'feed_price' => 'required|numeric|min:0',
            'mortality_pct' => 'required|numeric|between:0,100', 'output_per_head' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0', 'capex' => 'required|numeric|min:0',
            'health_per_head' => 'nullable|numeric|min:0', 'labor' => 'nullable|numeric|min:0',
            'utilities' => 'nullable|numeric|min:0', 'variable_other' => 'nullable|numeric|min:0',
            'fixed_other' => 'nullable|numeric|min:0', 'capex_cycles' => 'nullable|integer|min:1',
            'save_as' => 'nullable|string|max:100', 'commodity_id' => 'nullable|exists:commodities,id',
        ]);
        $in = $d + ['health_per_head' => 0, 'labor' => 0, 'utilities' => 0, 'variable_other' => 0, 'fixed_other' => 0, 'capex_cycles' => 10];
        $results = $svc->calculate($in);

        if (! empty($d['save_as'])) {
            BudgetSimulation::create(['name' => $d['save_as'], 'commodity_id' => $d['commodity_id'] ?? null, 'inputs' => $in, 'results' => $results, 'created_by' => $r->user()->id]);
        }

        return ['scenarios' => $results, 'note' => 'Estimasi berdasarkan asumsi pengguna, bukan jaminan keuntungan.'];
    }
}
