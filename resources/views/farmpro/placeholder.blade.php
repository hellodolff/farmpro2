@extends('farmpro.layout')
@section('title', Str::headline($page ?? $group))

@section('content')
<h1 class="text-3xl font-bold text-slate-900">{{ Str::headline($page ?? $group) }}</h1>
<p class="mb-5 text-slate-500">{{ Str::headline($group) }}</p>
<x-farmpro.card class="py-16 text-center">
    <p class="text-lg font-semibold text-slate-800">Modul ini dikerjakan di tahap berikutnya</p>
    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">Lihat urutan pengerjaan di docs/LANGKAH_PENGEMBANGAN.md. Data dan API untuk modul ini sudah tersedia di database.</p>
    <a href="{{ route('dashboard') }}" class="mt-5 inline-block rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Kembali ke Dashboard</a>
</x-farmpro.card>
@endsection
