@extends('farmpro.layout')
@section('title','Input Harian')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Input Harian &amp; Panen</h1><p class="text-sm text-slate-500">Catatan operasional harian batch.</p></div>
<div class="grid gap-4 lg:grid-cols-2">
<x-farmpro.card title="Catatan harian" icon="clipboard-plus">
<form method="POST" action="{{ route('operational.daily.store') }}" class="grid gap-3 sm:grid-cols-2">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
<x-farmpro.field name="record_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="deaths" type="number" label="Kematian" :value="0" />
<x-farmpro.field name="culls" type="number" label="Afkir" :value="0" />
<x-farmpro.field name="feed_kg" type="number" label="Pakan (kg)" :step="0.01" />
<x-farmpro.field name="feed_item_id" type="select" label="Barang pakan" :options="$feedItems->pluck('name','id')" />
<x-farmpro.field name="avg_weight_g" type="number" label="Bobot rata-rata (g)" :step="0.01" />
<x-farmpro.field name="temperature" type="number" label="Suhu (°C)" :step="0.1" />
<x-farmpro.field name="humidity" type="number" label="Kelembapan (%)" />
<x-farmpro.field name="total_eggs" type="number" label="Total telur" />
<x-farmpro.field name="notes" type="textarea" label="Catatan" class="sm:col-span-2" />
<div class="sm:col-span-2"><button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan catatan</button></div>
</form></x-farmpro.card>
<x-farmpro.card title="Panen &amp; penjualan" icon="shopping-basket">
<form method="POST" action="{{ route('operational.harvest.store') }}" class="grid gap-3">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
<x-farmpro.field name="harvest_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="heads" type="number" label="Jumlah ekor" required />
<x-farmpro.field name="total_weight_kg" type="number" label="Bobot total kotor (kg)" :step="0.01" required />
<x-farmpro.field name="weight_basis" type="select" label="Dasar bobot" :options="['live'=>'Hidup','carcass'=>'Karkas']" required />
<x-farmpro.field name="price_per_unit" type="number" label="Harga per kg" :step="0.01" required />
<x-farmpro.field name="deductions" type="number" label="Potongan bobot (kg)" :value="0" :step="0.01" />
<x-farmpro.field name="transport_cost" type="number" label="Biaya angkut" :value="0" :step="0.01" />
<x-farmpro.field name="notes" type="textarea" label="Catatan" />
<button class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan panen &amp; penjualan</button>
</form></x-farmpro.card>
</div>
@endsection
