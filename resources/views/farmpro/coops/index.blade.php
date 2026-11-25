@extends('farmpro.layout')
@section('title', 'Kandang')

@section('content')
@php
    $isAdmin = auth()->user()->role === 'admin';
    $status = ['available' => ['Tersedia', 'bg-emerald-100 text-emerald-700'], 'occupied' => ['Terisi', 'bg-sky-100 text-sky-700'], 'maintenance' => ['Perawatan', 'bg-amber-100 text-amber-800']];
@endphp
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Kandang</h1>
        <p class="text-slate-500">Kapasitas dan keterisian kandang. Status "Terisi" diatur otomatis oleh batch aktif.</p>
    </div>
    @if ($isAdmin)<a href="{{ route('coops.create') }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">+ Tambah Kandang</a>@endif
</div>

<x-farmpro.card>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="border-b border-slate-100 text-slate-500"><tr>
                @foreach (['Kode', 'Nama', 'Tipe', 'Keterisian', 'Batch Aktif', 'Status', ''] as $h)<th class="pb-2 pr-3 font-medium">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($coops as $c)
                    @php $used = (int) $c->used_population; $pct = min(100, (int) round($used / max(1, $c->capacity) * 100)); @endphp
                    <tr>
                        <td class="py-2.5 pr-3 font-medium text-slate-800">{{ $c->code }}</td><td class="pr-3">{{ $c->name }}</td><td class="pr-3">{{ $c->type ?: '-' }}</td>
                        <td class="w-56 pr-3">
                            <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full {{ $used > $c->capacity ? 'bg-red-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div></div>
                            <p class="mt-1 text-xs text-slate-500">{{ number_format($used, 0, ',', '.') }} / {{ number_format($c->capacity, 0, ',', '.') }} ekor</p>
                        </td>
                        <td class="pr-3">{{ $c->active_batches }}</td>
                        <td class="pr-3"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status[$c->status][1] }}">{{ $status[$c->status][0] }}</span></td>
                        <td class="whitespace-nowrap text-right">
                            @if ($isAdmin)
                                <a href="{{ route('coops.edit', $c) }}" class="text-brand-700 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('coops.destroy', $c) }}" class="ml-3 inline" onsubmit="return confirm('Hapus {{ $c->name }}?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Hapus</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">Belum ada kandang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-farmpro.card>
@endsection
