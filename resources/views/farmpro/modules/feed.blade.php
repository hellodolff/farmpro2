@extends('farmpro.layout')
@section('title','Konsumsi Pakan')
@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Konsumsi Pakan</h1>
    <p class="mt-1 text-sm text-slate-500">Catat pemakaian pakan batch. Stok dan biaya batch diperbarui otomatis.</p>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Catat konsumsi" icon="wheat" class="lg:col-span-1">
        <form method="POST" action="{{ route('feed.store') }}" class="space-y-3">
            @csrf
            <x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
            <x-farmpro.field name="inventory_item_id" type="select" label="Jenis pakan" :options="$feedItems->mapWithKeys(fn ($item) => [$item->id => $item->name.' — '.number_format($item->stock,2,',','.').' '.$item->base_unit])->all()" required />
            <x-farmpro.field name="record_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
            <x-farmpro.field name="feed_phase" type="select" label="Fase pakan" :options="['starter'=>'Starter','grower'=>'Grower','finisher'=>'Finisher','layer'=>'Layer']" required />
            <x-farmpro.field name="feed_kg" type="number" label="Jumlah (kg)" :step="0.01" required />
            <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Simpan konsumsi</button>
        </form>
    </x-farmpro.card>

    <x-farmpro.card title="Riwayat konsumsi" icon="history" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="border-b border-slate-100 text-slate-500">
                    <tr><th class="pb-2 font-medium">Tanggal</th><th class="pb-2 font-medium">Batch</th><th class="pb-2 font-medium">Fase</th><th class="pb-2 font-medium">Pakan</th><th class="pb-2 font-medium text-right">Jumlah</th><th class="pb-2 font-medium text-right">Biaya</th><th class="pb-2"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($records as $record)
                        <tr>
                            <td class="py-3">{{ \Illuminate\Support\Carbon::parse($record->record_date)->format('d M Y') }}</td>
                            <td class="font-medium text-slate-800">{{ $record->batch?->code ?? '-' }}</td>
                            <td>{{ ucfirst($record->feed_phase ?: '-') }}</td>
                            <td class="text-slate-600">{{ \App\Models\InventoryItem::find($record->inventory_item_id)?->name ?? '-' }}</td>
                            <td class="text-right">{{ number_format($record->feed_kg, 2, ',', '.') }} kg</td>
                            <td class="text-right">Rp {{ number_format($record->cost, 0, ',', '.') }}</td>
                            <td class="py-2 text-right">
                                @if (in_array(auth()->user()->role, ['admin','operator']))
                                    <form method="POST" action="{{ route('feed.reverse', $record) }}" onsubmit="return confirm('Batalkan konsumsi ini dan kembalikan stok pakan?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50">Koreksi</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada konsumsi pakan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-farmpro.card>
</div>
@endsection
