@extends('farmpro.layout')
@section('title','Laporan')
@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Laporan Keuangan</h1>
        <p class="text-sm text-slate-500">Ekspor laporan keuangan dalam format CSV atau PDF.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('reports.index',['format'=>'csv']) }}" class="rounded-lg border border-brand-600 px-4 py-2 text-sm font-medium text-brand-700 hover:bg-brand-50">Ekspor CSV</a>
        <a href="{{ route('reports.index',['format'=>'pdf']) }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Ekspor PDF</a>
    </div>
</div>
<x-farmpro.card>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b">
                    <th class="py-2">Tanggal</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Jumlah</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr class="border-b">
                    <td class="py-2">{{ $r->trx_date }}</td>
                    <td>{{ $r->type }}</td>
                    <td>{{ $r->category }}</td>
                    <td>Rp {{ number_format($r->amount,0,',','.') }}</td>
                    <td>{{ $r->description }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-6 text-center text-slate-500">Belum ada data.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-farmpro.card>
@endsection
