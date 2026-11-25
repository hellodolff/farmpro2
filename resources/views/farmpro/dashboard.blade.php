@extends('farmpro.layout')
@section('title', 'Dashboard')

@section('content')
@php
    $k = $d['kpi'];
    $rp = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
    $dot = ['bg-emerald-600', 'bg-teal-400', 'bg-amber-400', 'bg-sky-400'];
@endphp

{{-- ===== PAGE HEADER ===== --}}
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Dashboard</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Ringkasan aktivitas peternakan Anda secara real-time</p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="month" name="bulan" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()"
               class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 focus:outline-none shadow-sm"
               aria-label="Pilih bulan">
        <span class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live
        </span>
        @if (config('farmpro.demo'))
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Data simulasi</span>
        @endif
    </form>
</div>

{{-- ===== KPI CARDS ===== --}}
<div class="grid gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
    <x-farmpro.kpi tone="green" icon="bird"      label="Total Ayam Hidup"       :value="number_format($k['live_population'], 0, ',', '.')" unit="ekor" :delta="$k['live_growth']"    :sub="$k['commodity_count'].' Komoditas'" />
    <x-farmpro.kpi tone="blue"  icon="warehouse"  label="Batch Aktif"            :value="$k['active_batches'].' Batch'"  sub="Seluruh Kandang" />
    <x-farmpro.kpi tone="green" icon="coins"      label="Pendapatan Bulan Ini"   :value="$rp($k['income'])"              :delta="$k['income_growth']" />
    <x-farmpro.kpi tone="amber" icon="wallet"     label="Pengeluaran Bulan Ini"  :value="$rp($k['expense'])"             :delta="$k['expense_growth']" />
</div>

