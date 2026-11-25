<?php

namespace App\Services;

use App\Exceptions\CapacityExceededException;
use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Coop;
use App\Models\FinancialTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Aturan siklus hidup batch (PDF bagian 3 & 6). Dipakai bersama oleh web dan API mobile. */
class BatchService
{
    /** @throws CapacityExceededException|ValidationException */
    public function create(array $d): Batch
    {
        return DB::transaction(function () use ($d) {
            $coop = Coop::whereKey($d['coop_id'])->lockForUpdate()->firstOrFail();

            if ($coop->status === 'maintenance') {
                throw ValidationException::withMessages(['coop_id' => "Kandang {$coop->name} sedang dalam perawatan."]);
            }

            $used = (int) Batch::where('coop_id', $coop->id)->where('status', 'active')->sum('current_population');
            if ($used + $d['initial_population'] > $coop->capacity && ! ($d['confirm_over_capacity'] ?? false)) {
                throw new CapacityExceededException(sprintf(
                    'Populasi %s + %s melebihi kapasitas %s (%s ekor).',
                    number_format($used, 0, ',', '.'), number_format($d['initial_population'], 0, ',', '.'), $coop->name, number_format($coop->capacity, 0, ',', '.')
                ));
            }

            $cost = round($d['initial_population'] * $d['price_per_head'], 2);
            $batch = Batch::create(Arr::only($d, ['code', 'commodity_id', 'coop_id', 'supplier_id', 'start_date', 'seed_type', 'initial_population', 'price_per_head', 'notes']) + [
                'current_population' => $d['initial_population'], 'initial_cost' => $cost, 'status' => 'active',
            ]);

            // Biaya bibit dicatat SATU kali sebagai pengeluaran.
            FinancialTransaction::create([
                'trx_date' => $d['start_date'], 'type' => 'expense', 'category' => 'DOC', 'amount' => $cost, 'batch_id' => $batch->id,
                'description' => 'Pembelian bibit batch '.$batch->code, 'source_type' => Batch::class, 'source_id' => $batch->id,
            ]);

            $coop->update(['status' => 'occupied']);
            AuditLog::record('create', $batch, null, ['code' => $batch->code, 'population' => $batch->initial_population]);

            return $batch;
        });
    }

    /** Selesai: hanya jika populasi sudah nol (panen/afkir/kematian tercatat sebagai mutasi). */
    public function finish(Batch $batch): void
    {
        if ($batch->status !== 'active') {
            throw ValidationException::withMessages(['status' => 'Hanya batch aktif yang dapat diselesaikan.']);
        }
        if ($batch->current_population > 0) {
            throw ValidationException::withMessages(['status' => "Masih ada {$batch->current_population} ekor. Catat panen, afkir, atau kematian terlebih dahulu."]);
        }

        DB::transaction(function () use ($batch) {
            $end = $batch->movements()->max('movement_date') ?? today()->toDateString();
            $batch->update(['status' => 'completed', 'end_date' => $end]);
            $this->releaseCoop($batch);
            AuditLog::record('finish', $batch, ['status' => 'active'], ['status' => 'completed', 'end_date' => $end]);
        });
    }

    /** Batal: hanya jika belum ada catatan. Biaya bibit dibalik dengan reversal, bukan dihapus. */
    public function cancel(Batch $batch): void
    {
        if (! in_array($batch->status, ['active', 'planned'], true)) {
            throw ValidationException::withMessages(['status' => 'Batch ini tidak dapat dibatalkan.']);
        }
        if ($batch->movements()->exists() || $batch->dailyRecords()->exists()) {
            throw ValidationException::withMessages(['status' => 'Batch sudah memiliki catatan. Gunakan "Selesaikan batch" setelah populasi dicatat habis.']);
        }

        DB::transaction(function () use ($batch) {
            $orig = FinancialTransaction::where('source_type', Batch::class)->where('source_id', $batch->id)
                ->where('category', 'DOC')->whereNull('reversed_of_id')->first();

            if ($orig && ! FinancialTransaction::where('reversed_of_id', $orig->id)->exists()) {
                FinancialTransaction::create([
                    'trx_date' => today(), 'type' => 'expense', 'category' => 'DOC', 'amount' => -$orig->amount, 'batch_id' => $batch->id,
                    'description' => 'Reversal pembatalan batch '.$batch->code, 'source_type' => Batch::class, 'source_id' => $batch->id, 'reversed_of_id' => $orig->id,
                ]);
            }

            $batch->update(['status' => 'cancelled', 'end_date' => today()]);
            $this->releaseCoop($batch);
            AuditLog::record('cancel', $batch, ['status' => 'active'], ['status' => 'cancelled']);
        });
    }

    private function releaseCoop(Batch $batch): void
    {
        $stillUsed = Batch::where('coop_id', $batch->coop_id)->where('status', 'active')->where('id', '!=', $batch->id)->exists();
        if (! $stillUsed) {
            Coop::whereKey($batch->coop_id)->where('status', 'occupied')->update(['status' => 'available']);
        }
    }
}
