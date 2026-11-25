# Langkah Pengembangan FarmPro

## Prasyarat
PHP ≥ 8.2, Composer, MySQL/MariaDB, Node.js ≥ 18, Git. Untuk mobile: aplikasi **Expo Go** di HP atau emulator Android.

## Langkah 1 — Buat project Laravel dan pasang overlay
```bash
composer create-project laravel/laravel farmpro-app
cd farmpro-app
composer require laravel/breeze --dev
php artisan breeze:install blade        # login/logout web
php artisan install:api                 # Sanctum untuk mobile
```
Pastikan `app/Models/User.php` memakai trait `Laravel\Sanctum\HasApiTokens` (tambahkan jika belum ada).
Lalu **salin isi folder `backend/` ke atas project** (timpa `routes/web.php` dan `routes/api.php`):
```bash
cp -R /path/ke/farmpro/backend/* .
```

## Langkah 2 — Database
Buat database kosong `farmpro`, lalu isi `.env`:
```
DB_CONNECTION=mysql
DB_DATABASE=farmpro
DB_USERNAME=root
DB_PASSWORD=
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta
FARMPRO_DEMO=true
```
```bash
php artisan migrate --seed
```
Akun demo: `admin@farmpro.test` / `password` (data simulasi; matikan dengan `FARMPRO_DEMO=false`).

## Langkah 3 — Jalankan web
```bash
php artisan serve --host=0.0.0.0
php artisan test        # Unit (rumus) + Feature (Tahap 1)
```
Buka `http://localhost:8000` → login → dashboard.

## Langkah 4 — Uji API sebelum membuat layar mobile
```bash
curl -X POST localhost:8000/api/login -H "Accept: application/json" \
     -d "email=admin@farmpro.test" -d "password=password"
curl localhost:8000/api/dashboard -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

## Langkah 5 — Jalankan mobile
```bash
cd mobile
npm install
npx expo install --fix          # menyelaraskan versi paket dengan Expo
cp .env.example .env            # ubah EXPO_PUBLIC_API_URL
npx expo start
```
- Emulator Android: `http://10.0.2.2:8000/api`
- HP asli: `http://IP-LAPTOP:8000/api` (satu Wi-Fi, `artisan serve --host=0.0.0.0`)

## Urutan pengerjaan berikutnya (ikuti PDF bagian 18)
| Tahap | Kerjakan | Sudah ada di kerangka |
|---|---|---|
| 1. Fondasi | CRUD Komoditas, Kandang, Batch (web), Form Request, role admin/operator | tabel, model, API batch, daftar batch |
| 2. Operasional | halaman input harian web, koreksi catatan (reversal), panen → mutasi `harvest` | `DailyRecordService`, API input harian |
| 3. Kesehatan & stok | CRUD jadwal/riwayat kesehatan, barang masuk/keluar, notifikasi stok | tabel, `InventoryService`, API stok |
| 4. Keuangan | pemasukan/pengeluaran, biaya per batch, HPP, laba-rugi, penjualan → `financial_transactions` | tabel, API transaksi |
| 5. Analitik | grafik bobot/FCR/HDP vs target, BEP/ROI per batch | `PerformanceService`, `SimulationService` |
| 6. Rilis | ekspor Excel/PDF, backup terjadwal, audit log, test edge case, Vite (ganti CDN) | tabel `audit_logs` |

Aturan kerja per fitur: (1) migrasi → (2) service + test → (3) controller + validasi → (4) view/layar → (5) cek angka dashboard ikut berubah.

## Keputusan yang perlu Anda tetapkan sendiri
- Definisi FCR/IP/mortalitas (tertulis di `PerformanceService`) — tampilkan di laporan.
- Metode nilai stok (kerangka memakai rata-rata tertimbang) dan alokasi biaya bersama antar batch.
- Target performa & jadwal vaksin hanya referensi; **konfirmasi dengan dokter hewan**. Sistem tidak mendiagnosis.

---

# Tahap 1 — Fondasi (selesai di kerangka ini)

## Yang sudah ada
| Fitur | Lokasi |
|---|---|
| CRUD Komoditas (admin) + target + audit perubahan target | `Web/CommodityController`, `views/farmpro/commodities` |
| CRUD Kandang (admin), keterisian, status otomatis | `Web/CoopController`, `views/farmpro/coops` |
| Batch: daftar+filter, buat, detail, catatan/pemasok, mutasi populasi, selesaikan, batalkan | `Web/BatchController`, `Services/BatchService`, `views/farmpro/batches` |
| Form Request (validasi + pesan Indonesia) | `Http/Requests/*` |
| Role admin / operator / viewer | `Http/Middleware/EnsureRole` + `HasMiddleware` di tiap controller |
| Test aturan bisnis | `tests/Feature/FoundationTest.php` |

## Hak akses
| Aksi | admin | operator | viewer |
|---|:-:|:-:|:-:|
| Lihat dashboard, komoditas, kandang, batch | ✔ | ✔ | ✔ |
| Ubah komoditas & kandang | ✔ | ✖ | ✖ |
| Buat batch, catat mutasi, selesai/batal | ✔ | ✔ | ✖ |

Akun seed (password `password`): `admin@`, `operator@`, `viewer@` + `farmpro.test`.

## Aturan data yang dijaga
- Kode batch unik & tidak bisa dipakai ulang (termasuk batch yang di-soft-delete).
- Melebihi kapasitas kandang → peringatan + wajib centang konfirmasi (web) / `needs_confirmation` (API).
- Biaya bibit dicatat **satu kali**; membatalkan batch membuat **reversal** (nominal minus), bukan menghapus.
- Populasi hanya berubah lewat `PopulationService` (tidak bisa negatif). Batch hanya bisa diselesaikan saat populasi 0.
- Batch yang sudah punya catatan tidak bisa dibatalkan (gunakan Selesaikan).
- Kandang/komoditas yang pernah dipakai batch tidak bisa dihapus. Kode komoditas dikunci setelah dibuat.

## Cek manual (±10 menit)
1. Login `admin@` → Komoditas → ubah target FCR → simpan.
2. Kandang → tambah "Kandang D" kapasitas 100.
3. Login `operator@` → Batch Baru, populasi 150 di Kandang D → muncul peringatan → centang → simpan.
4. Buka batch → catat mutasi panen 150 → Selesaikan batch → kandang kembali "Tersedia".
5. Login `viewer@` → tombol tambah/ubah tidak tampil; membuka `/data-peternakan/komoditas/create` menghasilkan 403.

## Terjadwal berikutnya (Tahap 2)
Halaman web input harian (memakai `DailyRecordService`), koreksi catatan lewat reversal, panen lengkap (bobot, harga, pembeli) → penjualan, grafik bobot vs target.

## Catatan bahasa validasi
Pesan khusus Form Request sudah berbahasa Indonesia. Untuk pesan bawaan Laravel lainnya (mis. halaman login Breeze):
`php artisan lang:publish` lalu pasang paket terjemahan Indonesia (mis. `laravel-lang/lang`).
