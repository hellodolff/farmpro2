@extends('farmpro.layout')
@section('title','Kesehatan Ternak')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Kesehatan Ternak</h1><p class="text-sm text-slate-500">Pencatatan jadwal dan riwayat. Bukan diagnosa medis.</p></div>
<div class="grid gap-4 lg:grid-cols-3">
<x-farmpro.card title="Buat jadwal" icon="calendar-plus" class="lg:col-span-1">
<form method="POST" action="{{ route('health.store') }}" class="space-y-3">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
<x-farmpro.field name="title" label="Judul kegiatan" required />
<x-farmpro.field name="type" type="select" label="Jenis" :options="['vaccine'=>'Vaksin','medicine'=>'Obat','vitamin'=>'Vitamin']" required />
<x-farmpro.field name="due_date" type="date" label="Tanggal jatuh tempo" required />
<x-farmpro.field name="priority" type="select" label="Prioritas" :options="['low'=>'Rendah','normal'=>'Normal','high'=>'Tinggi']" required />
<button class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Simpan jadwal</button></form></x-farmpro.card>
<x-farmpro.card title="Jadwal mendatang" icon="calendar-days" class="lg:col-span-2">
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="py-2">Jatuh tempo</th><th>Batch</th><th>Kegiatan</th><th>Status</th></tr></thead><tbody>@forelse($schedules as $s)<tr class="border-b"><td class="py-2">{{ $s->due_date }}</td><td>{{ $s->batch?->code }}</td><td>{{ $s->title }}</td><td>{{ ucfirst($s->status) }}</td></tr>@empty<tr><td colspan="4" class="py-6 text-center text-slate-500">Belum ada jadwal.</td></tr>@endforelse</tbody></table></div>
</x-farmpro.card></div>

<x-farmpro.card title="Catat riwayat kesehatan" icon="clipboard-plus" class="mt-4">
<form method="POST" action="{{ route('health.record.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
<x-farmpro.field name="record_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="type" type="select" label="Jenis intervensi" :options="['vaccine'=>'Vaksin','medicine'=>'Obat','vitamin'=>'Vitamin','supplement'=>'Suplemen','other'=>'Lainnya']" required />
<x-farmpro.field name="product_name" label="Nama produk" />
<x-farmpro.field name="dose" label="Dosis" />
<x-farmpro.field name="method" label="Metode/rute" />
<x-farmpro.field name="lot_number" label="Nomor lot" />
<x-farmpro.field name="expiry_date" type="date" label="Tanggal kedaluwarsa" />
<x-farmpro.field name="withdrawal_days" type="number" label="Masa henti (hari)" />
<x-farmpro.field name="inventory_item_id" type="select" label="Barang inventaris" :options="\App\Models\InventoryItem::whereIn('category',['vaccine','medicine','vitamin','sanitation'])->where('is_active',true)->pluck('name','id')" />
<x-farmpro.field name="quantity" type="number" label="Jumlah dipakai" :step="0.001" />
<x-farmpro.field name="cost" type="number" label="Biaya manual" :step="0.01" :value="0" />
<x-farmpro.field name="performed_by" label="Pelaksana" />
<x-farmpro.field name="symptoms" type="textarea" label="Gejala/keluhan" />
<x-farmpro.field name="suspected_disease" label="Dugaan kondisi (bukan diagnosis otomatis)" />
<x-farmpro.field name="cases" type="number" label="Jumlah kasus" />
<x-farmpro.field name="notes" type="textarea" label="Catatan tindak lanjut" class="sm:col-span-2 lg:col-span-3" />
<div class="sm:col-span-2 lg:col-span-3"><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Simpan catatan kesehatan</button></div>
</form>
</x-farmpro.card>
@endsection
