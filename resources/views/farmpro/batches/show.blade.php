@extends('farmpro.layout')
@section('title', 'Batch '.$batch->code)

@section('content')
@php
    $canWrite = in_array(auth()->user()->role, ['admin', 'operator']);
    $label = ['active' => 'Aktif', 'completed' => 'Selesai', 'planned' => 'Direncanakan', 'cancelled' => 'Dibatalkan'];
    $types = ['death' => 'Kematian', 'cull' => 'Afkir', 'harvest' => 'Panen / terjual', 'transfer_in' => 'Transfer masuk', 'transfer_out' => 'Transfer keluar'];
    $isLayer = $batch->commodity->code === 'layer';
    $rp = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
    $perfRows = [
        'Mortalitas' => $perf['mortality_pct'].'%',
        'Livability' => $perf['livability_pct'].'%',
        'Total pakan' => number_format($perf['feed_kg'], 0, ',', '.').' kg',
        'FCR' => $perf['fcr'] ?? '-',
    ];
    if ($isLayer) {
        $perfRows['HDP terakhir'] = isset($perf['hdp_pct']) ? $perf['hdp_pct'].'%' : '-';
    } else {
        $perfRows['Bobot rata-rata'] = ! empty($perf['avg_weight_g']) ? $perf['avg_weight_g'].' g' : '-';
        $perfRows['IP'] = $perf['ip'] ?? '-';
    }
@endphp

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ route('batches.index') }}" class="text-sm text-brand-700 hover:underline">← Batch Ternak</a>
        <h1 class="text-3xl font-bold text-slate-900">{{ $batch->code }} <span class="ml-2 align-middle rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600">{{ $label[$batch->status] }}</span></h1>
        <p class="text-slate-500">{{ $batch->commodity->name }} • {{ $batch->coop->name }} • mulai {{ $batch->start_date->format('d M Y') }}</p>
    </div>
    @if ($canWrite && $batch->status === 'active')
        <div class="flex gap-2">
            <form method="POST" action="{{ route('batches.finish', $batch) }}" onsubmit="return confirm('Selesaikan batch {{ $batch->code }}?')">@csrf<button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Selesaikan batch</button></form>
            <form method="POST" action="{{ route('batches.cancel', $batch) }}" onsubmit="return confirm('Batalkan batch? Hanya bisa jika belum ada catatan; biaya bibit dibalik.')">@csrf<button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Batalkan</button></form>
        </div>
    @endif
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Ringkasan" icon="info">
        <dl class="space-y-2 text-sm">
            @foreach ([
                'Populasi sekarang' => number_format($batch->current_population, 0, ',', '.').' / '.number_format($batch->initial_population, 0, ',', '.').' ekor',
                'Umur' => $batch->age_days.' hari',
                'Jenis bibit' => strtoupper($batch->seed_type),
                'Harga per ekor' => $rp($batch->price_per_head),
                'Biaya bibit' => $rp($batch->initial_cost),
                'Tanggal selesai' => $batch->end_date?->format('d M Y') ?? '-',
            ] as $k => $v)
                <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold text-slate-800">{{ $v }}</dd></div>
            @endforeach
        </dl>
    </x-farmpro.card>

    <x-farmpro.card title="Performa" icon="activity">
        <dl class="space-y-2 text-sm">
            @foreach ($perfRows as $k => $v)
                <div class="flex justify-between"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold text-slate-800">{{ $v }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-3 text-xs text-slate-400">Dihitung dari catatan harian; "-" berarti belum ada data.</p>
    </x-farmpro.card>

    @if ($canWrite)
    <x-farmpro.card title="Data batch" icon="pencil">
        <form method="POST" action="{{ route('batches.update', $batch) }}" class="space-y-3">
            @csrf @method('PUT')
            <x-farmpro.field name="supplier_id" type="select" label="Pemasok" :options="$suppliers" :value="$batch->supplier_id" />
            <x-farmpro.field name="notes" type="textarea" label="Catatan" :value="$batch->notes" />
            <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm text-white">Simpan</button>
        </form>
        <p class="mt-3 text-xs text-slate-400">Kode, kandang, komoditas, dan populasi awal dikunci agar histori konsisten.</p>
    </x-farmpro.card>
    @endif
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Riwayat mutasi populasi" icon="arrow-left-right" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[28rem] text-left text-sm">
                <thead class="border-b border-slate-100 text-slate-500"><tr><th class="pb-2 font-medium">Tanggal</th><th class="pb-2 font-medium">Jenis</th><th class="pb-2 font-medium">Jumlah</th><th class="pb-2 font-medium">Alasan</th></tr></thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($movements as $m)
                        <tr><td class="py-2">{{ \Illuminate\Support\Carbon::parse($m->movement_date)->format('d M Y') }}</td><td>{{ $types[$m->type] }}</td><td>{{ number_format($m->quantity, 0, ',', '.') }}</td><td class="text-slate-500">{{ $m->reason ?: '-' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-slate-500">Belum ada mutasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-farmpro.card>

    @if ($canWrite && $batch->status === 'active')
    <x-farmpro.card title="Catat mutasi" icon="plus">
        <form method="POST" action="{{ route('batches.movement', $batch) }}" class="space-y-3">
            @csrf
            <x-farmpro.field name="type" type="select" label="Jenis" :options="['death' => 'Kematian', 'cull' => 'Afkir', 'harvest' => 'Panen / terjual']" required />
            <x-farmpro.field name="quantity" type="number" label="Jumlah (ekor)" required />
            <x-farmpro.field name="movement_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
            <x-farmpro.field name="reason" label="Alasan (opsional)" />
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Simpan mutasi</button>
        </form>
        <p class="mt-3 text-xs text-slate-400">Detail panen (bobot, harga, pembeli) dicatat di modul Panen &amp; Penjualan (Tahap 2).</p>
    </x-farmpro.card>
    @endif
</div>
@endsection
