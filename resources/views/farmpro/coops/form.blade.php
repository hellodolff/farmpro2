@extends('farmpro.layout')
@section('title', $coop->exists ? 'Ubah Kandang' : 'Tambah Kandang')

@section('content')
<h1 class="mb-5 text-3xl font-bold text-slate-900">{{ $coop->exists ? 'Ubah' : 'Tambah' }} Kandang</h1>

<x-farmpro.card class="max-w-3xl">
    <form method="POST" action="{{ $coop->exists ? route('coops.update', $coop) : route('coops.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf @if ($coop->exists) @method('PUT') @endif
        <x-farmpro.field name="code" label="Kode" :value="$coop->code" required />
        <x-farmpro.field name="name" label="Nama" :value="$coop->name" required />
        <x-farmpro.field name="capacity" type="number" label="Kapasitas maksimum (ekor)" :value="$coop->capacity" required />
        <x-farmpro.field name="type" label="Tipe kandang" :value="$coop->type" hint="Mis. postal, closed house, baterai." />
        <x-farmpro.field name="location" label="Lokasi" :value="$coop->location" />
        @if ($inUse)
            <div><p class="mb-1 text-sm font-medium text-slate-700">Status</p><p class="rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800">Terisi — dipakai batch aktif (diatur otomatis).</p></div>
        @else
            <x-farmpro.field name="status" type="select" label="Status" :options="['available' => 'Tersedia', 'maintenance' => 'Perawatan']" :value="$coop->status === 'occupied' ? 'available' : $coop->status" />
        @endif
        <x-farmpro.field name="sanitation_notes" type="textarea" label="Catatan sanitasi" :value="$coop->sanitation_notes" class="sm:col-span-2" />
        <div class="flex gap-3 sm:col-span-2">
            <button class="rounded-lg bg-brand-600 px-5 py-2 text-sm font-medium text-white hover:bg-brand-700">Simpan</button>
            <a href="{{ route('coops.index') }}" class="rounded-lg border border-slate-200 px-5 py-2 text-sm hover:bg-slate-50">Batal</a>
        </div>
    </form>
</x-farmpro.card>
@endsection
