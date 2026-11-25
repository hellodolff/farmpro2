@extends('farmpro.layout')
@section('title','Panen & Penjualan')
@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Panen &amp; Penjualan</h1>
    <p class="text-sm text-slate-500">Catat hasil panen, pembeli, dan pemasukan batch.</p>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <x-farmpro.card title="Catat panen" icon="shopping-basket" class="lg:col-span-1">
        <form method="POST" action="{{ route('operational.harvest.store') }}" class="space-y-3" id="harvest-form">
            @csrf
            <x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->mapWithKeys(fn($b) => [$b->id => $b->code.' — '.$b->current_population.' ekor'])->all()" required />
            <x-farmpro.field name="harvest_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
            <x-farmpro.field name="heads" type="number" label="Jumlah ekor" required />
            <x-farmpro.field name="total_weight_kg" type="number" label="Bobot total kotor (kg)" :step="0.01" required />
            <x-farmpro.field name="weight_basis" type="select" label="Dasar bobot" :options="['live'=>'Hidup','carcass'=>'Karkas']" required />
            <x-farmpro.field name="customer_id" type="select" label="Pembeli" :options="$customers" />
            <x-farmpro.field name="deductions" type="number" label="Potongan bobot (kg)" :value="0" :step="0.01" />
            <x-farmpro.field name="price_per_unit" type="number" label="Harga per kg" :step="0.01" required />

            <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm">
                <div class="flex justify-between text-slate-600"><span>Bobot bersih</span><strong id="net-weight-preview">0 kg</strong></div>
                <div class="mt-1 flex justify-between text-slate-600"><span>Hasil penjualan</span><strong id="sale-total-preview" class="text-brand-800">Rp 0</strong></div>
                <p class="mt-2 text-xs text-slate-500">Rumus: (bobot total kotor − potongan bobot) × harga per kg.</p>
            </div>

            <x-farmpro.field name="transport_cost" type="number" label="Biaya angkut (informasi)" :value="0" :step="0.01" />
            <x-farmpro.field name="notes" type="textarea" label="Catatan" />
            <button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan panen &amp; penjualan</button>
        </form>
    </x-farmpro.card>

    <x-farmpro.card title="Riwayat panen" icon="history" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">Tanggal</th>
                        <th>Batch</th>
                        <th>Ekor</th>
                        <th>Bobot kotor</th>
                        <th>Dasar</th>
                        <th>Catatan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($harvests as $h)
                        <tr class="border-b">
                            <td class="py-3">{{ \Illuminate\Support\Carbon::parse($h->harvest_date)->format('d M Y') }}</td>
                            <td>{{ $h->batch?->code }}</td>
                            <td>{{ number_format($h->heads) }}</td>
                            <td>{{ number_format($h->total_weight_kg, 2, ',', '.') }} kg</td>
                            <td>{{ $h->weight_basis === 'live' ? 'Hidup' : 'Karkas' }}</td>
                            <td>{{ $h->notes ?: '-' }}</td>
                            <td class="text-right">
                                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer text-xs text-brand-700">Edit</summary>
                                        <form method="POST" action="{{ route('harvest.update', $h) }}" class="mt-2 w-80 space-y-2 rounded-lg border bg-white p-3 shadow">
                                            @csrf
                                            @method('PUT')
                                            <x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" :value="$h->batch_id" required />
                                            <x-farmpro.field name="harvest_date" type="date" label="Tanggal" :value="\Illuminate\Support\Carbon::parse($h->harvest_date)->toDateString()" required />
                                            <x-farmpro.field name="heads" type="number" label="Jumlah ekor" :value="$h->heads" required />
                                            <x-farmpro.field name="total_weight_kg" type="number" label="Bobot total kotor (kg)" :value="$h->total_weight_kg" :step="0.01" required />
                                            <x-farmpro.field name="weight_basis" type="select" label="Dasar bobot" :options="['live'=>'Hidup','carcass'=>'Karkas']" :value="$h->weight_basis" required />
                                            <x-farmpro.field name="customer_id" type="select" label="Pembeli" :options="$customers" :value="$h->sale?->customer_id" />
                                            <x-farmpro.field name="deductions" type="number" label="Potongan bobot (kg)" :value="$h->sale?->deductions ?? 0" :step="0.01" />
                                            <x-farmpro.field name="price_per_unit" type="number" label="Harga per kg" :value="$h->sale?->price_per_unit ?? 0" :step="0.01" required />
                                            <x-farmpro.field name="transport_cost" type="number" label="Biaya angkut" :value="$h->sale?->transport_cost ?? 0" :step="0.01" />
                                            <x-farmpro.field name="notes" type="textarea" label="Catatan" :value="$h->notes" />
                                            <button class="rounded-lg bg-brand-600 px-3 py-2 text-xs text-white">Simpan perubahan</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('harvest.reverse', $h) }}" onsubmit="return confirm('Batalkan panen ini dan kembalikan populasi?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs text-red-700">Koreksi</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">Belum ada riwayat panen.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-farmpro.card>
</div>

@push('scripts')
<script>
(function () {
    const gross = document.querySelector('[name="total_weight_kg"]');
    const deduction = document.querySelector('[name="deductions"]');
    const price = document.querySelector('[name="price_per_unit"]');
    const netPreview = document.getElementById('net-weight-preview');
    const totalPreview = document.getElementById('sale-total-preview');

    function rupiah(value) {
        return 'Rp ' + Math.max(0, value).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function calculate() {
        const grossValue = Number(gross?.value || 0);
        const deductionValue = Number(deduction?.value || 0);
        const priceValue = Number(price?.value || 0);
        const netValue = Math.max(0, grossValue - deductionValue);
        netPreview.textContent = netValue.toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' kg';
        totalPreview.textContent = rupiah(netValue * priceValue);
    }

    [gross, deduction, price].forEach((input) => input?.addEventListener('input', calculate));
    calculate();
})();
</script>
@endpush
@endsection
