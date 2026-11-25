@extends('farmpro.layout')
@section('title', $commodity->exists ? 'Ubah Komoditas' : 'Tambah Komoditas')

@section('content')
<h1 class="mb-5 text-3xl font-bold text-slate-900">{{ $commodity->exists ? 'Ubah' : 'Tambah' }} Komoditas</h1>

<x-farmpro.card class="max-w-3xl">
    <form method="POST" action="{{ $commodity->exists ? route('commodities.update', $commodity) : route('commodities.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf @if ($commodity->exists) @method('PUT') @endif
        <x-farmpro.field name="code" label="Kode" :value="$commodity->code" :required="! $commodity->exists" :readonly="$commodity->exists" hint="Huruf kecil tanpa spasi, mis. broiler. Tidak dapat diubah setelah dibuat." />
        <x-farmpro.field name="name" label="Nama" :value="$commodity->name" required />
        <x-farmpro.field name="product_unit" type="select" label="Satuan produksi" :options="['kg' => 'kg (daging)', 'butir' => 'butir (telur)']" :value="$commodity->product_unit" required />
        <x-farmpro.field name="effective_from" type="date" label="Target berlaku sejak" :value="$commodity->effective_from" />
        <x-farmpro.field name="target_fcr_min" type="number" step="0.01" label="FCR minimum" :value="$commodity->target_fcr_min" />
        <x-farmpro.field name="target_fcr_max" type="number" step="0.01" label="FCR maksimum" :value="$commodity->target_fcr_max" />
        <x-farmpro.field name="target_mortality_pct" type="number" step="0.01" label="Target mortalitas (%)" :value="$commodity->target_mortality_pct" />
        <x-farmpro.field name="target_harvest_weight_kg" type="number" step="0.001" label="Target bobot panen (kg)" :value="$commodity->target_harvest_weight_kg" />
        <x-farmpro.field name="target_cycle_days" type="number" label="Durasi siklus (hari)" :value="$commodity->target_cycle_days" />
        <x-farmpro.field name="description" type="textarea" label="Deskripsi" :value="$commodity->description" class="sm:col-span-2" />
        <p class="text-xs text-slate-500 sm:col-span-2">Target hanyalah acuan awal; nilai aktual dipengaruhi strain, pakan, lingkungan, dan manajemen. Perubahan target tercatat di audit log.</p>
        <div class="flex gap-3 sm:col-span-2">
            <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700">Simpan</button>
            <a href="{{ route('commodities.index') }}" class="rounded-lg border border-slate-200 px-5 py-2 text-sm hover:bg-slate-50">Batal</a>
        </div>
    </form>
</x-farmpro.card>
@endsection
