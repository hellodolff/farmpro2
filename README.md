# FarmPro — Sistem Manajemen Peternakan Ayam

Web (Laravel, tampilan mengikuti mockup dashboard) + aplikasi mobile (Expo React Native).
Komoditas: **Broiler, Pejantan, Layer**. Spesifikasi: dua PDF rancangan; entitas pusat = **Batch**.

```
farmpro/
├── README.md
├── docs/
│   ├── LANGKAH_PENGEMBANGAN.md   ← mulai dari sini
│   └── DATABASE.md               ← ERD + aturan data
├── backend/                      ← OVERLAY untuk project Laravel 11 (bukan seluruh framework)
│   ├── config/farmpro.php        menu sidebar (PDF bagian 4)
│   ├── database/
│   │   ├── migrations/           5 file, urut per tahap
│   │   └── seeders/              data simulasi
│   ├── app/
│   │   ├── Models/               21 model (+ Concerns/BelongsToBatch)
│   │   ├── Services/             logika domain: Population, Inventory, DailyRecord, Performance, Simulation, Dashboard
│   │   ├── Http/{Middleware,Requests}/   EnsureRole, Form Request
│   │   └── Http/Controllers/
│   │       ├── Web/              Dashboard, Batch  (Blade)
│   │       └── Api/              Auth, Dashboard, Batch, DailyRecord, Inventory, Finance, Simulation (JSON untuk mobile)
│   ├── routes/{web,api}.php
│   ├── resources/views/{farmpro,components/farmpro}   layout, sidebar, dashboard
│   └── tests/Unit/PerformanceServiceTest.php
└── mobile/                       ← Expo (React Native)
    ├── App.js
    └── src/{api,navigation,screens,auth.js,theme.js,ui.js}
```

Prinsip: pengontrol tipis → **Service** memegang aturan (populasi tidak boleh negatif, stok tidak boleh negatif,
biaya dicatat satu kali, semua dalam DB transaction). Web dan mobile memakai service yang sama.

> Status: Tahap 1 (Fondasi) lengkap di web; Tahap 2–5 baru sebagian (service + API). Kode belum dijalankan di mesin saya (lingkungan tanpa PHP/Composer),
> jadi jalankan langkah di docs dan laporkan error pertama yang muncul.
