@extends('farmpro.layout')
@section('title',$title)
@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
    <p class="text-sm text-slate-500">Kelola transaksi manual dengan jejak koreksi dan audit.</p>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Tambah {{ strtolower($title) }}" icon="circle-plus" class="lg:col-span-1">
        <form method="POST" action="{{ route('finance.store') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <x-farmpro.field name="trx_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
            <x-farmpro.field name="category" type="select" label="Kategori" :options="array_combine($categories,$categories)" required />
            <x-farmpro.field name="amount" type="number" label="Nominal (Rp)" :step="0.01" required />
            <x-farmpro.field name="payment_method" type="select" label="Metode pembayaran" :options="['Tunai'=>'Tunai','Transfer'=>'Transfer','E-Wallet'=>'E-Wallet','Piutang'=>'Piutang','Utang'=>'Utang']" />
            <x-farmpro.field name="batch_id" type="select" label="Batch (opsional)" :options="$batches->all()" />
            <x-farmpro.field name="description" type="textarea" label="Keterangan" />
            <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan {{ strtolower($title) }}</button>
        </form>
    </x-farmpro.card>

    <x-farmpro.card title="Daftar {{ strtolower($title) }}" icon="receipt" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead>
                    <tr class="border-b"><th class="py-2">Tanggal</th><th>Kategori</th><th>Metode</th><th>Batch</th><th class="text-right">Nominal</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="border-b align-top">
                            <td class="py-3">{{ \Illuminate\Support\Carbon::parse($transaction->trx_date)->format('d M Y') }}</td>
                            <td><strong>{{ $transaction->category }}</strong><div class="text-xs text-slate-500">{{ $transaction->description ?: '-' }}</div></td>
                            <td>{{ $transaction->payment_method ?: '-' }}</td>
                            <td>{{ $transaction->batch_id ?: '-' }}</td>
                            <td class="text-right font-semibold">Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                            <td class="text-right">
                                @if(is_null($transaction->source_type) && is_null($transaction->reversed_of_id))
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer text-xs text-brand-700">Ubah</summary>
                                        <form method="POST" action="{{ route('finance.update', $transaction) }}" class="mt-2 w-72 space-y-2 rounded-lg border bg-white p-3 shadow">
                                            @csrf @method('PUT')
                                            <x-farmpro.field name="trx_date" type="date" label="Tanggal" :value="$transaction->trx_date" required />
                                            <x-farmpro.field name="category" label="Kategori" :value="$transaction->category" required />
                                            <x-farmpro.field name="amount" type="number" label="Nominal" :value="abs($transaction->amount)" :step="0.01" required />
                                            <x-farmpro.field name="payment_method" label="Metode" :value="$transaction->payment_method" />
                                            <x-farmpro.field name="batch_id" type="number" label="Batch ID" :value="$transaction->batch_id" />
                                            <x-farmpro.field name="description" type="textarea" label="Keterangan" :value="$transaction->description" />
                                            <button class="rounded-lg bg-brand-600 px-3 py-2 text-xs text-white">Simpan perubahan</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('finance.reverse', $transaction) }}" class="ml-2 inline" onsubmit="return confirm('Koreksi transaksi ini dengan reversal?')">
                                        @csrf
                                        <button class="text-xs text-red-700">Reversal</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">Transaksi sumber</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada {{ strtolower($title) }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $transactions->links() }}</div>
    </x-farmpro.card>
</div>
@endsection
