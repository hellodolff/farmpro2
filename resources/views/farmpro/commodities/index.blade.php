@extends('farmpro.layout')
@section('title', 'Komoditas')

@section('content')
@php $isAdmin = auth()->user()->role === 'admin'; @endphp
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Komoditas</h1>
        <p class="text-slate-500">Master jenis ayam dan target performa. Nilai target adalah referensi awal yang dapat diubah.</p>
    </div>
    @if ($isAdmin)<a href="{{ route('commodities.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">+ Tambah Komoditas</a>@endif
</div>

<x-farmpro.card>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
            <thead class="border-b border-slate-100 text-slate-500"><tr>
                @foreach (['Kode', 'Nama', 'Satuan', 'Target FCR', 'Mortalitas', 'Bobot Panen', 'Siklus', 'Batch', ''] as $h)<th class="pb-2 pr-3 font-medium">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($commodities as $c)
                    <tr>
                        <td class="py-2.5 pr-3 font-medium text-slate-800">{{ $c->code }}</td><td class="pr-3">{{ $c->name }}</td><td class="pr-3">{{ $c->product_unit }}</td>
                        <td class="pr-3">{{ $c->target_fcr_min ? $c->target_fcr_min.' – '.$c->target_fcr_max : '-' }}</td>
                        <td class="pr-3">{{ $c->target_mortality_pct !== null ? $c->target_mortality_pct.'%' : '-' }}</td>
                        <td class="pr-3">{{ $c->target_harvest_weight_kg ? $c->target_harvest_weight_kg.' kg' : '-' }}</td>
                        <td class="pr-3">{{ $c->target_cycle_days ? $c->target_cycle_days.' hari' : '-' }}</td>
                        <td class="pr-3">{{ $c->batches_count }}</td>
                        <td class="whitespace-nowrap text-right">
                            @if ($isAdmin)
                                <a href="{{ route('commodities.edit', $c) }}" class="text-brand-700 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('commodities.destroy', $c) }}" class="ml-3 inline" onsubmit="return confirm('Hapus komoditas {{ $c->name }}?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Hapus</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-slate-500">Belum ada komoditas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-farmpro.card>
@endsection
