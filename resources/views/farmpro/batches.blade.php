@extends('farmpro.layout')
@section('title', 'Batch Ternak')

@section('content')
<h1 class="text-3xl font-bold text-slate-900">Batch Ternak</h1>
<p class="mb-5 text-slate-500">Semua batch beserta status dan populasi berjalan.</p>

<x-farmpro.card>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="border-b border-slate-100 text-slate-500"><tr>
                @foreach (['Kode', 'Komoditas', 'Kandang', 'Mulai', 'Populasi Awal', 'Populasi Sekarang', 'Umur', 'Status'] as $h)<th class="pb-2 pr-3 font-medium">{{ $h }}</th>@endforeach
            </tr></thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($batches as $b)
                    @php $tone = ['active' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-slate-100 text-slate-600', 'planned' => 'bg-sky-100 text-sky-700', 'cancelled' => 'bg-red-100 text-red-700'][$b->status]; @endphp
                    <tr><td class="py-2.5 pr-3 font-medium text-slate-800">{{ $b->code }}</td><td class="pr-3">{{ $b->commodity->name }}</td><td class="pr-3">{{ $b->coop->name }}</td>
                        <td class="pr-3">{{ $b->start_date->format('d M Y') }}</td><td class="pr-3">{{ number_format($b->initial_population, 0, ',', '.') }}</td>
                        <td class="pr-3">{{ number_format($b->current_population, 0, ',', '.') }}</td><td class="pr-3">{{ $b->age_days }} hari</td>
                        <td><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tone }}">{{ ['active' => 'Aktif', 'completed' => 'Selesai', 'planned' => 'Direncanakan', 'cancelled' => 'Dibatalkan'][$b->status] }}</span></td></tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-500">Belum ada batch. Buat batch pertama lewat aplikasi mobile atau API.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $batches->links() }}</div>
</x-farmpro.card>
@endsection
