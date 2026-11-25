@extends('farmpro.layout')
@section('title', 'Batch Ternak')

@section('content')
@php
    $canWrite = in_array(auth()->user()->role, ['admin', 'operator']);
    $label = ['active' => 'Aktif', 'completed' => 'Selesai', 'planned' => 'Direncanakan', 'cancelled' => 'Dibatalkan'];
    $tone = ['active' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-slate-100 text-slate-600', 'planned' => 'bg-sky-100 text-sky-700', 'cancelled' => 'bg-red-100 text-red-700'];
@endphp
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Batch Ternak</h1>
        <p class="text-slate-500">Semua batch beserta status dan populasi berjalan.</p>
    </div>
    @if ($canWrite)<a href="{{ route('batches.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">+ Batch Baru</a>@endif
</div>

<x-farmpro.card>
    <form method="GET" class="mb-4 flex flex-wrap gap-2 text-sm">
        <input name="q" value="{{ request('q') }}" placeholder="Cari kode batch" class="rounded-lg border border-slate-200 px-3 py-1.5">
        <select name="commodity_id" class="rounded-lg border border-slate-200 px-3 py-1.5"><option value="">Semua komoditas</option>@foreach ($commodities as $id => $n)<option value="{{ $id }}" @selected(request('commodity_id') == $id)>{{ $n }}</option>@endforeach</select>
        <select name="status" class="rounded-lg border border-slate-200 px-3 py-1.5"><option value="">Semua status</option>@foreach ($label as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
        <button class="rounded-lg bg-slate-800 px-4 py-1.5 text-white">Filter</button>
        @if (request()->hasAny(['q', 'commodity_id', 'status']))<a href="{{ route('batches.index') }}" class="px-2 py-1.5 text-slate-500 hover:underline">Reset</a>@endif
    </form>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[42rem] text-left text-sm">
            <thead class="border-b border-slate-100 text-slate-500"><tr>
                @foreach (['Kode', 'Komoditas', 'Kandang', 'Mulai', 'Populasi Awal', 'Populasi Sekarang', 'Umur', 'Status'] as $h)<th class="pb-2 pr-3 font-medium">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($batches as $b)
                    <tr>
                        <td class="py-2.5 pr-3 font-medium"><a href="{{ route('batches.show', $b) }}" class="text-brand-700 hover:underline">{{ $b->code }}</a></td>
                        <td class="pr-3">{{ $b->commodity->name }}</td><td class="pr-3">{{ $b->coop->name }}</td><td class="pr-3">{{ $b->start_date->format('d M Y') }}</td>
                        <td class="pr-3">{{ number_format($b->initial_population, 0, ',', '.') }}</td><td class="pr-3">{{ number_format($b->current_population, 0, ',', '.') }}</td>
                        <td class="pr-3">{{ $b->age_days }} hari</td>
                        <td><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tone[$b->status] }}">{{ $label[$b->status] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-500">Tidak ada batch yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-farmpro.card>
@endsection
