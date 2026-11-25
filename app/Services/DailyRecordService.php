<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\DailyRecord;
use App\Models\EggProduction;
use App\Models\FeedRecord;
use App\Models\InventoryItem;
use App\Models\WeightRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Input harian satu langkah: kematian/afkir, pakan, bobot, telur — semua atomik. */
class DailyRecordService
{
    public function __construct(private PopulationService $pop, private InventoryService $inv) {}

    public function store(Batch $batch, array $d): DailyRecord
    {
        return DB::transaction(function () use ($batch, $d) {
            $date = $d['record_date'];
            if (DailyRecord::where('batch_id', $batch->id)->whereDate('record_date', $date)->exists()) {
                throw ValidationException::withMessages(['record_date' => 'Catatan harian tanggal ini sudah ada. Gunakan fitur koreksi.']);
            }

            $rec = DailyRecord::create([
                'batch_id' => $batch->id, 'record_date' => $date,
                'deaths' => $d['deaths'] ?? 0, 'culls' => $d['culls'] ?? 0,
                'temperature' => $d['temperature'] ?? null, 'humidity' => $d['humidity'] ?? null,
                'litter_condition' => $d['litter_condition'] ?? null, 'notes' => $d['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            if (($d['deaths'] ?? 0) > 0) {
                $this->pop->record($batch, 'death', (int) $d['deaths'], $date, $d['notes'] ?? null);
            }
            if (($d['culls'] ?? 0) > 0) {
                $this->pop->record($batch, 'cull', (int) $d['culls'], $date);
            }

            if (($d['feed_kg'] ?? 0) > 0) {
                $cost = ! empty($d['feed_item_id'])
                    ? $this->inv->consume(InventoryItem::findOrFail($d['feed_item_id']), (float) $d['feed_kg'], $date, $batch->id)
                    : 0;
                FeedRecord::create([
                    'batch_id' => $batch->id, 'record_date' => $date, 'inventory_item_id' => $d['feed_item_id'] ?? null,
                    'feed_phase' => $d['feed_phase'] ?? null, 'feed_kg' => $d['feed_kg'], 'cost' => $cost,
                ]);
            }

            if (! empty($d['avg_weight_g'])) {
                WeightRecord::create([
                    'batch_id' => $batch->id, 'record_date' => $date,
                    'age_days' => (int) $batch->start_date->diffInDays(Carbon::parse($date)),
                    'sample_count' => $d['sample_count'] ?? null, 'avg_weight_g' => $d['avg_weight_g'],
                ]);
            }

            if (isset($d['total_eggs'])) {
                EggProduction::create([
                    'batch_id' => $batch->id, 'record_date' => $date, 'total_eggs' => $d['total_eggs'],
                    'sellable_eggs' => $d['sellable_eggs'] ?? 0, 'cracked_eggs' => $d['cracked_eggs'] ?? 0,
                    'dirty_eggs' => $d['dirty_eggs'] ?? 0, 'reject_eggs' => $d['reject_eggs'] ?? 0,
                    'egg_weight_kg' => $d['egg_weight_kg'] ?? 0,
                    'productive_hens' => $batch->fresh()->current_population,
                ]);
            }

            return $rec;
        });
    }
}
