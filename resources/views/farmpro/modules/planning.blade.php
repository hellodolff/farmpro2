@extends('farmpro.layout')
@section('title','Simulasi Usaha')
@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Simulasi Usaha</h1>
    <p class="text-sm text-slate-500">Estimasi konservatif, dasar, dan optimistis. Hasil bukan jaminan keuntungan.</p></div>
<x-farmpro.card title="Estimasi kalkulator" icon="calculator">
<form method="POST" action="{{ route('planning.calculate') }}" class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">@csrf
<x-farmpro.field name="population" type="number" label="Populasi" :value="old('population',1000)" required/>
<x-farmpro.field name="doc_price" type="number" label="Harga DOC" :value="old('doc_price',7000)" required/>
<x-farmpro.field name="feed_kg_per_head" type="number" label="Pakan/ekor (kg)" :value="old('feed_kg_per_head',3.5)" :step="0.1" required/>
<x-farmpro.field name="feed_price" type="number" label="Harga pakan/kg" :value="old('feed_price',8500)" required/>
<x-farmpro.field name="mortality_pct" type="number" label="Mortalitas (%)" :value="old('mortality_pct',5)" :step="0.1" required/>
<x-farmpro.field name="output_per_head" type="number" label="Output/ekor (kg)" :value="old('output_per_head',2)" :step="0.1" required/>
<x-farmpro.field name="sell_price" type="number" label="Harga jual/kg" :value="old('sell_price',35000)" required/>
<x-farmpro.field name="capex" type="number" label="Modal tetap" :value="old('capex',50000000)" required/>
<div class="sm:col-span-2 md:col-span-3">
    <button class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white">Hitung skenario</button></div>
</form>
@if(session('simulation'))<pre class="mt-4 overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs text-green-300">{{ json_encode(session('simulation'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>@endif
</x-farmpro.card>
@endsection