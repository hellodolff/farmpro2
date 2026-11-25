<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\PopulationMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Satu-satunya pintu untuk mengubah populasi batch (tidak boleh diedit bebas). */
class PopulationService
{
    public const OUT = ['death', 'cull', 'harvest', 'transfer_out'];

    public const IN = ['transfer_in'];

    public function record(Batch $batch, string $type, int $qty, string $date, ?string $reason = null, ?int $relatedBatchId = null): PopulationMovement
    {
        if (! in_array($type, [...self::OUT, ...self::IN], true) || $qty < 1) {
            throw ValidationException::withMessages(['type' => 'Jenis atau jumlah mutasi tidak valid.']);
        }

        return DB::transaction(function () use ($batch, $type, $qty, $date, $reason, $relatedBatchId) {
            $b = Batch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $delta = in_array($type, self::OUT, true) ? -$qty : $qty;

            if ($date < $b->start_date->toDateString()) {
                throw ValidationException::withMessages(['record_date' => 'Tanggal sebelum batch dimulai.']);
            }
            if ($b->current_population + $delta < 0) {
                throw ValidationException::withMessages(['quantity' => "Jumlah melebihi populasi saat ini ({$b->current_population} ekor)."]);
            }

            $m = PopulationMovement::create([
                'batch_id' => $b->id, 'type' => $type, 'movement_date' => $date, 'quantity' => $qty,
                'reason' => $reason, 'related_batch_id' => $relatedBatchId, 'created_by' => auth()->id(),
            ]);
            $b->increment('current_population', $delta);

            return $m;
        });
    }
}
