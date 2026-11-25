@extends('farmpro.layout')
@section('title','Keuangan')
@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div><h1 class="text-2xl font-bold text-slate-900">Ringkasan Keuangan</h1><p class="text-sm text-slate-500">Sumber angka dari transaksi pemasukan dan pengeluaran.</p></div>
    <div class="flex gap-2"><a href="{{ route('finance.income') }}" class="rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Kelola Pemasukan</a><a href="{{ route('finance.expense') }}" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Kelola Pengeluaran</a></div>
</div>
<div class="grid gap-4 sm:grid-cols-3">
    <x-farmpro.card title="Pemasukan"><p class="text-2xl font-bold text-emerald-700">Rp {{ number_format($income,0,',','.') }}</p></x-farmpro.card>
    <x-farmpro.card title="Pengeluaran"><p class="text-2xl font-bold text-red-700">Rp {{ number_format($expense,0,',','.') }}</p></x-farmpro.card>
    <x-farmpro.card title="Laba/rugi"><p class="text-2xl font-bold">Rp {{ number_format($income-$expense,0,',','.') }}</p></x-farmpro.card>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Catat transaksi cepat" icon="receipt" class="lg:col-span-1">
        <form method="POST" action="{{ route('finance.store') }}" class="space-y-3">
            @csrf
            <x-farmpro.field name="trx_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
            <x-farmpro.field name="type" type="select" label="Jenis" :options="['income'=>'Pemasukan','expense'=>'Pengeluaran','transfer'=>'Transfer']" required />
            <x-farmpro.field name="category" label="Kategori" required />
            <x-farmpro.field name="amount" type="number" label="Nominal (Rp)" :step="0.01" required />
            <x-farmpro.field name="payment_method" label="Metode pembayaran" />
            <x-farmpro.field name="description" type="textarea" label="Keterangan" />
            <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Simpan transaksi</button>
        </form>
    </x-farmpro.card>

    <x-farmpro.card title="Transaksi terbaru" icon="history" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[38rem] text-left text-sm">
                <thead><tr class="border-b"><th class="py-2">Tanggal</th><th>Tipe</th><th>Kategori</th><th class="text-right">Jumlah</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @forelse($trx as $t)
                        <tr class="border-b"><td class="py-2">{{ \Illuminate\Support\Carbon::parse($t->trx_date)->format('d M Y') }}</td><td>{{ ucfirst($t->type) }}</td><td>{{ $t->category }}</td><td class="text-right font-semibold">Rp {{ number_format($t->amount,0,',','.') }}</td><td>{{ $t->description ?: '-' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-farmpro.card>
</div>
@endsection
