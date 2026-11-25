@extends('farmpro.layout')
@section('title','Penimbangan Bobot')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Penimbangan Bobot</h1><p class="text-sm text-slate-500">Catat bobot rata-rata ternak berdasarkan sampel.</p></div>
<div class="grid gap-4 lg:grid-cols-3">
<x-farmpro.card title="Catat penimbangan" icon="scale" class="lg:col-span-1">
<form method="POST" action="{{ route('weight.store') }}" class="space-y-3">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->pluck('code','id')" required />
<x-farmpro.field name="record_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="avg_weight_g" type="number" label="Bobot rata-rata (gram)" :step="0.01" required />
<x-farmpro.field name="sample_count" type="number" label="Jumlah sampel" />
<x-farmpro.field name="total_sample_weight_g" type="number" label="Total bobot sampel (gram)" :step="0.01" />
<x-farmpro.field name="notes" type="textarea" label="Catatan" />
<button class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">Simpan penimbangan</button>
</form></x-farmpro.card>
<x-farmpro.card title="Riwayat penimbangan" icon="history" class="lg:col-span-2"><div class="overflow-x-auto"><table class="w-full min-w-[34rem] text-left text-sm"><thead><tr class="border-b"><th class="py-2">Tanggal</th><th>Batch</th><th>Umur</th><th>Bobot rata-rata</th><th>Sampel</th><th></th></tr></thead><tbody>@forelse($records as $r)<tr class="border-b"><td class="py-3">{{ \Illuminate\Support\Carbon::parse($r->record_date)->format('d M Y') }}</td><td>{{ $r->batch?->code }}</td><td>{{ $r->age_days }} hari</td><td class="font-semibold">{{ number_format($r->avg_weight_g,2,',','.') }} g</td><td>{{ $r->sample_count ?: '-' }}</td><td class="text-right">@if(in_array(auth()->user()->role,['admin','operator']))<form method="POST" action="{{ route('weight.reverse',$r) }}" onsubmit="return confirm('Batalkan catatan bobot ini?')">@csrf @method('DELETE')<button class="text-xs text-red-700">Koreksi</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data penimbangan.</td></tr>@endforelse</tbody></table></div></x-farmpro.card>
</div>
@endsection
