<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Commodity;
use App\Models\Coop;
use App\Models\EggProduction;
use App\Models\FarmNotification;
use App\Models\FarmProfile;
use App\Models\FeedRecord;
use App\Models\FinancialTransaction;
use App\Models\HealthRecord;
use App\Models\HealthSchedule;
use App\Models\InventoryItem;
use App\Models\PopulationMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DATA SIMULASI (ilustrasi) — bukan data aktual. Angka target = referensi awal dari PDF, dapat diubah di Pengaturan.
 * Login: admin@farmpro.test / password
 */
class FarmDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('farmpro.demo')) {
            return;
        }

        if (User::where('email', 'admin@farmpro.test')->exists()) {
            return;
        }

        $farm = FarmProfile::create(['name' => 'FarmPro Demo Farm']);
        User::unguarded(fn () => User::updateOrCreate(['email' => 'admin@farmpro.test'], [
            'name' => 'Andi Pratama', 'password' => Hash::make('password'), 'role' => 'admin', 'farm_profile_id' => $farm->id,
        ]));

        $com = [];
        foreach ([
            ['broiler', 'Broiler', 'kg', 1.40, 1.60, 5.0, 2.000, 35],
            ['pejantan', 'Pejantan', 'kg', 1.60, 1.85, 7.0, 1.000, 60],
            ['layer', 'Layer', 'butir', 2.10, 2.35, 8.0, null, 700],
        ] as [$code, $name, $unit, $fmin, $fmax, $mort, $w, $days]) {
            $com[$code] = Commodity::create([
                'code' => $code, 'name' => $name, 'product_unit' => $unit, 'target_fcr_min' => $fmin, 'target_fcr_max' => $fmax,
                'target_mortality_pct' => $mort, 'target_harvest_weight_kg' => $w, 'target_cycle_days' => $days,
            ]);
        }

        $coop = [];
        foreach (['A' => 5000, 'B' => 3000, 'C' => 2000] as $code => $cap) {
            $coop[$code] = Coop::create(['code' => "KDG-$code", 'name' => "Kandang $code", 'capacity' => $cap, 'type' => 'postal']);
        }

        // [kode, komoditas, kandang, mulai, populasi awal, status, [[jenis, tanggal, jumlah], ...]]
        $rows = [
            ['BR-H1', 'broiler', 'A', '2026-07-01', 4000, 'completed', [['death', '2026-07-20', 120], ['harvest', '2026-08-05', 3880]]],
            ['BR-H2', 'broiler', 'A', '2026-08-10', 4100, 'completed', [['death', '2026-08-25', 130], ['harvest', '2026-09-14', 3970]]],
            ['BR-H3', 'broiler', 'A', '2026-09-19', 4200, 'completed', [['death', '2026-10-05', 150], ['harvest', '2026-10-24', 4050]]],
            ['JP-H1', 'pejantan', 'B', '2026-07-10', 2000, 'completed', [['death', '2026-08-01', 80], ['harvest', '2026-09-10', 1920]]],
            ['LY-H1', 'layer', 'C', '2026-06-01', 1500, 'completed', [['death', '2026-07-15', 60], ['cull', '2026-10-30', 1440]]],
            ['BR-001', 'broiler', 'A', '2026-11-01', 4600, 'active', [['death', '2026-11-10', 100]]],
            ['JP-001', 'pejantan', 'B', '2026-11-05', 2400, 'active', [['death', '2026-11-12', 50]]],
            ['LY-001', 'layer', 'C', '2026-11-10', 1650, 'active', [['death', '2026-11-15', 50]]],
        ];
        $batch = [];
        foreach ($rows as [$code, $cm, $cp, $start, $init, $status, $moves]) {
            $cur = $init;
            foreach ($moves as [$t, , $q]) {
                $cur += $t === 'transfer_in' ? $q : -$q;
            }
            $batch[$code] = Batch::create([
                'code' => $code, 'commodity_id' => $com[$cm]->id, 'coop_id' => $coop[$cp]->id, 'start_date' => $start,
                'end_date' => $status === 'completed' ? end($moves)[1] : null, 'seed_type' => $cm === 'layer' ? 'pullet' : 'doc',
                'initial_population' => $init, 'current_population' => $cur, 'price_per_head' => 7000, 'initial_cost' => $init * 7000, 'status' => $status,
            ]);
            foreach ($moves as [$t, $d, $q]) {
                PopulationMovement::create(['batch_id' => $batch[$code]->id, 'type' => $t, 'movement_date' => $d, 'quantity' => $q]);
            }
        }

        $feed = InventoryItem::create(['code' => 'PKN-BR-ST', 'name' => 'Pakan Broiler Starter', 'category' => 'feed', 'base_unit' => 'kg', 'purchase_unit' => 'sak', 'unit_conversion' => 50, 'min_stock' => 500, 'stock' => 420, 'avg_cost' => 8500]);
        InventoryItem::create(['code' => 'VKS-ND-IB', 'name' => 'Vaksin ND-IB', 'category' => 'vaccine', 'base_unit' => 'dosis', 'min_stock' => 2000, 'stock' => 6000, 'avg_cost' => 90]);
        InventoryItem::create(['code' => 'VIT-KMP', 'name' => 'Vitamin Kompleks', 'category' => 'vitamin', 'base_unit' => 'gram', 'min_stock' => 500, 'stock' => 1200, 'avg_cost' => 120]);

        FeedRecord::create(['batch_id' => $batch['BR-001']->id, 'record_date' => today(), 'inventory_item_id' => $feed->id, 'feed_kg' => 120, 'cost' => 120 * 8500]);
        HealthRecord::create(['batch_id' => $batch['BR-001']->id, 'record_date' => today(), 'type' => 'vaccine', 'product_name' => 'ND-IB']);
        EggProduction::create(['batch_id' => $batch['LY-001']->id, 'record_date' => today(), 'total_eggs' => 850, 'sellable_eggs' => 820, 'productive_hens' => 1600, 'egg_weight_kg' => 50]);

        foreach ([['Vaksin ND', 'BR-001', '2026-12-02'], ['Pemberian Obat', 'LY-001', '2026-12-03'], ['Perkiraan Panen', 'BR-001', '2026-12-05']] as [$t, $b, $due]) {
            HealthSchedule::create(['batch_id' => $batch[$b]->id, 'title' => $t, 'due_date' => $due]);
        }

        // Keuangan: Nov = Rp 24.500.000 / Rp 16.200.000; Okt sebagai pembanding pertumbuhan.
        $trx = [
            ['2026-11-12', 'income', 'Penjualan ayam', 15000000], ['2026-11-14', 'income', 'Penjualan ayam', 6500000], ['2026-11-18', 'income', 'Penjualan telur', 3000000],
            ['2026-11-02', 'expense', 'Pakan', 9000000], ['2026-11-03', 'expense', 'DOC', 4000000], ['2026-11-08', 'expense', 'Kesehatan', 1200000], ['2026-11-15', 'expense', 'Operasional', 2000000],
            ['2026-10-20', 'income', 'Penjualan ayam', 21780000], ['2026-10-05', 'expense', 'Pakan', 9000000], ['2026-10-10', 'expense', 'Operasional', 5960000],
        ];
        foreach ($trx as [$d, $t, $c, $a]) {
            FinancialTransaction::create(['trx_date' => $d, 'type' => $t, 'category' => $c, 'amount' => $a, 'description' => 'Data simulasi']);
        }

        foreach (['Vaksin ND jatuh tempo', 'Stok pakan mendekati batas minimum', 'Estimasi panen BR-001'] as $title) {
            FarmNotification::create(['type' => 'info', 'title' => $title]);
        }
    }
}
