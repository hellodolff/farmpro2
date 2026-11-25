# Database FarmPro

Migrasi dipisah per tahap (jalankan berurutan oleh `php artisan migrate`):

| File | Tabel |
|---|---|
| 000001 master | farm_profiles, users (+role, farm_profile_id), commodities, coops, customers_suppliers |
| 000002 batch | batches, batch_population_movements |
| 000003 operasional | daily_records, feed_records, weight_records, egg_productions, harvests |
| 000004 kesehatan & stok | inventory_items, inventory_movements, health_records, health_schedules |
| 000005 keuangan & sistem | sales, expenses, financial_transactions, budget_simulations, farm_notifications, audit_logs |

```mermaid
erDiagram
  commodities ||--o{ batches : has
  coops ||--o{ batches : houses
  batches ||--o{ batch_population_movements : mutasi
  batches ||--o{ daily_records : harian
  batches ||--o{ feed_records : pakan
  batches ||--o{ weight_records : bobot
  batches ||--o{ egg_productions : telur
  batches ||--o{ harvests : panen
  batches ||--o{ health_records : kesehatan
  batches ||--o{ health_schedules : jadwal
  batches ||--o{ sales : jual
  batches ||--o{ financial_transactions : biaya
  inventory_items ||--o{ inventory_movements : mutasi_stok
  inventory_items ||--o{ feed_records : dipakai
  customers_suppliers ||--o{ batches : pemasok
  customers_suppliers ||--o{ sales : pembeli
```

## Aturan penting
- `batches.current_population` hanya diubah `PopulationService` (mutasi: death, cull, harvest, transfer_in/out).
- Uang/bobot/kuantitas = `DECIMAL`, bukan FLOAT. Soft delete pada master dan transaksi penting.
- Pembelian barang → 1 `inventory_movements` + 1 `financial_transactions` (expense). Pemakaian pakan → stok turun, biaya masuk `feed_records.cost`, **tanpa** pengeluaran kas kedua.
- `financial_transactions` adalah sumber tunggal dashboard; `type=transfer` tidak masuk laba-rugi; koreksi memakai reversal (`reversed_of_id`).
- Tabel notifikasi bernama `farm_notifications` agar tidak bentrok dengan tabel `notifications` bawaan Laravel.
