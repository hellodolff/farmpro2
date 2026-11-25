@extends('farmpro.layout')
@section('title','Kematian & Afkir')
@section('content')
<div class="mb-5"><h1 class="text-2xl font-bold text-slate-900">Kematian &amp; Afkir</h1><p class="text-sm text-slate-500">Mutasi populasi yang juga memperbarui perhitungan mortalitas.</p></div>
<div class="grid gap-4 lg:grid-cols-3">
<x-farmpro.card title="Catat mutasi" icon="heart-crack" class="lg:col-span-1"><form method="POST" action="{{ route('mortality.store') }}" class="space-y-3">@csrf
<x-farmpro.field name="batch_id" type="select" label="Batch" :options="$batches->mapWithKeys(fn($b)=>[$b->id=>$b->code.' — '.$b->current_population.' ekor'])->all()" required />
<x-farmpro.field name="type" type="select" label="Jenis" :options="['death'=>'Kematian','cull'=>'Afkir']" required />
<x-farmpro.field name="quantity" type="number" label="Jumlah (ekor)" required />
<x-farmpro.field name="movement_date" type="date" label="Tanggal" :value="now()->toDateString()" required />
<x-farmpro.field name="reason" label="Alasan" />
<button class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white">Simpan mutasi</button></form></x-farmpro.card>
<x-farmpro.card title="Riwayat" icon="history" class="lg:col-span-2"><div class="overflow-x-auto"><table class="w-full min-w-[38rem] text-left text-sm"><thead><tr class="border-b"><th class="py-2">Tanggal</th><th>Batch</th><th>Jenis</th><th>Jumlah</th><th>Alasan</th><th></th></tr></thead><tbody>@forelse($records as $r)<tr class="border-b"><td class="py-3">{{ \Illuminate\Support\Carbon::parse($r->movement_date)->format('d M Y') }}</td><td>{{ $r->batch?->code }}</td><td>{{ $r->type==='death'?'Kematian':'Afkir' }}</td><td class="font-semibold">{{ number_format($r->quantity) }} ekor</td><td>{{ $r->reason ?: '-' }}</td><td class="text-right">@if(in_array(auth()->user()->role,['admin','operator']))<form method="POST" action="{{ route('mortality.reverse',$r) }}" onsubmit="return confirm('Batalkan mutasi dan kembalikan populasi?')">@csrf @method('DELETE')<button class="text-xs text-red-700">Koreksi</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada data kematian atau afkir.</td></tr>@endforelse</tbody></table></div></x-farmpro.card>
</div>
@endsection
