<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /** Pembelian: stok masuk + SATU transaksi pengeluaran (tanpa pencatatan ganda). */
    public function purchase(InventoryItem $item, float $qty, float $unitCost, string $date, array $extra = []): InventoryMovement
    {
        return DB::transaction(function () use ($item, $qty, $unitCost, $date, $extra) {
            $i = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $newStock = (float) $i->stock + $qty;
            $i->avg_cost = $newStock > 0 ? (((float) $i->stock * (float) $i->avg_cost) + ($qty * $unitCost)) / $newStock : $unitCost;
            $i->stock = $newStock;
            $i->save();

            $mv = InventoryMovement::create([
                'inventory_item_id' => $i->id, 'direction' => 'in', 'reason' => 'purchase', 'quantity' => $qty,
                'unit_cost' => $unitCost, 'movement_date' => $date,
                'lot_number' => $extra['lot_number'] ?? null, 'expiry_date' => $extra['expiry_date'] ?? null,
            ]);

            FinancialTransaction::create([
                'trx_date' => $date, 'type' => 'expense', 'category' => 'inventory_'.$i->category,
                'amount' => round($qty * $unitCost, 2), 'description' => 'Pembelian '.$i->name,
                'source_type' => InventoryMovement::class, 'source_id' => $mv->id,
            ]);

            return $mv;
        });
    }

    /** Pemakaian: stok berkurang, biaya (rata-rata tertimbang) dibebankan ke batch. Tidak membuat pengeluaran kas baru. */
    public function consume(InventoryItem $item, float $qty, string $date, ?int $batchId, string $reason = 'batch_usage'): float
    {
        return DB::transaction(function () use ($item, $qty, $date, $batchId, $reason) {
            $i = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ((float) $i->stock < $qty) {
                throw ValidationException::withMessages(['feed_kg' => "Stok {$i->name} tidak cukup (sisa {$i->stock} {$i->base_unit})."]);
            }
            $i->stock = (float) $i->stock - $qty;
            $i->save();

            InventoryMovement::create([
                'inventory_item_id' => $i->id, 'direction' => 'out', 'reason' => $reason, 'quantity' => $qty,
                'unit_cost' => $i->avg_cost, 'batch_id' => $batchId, 'movement_date' => $date,
            ]);

            return round($qty * (float) $i->avg_cost, 2);
        });
    }
}
