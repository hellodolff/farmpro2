@extends('farmpro.layout')
@section('title','Inventaris')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Inventaris &amp; Stok</h1><p class="text-sm text-slate-500">Nilai stok memakai rata-rata tertimbang.</p></div>
<div class="grid gap-4 lg:grid-cols-3"><x-farmpro.card title="Stok saat ini" icon="package" class="lg:col-span-2">
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="py-2">Kode</th><th>Nama</th><th>Kategori</th><th>Stok</th><th>Biaya rata-rata</th></tr></thead><tbody>@foreach($items as $i)<tr class="border-b"><td class="py-2 font-mono">{{ $i->code }}</td><td>{{ $i->name }}</td><td>{{ ucfirst($i->category) }}</td><td class="{{ $i->is_low ? 'font-bold text-red-600' : '' }}">{{ number_format($i->stock,2,',','.').' '.$i->base_unit }}</td><td>Rp {{ number_format($i->avg_cost,0,',','.') }}</td></tr>@endforeach</tbody></table></div>
</x-farmpro.card>
<x-farmpro.card title="Barang masuk" icon="package-plus"><div class="space-y-4">@foreach($items as $i)<details class="rounded-lg border p-3"><summary class="cursor-pointer text-sm font-semibold">{{ $i->name }}</summary><form method="POST" action="{{ route('inventory.purchase',$i) }}" class="mt-3 space-y-2">@csrf<x-farmpro.field name="quantity" type="number" label="Jumlah" :step="0.001" required/><x-farmpro.field name="unit_cost" type="number" label="Harga per unit" :step="0.01" required/><x-farmpro.field name="movement_date" type="date" label="Tanggal" :value="now()->toDateString()" required/><button class="rounded-lg bg-brand-600 px-3 py-2 text-sm text-white">Catat masuk</button></form></details>@endforeach</div></x-farmpro.card></div>
@endsection