{{-- ===== CHART ROW ===== --}}
<div class="mt-5 grid gap-4 xl:grid-cols-3">
    <x-farmpro.card title="Grafik Performa Peternakan" icon="chart-line" class="xl:col-span-2" x-data="{ tab: 'populasi' }">
        <div class="mb-3 flex gap-1 p-1 bg-slate-100 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600/50 w-fit text-xs">
            @foreach (['populasi' => 'Populasi', 'produksi' => 'Produksi', 'keuangan' => 'Keuangan'] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'; window.setTab('{{ $key }}')"
                        :class="tab === '{{ $key }}' ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
                        class="rounded-lg px-3 py-1 font-bold transition">{{ $label }}</button>
            @endforeach
        </div>
        <div class="h-64"><canvas id="perfChart"></canvas></div>
    </x-farmpro.card>

    <x-farmpro.card title="Populasi per Komoditas" icon="layers">
        <div class="flex flex-wrap items-center gap-4">
            <div class="relative h-40 w-40 shrink-0 mx-auto">
                <canvas id="popChart"></canvas>
                <div class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                    <div>
                        <p class="text-xl font-black text-slate-900">{{ number_format($k['live_population'], 0, ',', '.') }}</p>
                        <p class="text-[11px] text-slate-500">Ekor</p>
                    </div>
                </div>
            </div>
            <ul class="flex-1 space-y-2 text-xs min-w-0">
                @foreach ($d['population_by_commodity'] as $i => $p)
                    <li class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full flex-shrink-0 {{ $dot[$i] ?? 'bg-slate-300' }}"></span>
                        <span class="flex-1 truncate text-slate-600">{{ $p['name'] }}</span>
                        <span class="font-bold text-slate-800">{{ number_format($p['total'], 0, ',', '.') }}</span>
                        <span class="w-9 text-right text-slate-400">{{ $p['pct'] }}%</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </x-farmpro.card>
</div>

{{-- ===== FINANCE / ACTIVITY / ALERTS ===== --}}
<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Ringkasan Keuangan" icon="chart-column">
        <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ $rp($k['profit']) }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Selisih pemasukan bulan ini</p>
        <dl class="space-y-2.5 text-sm border-t border-slate-100 dark:border-slate-700/60 pt-3">
            <div class="flex justify-between items-center">
                <dt class="text-slate-500 dark:text-slate-400">Pendapatan</dt>
                <dd class="font-bold text-emerald-700 dark:text-emerald-400">{{ $rp($k['income']) }}</dd>
            </div>
            <div class="flex justify-between items-center">
                <dt class="text-slate-500 dark:text-slate-400">Pengeluaran</dt>
                <dd class="font-bold text-slate-800 dark:text-slate-200">{{ $rp($k['expense']) }}</dd>
            </div>
            @if (! is_null($k['profit_growth']))
                <div class="flex justify-between items-center">
                    <dt class="text-slate-500 dark:text-slate-400">Dibanding bulan lalu</dt>
                    <dd class="font-bold {{ $k['profit_growth'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $k['profit_growth'] >= 0 ? '↑' : '↓' }} {{ abs($k['profit_growth']) }}%
                    </dd>
                </div>
            @endif
        </dl>
    </x-farmpro.card>

    <x-farmpro.card title="Aktivitas Terbaru" icon="clock" :link="route('operational.daily')">
        <ul class="divide-y divide-slate-100">
            @forelse ($d['activities'] as $a)
                <li class="flex items-start justify-between gap-3 py-2.5">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $a['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $a['sub'] }}</p>
                    </div>
                    <span class="text-xs text-slate-400 whitespace-nowrap mt-0.5">{{ $a['time'] }}</span>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">
                    <i data-lucide="inbox" class="h-8 w-8 mx-auto text-slate-300 mb-2"></i>
                    Belum ada aktivitas.<br>Mulai dengan input harian.
                </li>
            @endforelse
        </ul>
    </x-farmpro.card>

    <x-farmpro.card class="border-amber-200 dark:border-amber-800/40 bg-gradient-to-br from-amber-50 to-white dark:from-amber-900/20 dark:to-slate-800">
        <h3 class="flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-400 mb-2">
            <i data-lucide="triangle-alert" class="h-4 w-4 text-amber-600 dark:text-amber-400"></i>
            Perlu Diperhatikan
        </h3>
        @if ($k['low_stock'])
            <p class="text-sm text-slate-700 dark:text-slate-300 mt-2">
                <span class="font-bold text-amber-700 dark:text-amber-400">{{ $k['low_stock'] }} barang</span> mendekati atau di bawah batas stok minimum.
            </p>
            <a href="{{ route('inventory.category', 'pakan') }}"
               class="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-amber-400 dark:border-amber-600 px-4 py-2 text-xs font-bold text-amber-800 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">
                <i data-lucide="package-search" class="h-3.5 w-3.5"></i> Lihat Stok
            </a>
        @else
            <div class="mt-3 flex items-center gap-2 text-sm text-emerald-700 dark:text-emerald-400">
                <i data-lucide="check-circle" class="h-4 w-4 text-emerald-500"></i>
                Tidak ada peringatan saat ini.
            </div>
        @endif
    </x-farmpro.card>
</div>

{{-- ===== BATCH LIST & SCHEDULE ===== --}}
<div class="mt-4 grid gap-4 xl:grid-cols-3">
    <x-farmpro.card title="Daftar Batch Ternak" icon="layers" :link="route('batches.index')" class="xl:col-span-2">
        <div class="overflow-x-auto -mx-1">
            <table class="w-full min-w-[34rem] text-left text-xs">
                <thead class="border-b border-slate-100 dark:border-slate-700/60 text-slate-500 dark:text-slate-400 bg-slate-50/80 dark:bg-slate-700/40">
                    <tr>
                        @foreach (['Kode Batch', 'Komoditas', 'Kandang', 'Mulai', 'Populasi', 'Umur', 'Status'] as $h)
                            <th class="pb-2 pt-1 pr-4 pl-2 font-bold uppercase tracking-wide text-[10px]">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    @forelse ($d['batches'] as $b)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition">
                            <td class="py-2.5 pr-4 pl-2 font-bold text-slate-800 dark:text-slate-100">{{ $b['code'] }}</td>
                            <td class="pr-4 text-slate-600 dark:text-slate-400">{{ $b['commodity'] }}</td>
                            <td class="pr-4 text-slate-600 dark:text-slate-400">{{ $b['coop'] }}</td>
                            <td class="pr-4 text-slate-600 dark:text-slate-400">{{ $b['start_date'] }}</td>
                            <td class="pr-4 font-semibold text-slate-700 dark:text-slate-300">{{ number_format($b['population'], 0, ',', '.') }}</td>
                            <td class="pr-4 text-slate-600 dark:text-slate-400">{{ $b['age_days'] }} hr</td>
                            <td>
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700">
                                    {{ $b['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-slate-500">
                                <i data-lucide="list-x" class="h-8 w-8 mx-auto text-slate-300 mb-2"></i>
                                Belum ada batch aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-farmpro.card>

    <x-farmpro.card title="Jadwal Mendatang" icon="calendar" :link="route('health.index')">
        <ul class="divide-y divide-slate-100">
            @forelse ($d['schedules'] as $s)
                <li class="py-2.5">
                    <p class="text-sm font-semibold text-slate-800">{{ $s['title'] }} – {{ $s['sub'] }}</p>
                    <p class="text-xs text-slate-500">{{ $s['due'] }}</p>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">
                    <i data-lucide="calendar-check" class="h-8 w-8 mx-auto text-slate-300 mb-2"></i>
                    Tidak ada jadwal mendatang.
                </li>
            @endforelse
        </ul>
    </x-farmpro.card>
</div>
@endsection

@push('scripts')
<script>
    const charts = @json($d['charts']);
    const palette = ['#16a34a', '#f59e0b', '#38bdf8', '#14b8a6'];
    const money = (v) => 'Rp ' + Number(v).toLocaleString('id-ID');
    const toSets = (sets) => sets.map((s, i) => ({
        label: s.label,
        data: s.data,
        borderColor: palette[i],
        backgroundColor: palette[i] + '22',
        fill: true,
        tension: .4,
        pointRadius: 3,
        borderWidth: 2.5
    }));

    const perf = new Chart(document.getElementById('perfChart'), {
        type: 'line',
        data: { labels: charts.labels, datasets: toSets(charts.populasi) },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: { size: 11, weight: '600' },
                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569' } } },
            scales: {
                y: { beginAtZero: true,
                     grid: { color: document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,0.12)' : '#f1f5f9' },
                     ticks: { font: { size: 11 }, color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } },
                x: { grid: { display: false },
                     ticks: { font: { size: 11 }, color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b' } }
            }
        }
    });

    window.setTab = (key) => {
        perf.data.datasets = toSets(charts[key]);
        perf.options.plugins.tooltip = {
            callbacks: {
                label: (c) => c.dataset.label + ': ' + (key === 'keuangan' ? money(c.parsed.y) : c.parsed.y.toLocaleString('id-ID'))
            }
        };
        perf.update();
    };

    const pop = @json($d['population_by_commodity']);
    new Chart(document.getElementById('popChart'), {
        type: 'doughnut',
        data: {
            labels: pop.map(p => p.name),
            datasets: [{ data: pop.map(p => p.total), backgroundColor: ['#16a34a', '#2dd4bf', '#fcd34d', '#38bdf8'], borderWidth: 0, hoverOffset: 4 }]
        },
        options: { cutout: '70%', plugins: { legend: { display: false } } }
    });
</script>
@endpush
