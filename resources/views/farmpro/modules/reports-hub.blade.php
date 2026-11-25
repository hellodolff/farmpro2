@extends('farmpro.layout')
@section('title','Laporan')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Pusat Laporan</h1><p class="text-sm text-slate-500">Pilih jenis laporan dan filter tanggal, bulan, batch, atau komoditas.</p></div>

<x-farmpro.card title="Filter laporan" icon="filter">
<form method="GET" action="{{ route('reports.hub') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <x-farmpro.field name="jenis" type="select" label="Jenis laporan" :options="['harian'=>'Harian','bulanan'=>'Bulanan','per-batch'=>'Per Batch','populasi'=>'Populasi','produksi'=>'Produksi','inventaris'=>'Inventaris','keuangan'=>'Keuangan']" :value="$jenis" />
    <x-farmpro.field name="bulan" type="month" label="Rentang bulan" :value="request('bulan')" />
    <x-farmpro.field name="tanggal_mulai" type="date" label="Tanggal mulai" :value="request('tanggal_mulai')" />
    <x-farmpro.field name="tanggal_selesai" type="date" label="Tanggal selesai" :value="request('tanggal_selesai')" />
    <x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" :value="request('batch_id')" />
    <div class="lg:col-span-2"><x-farmpro.field name="commodity_id" type="select" label="Komoditas" :options="$commodities->pluck('name','id')" :value="request('commodity_id')" /></div>
    <div class="flex items-end gap-2 lg:col-span-3"><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Terapkan filter</button><a href="{{ route('reports.hub') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600">Reset</a></div>
</form>
</x-farmpro.card>

<div class="mt-4">
<x-farmpro.card title="{{ $title }}" icon="file-text">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">Periode: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
        <div class="flex gap-2">
            <a href="{{ route('reports.index', array_merge(request()->all(), ['format'=>'csv'])) }}" class="rounded-lg border border-brand-600 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50">Ekspor CSV</a>
            <a href="{{ route('reports.index', array_merge(request()->all(), ['format'=>'pdf'])) }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Ekspor PDF</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
            <thead><tr class="border-b">@foreach($columns as $column)<th class="py-2 pr-3 font-medium text-slate-600">{{ $column }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-b">@foreach($columns as $column)<td class="py-2 pr-3">{{ $row[$column] ?? '-' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="py-8 text-center text-slate-500">Tidak ada data pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-farmpro.card>
</div>
@endsection
