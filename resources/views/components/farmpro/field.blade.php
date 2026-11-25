@props(['name', 'label', 'type' => 'text', 'value' => null, 'options' => [], 'hint' => null, 'required' => false, 'readonly' => false, 'step' => null])
@php
    $val = old($name, $value);
    $cls = 'w-full rounded-lg border bg-white px-3 py-2 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 '.($errors->has($name) ? 'border-red-400' : 'border-slate-200').($readonly ? ' bg-slate-100 text-slate-500' : '');
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}@if ($required)<span class="text-red-500"> *</span>@endif</label>
    @if ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" class="{{ $cls }}" @required($required)>
            <option value="">— Pilih —</option>
            @foreach ($options as $k => $l)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $l }}</option>@endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" class="{{ $cls }}">{{ $val }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $val }}" @if ($step) step="{{ $step }}" @endif class="{{ $cls }}" @required($required) @readonly($readonly)>
    @endif
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
