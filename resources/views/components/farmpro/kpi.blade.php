@props(['label', 'value', 'icon', 'unit' => null, 'sub' => null, 'delta' => null, 'tone' => 'green'])
@php
    $tones = [
        'green' => [
            'wrap'  => 'border-emerald-200 dark:border-emerald-800/60 bg-gradient-to-br from-white to-emerald-50/40 dark:from-slate-800 dark:to-emerald-900/20',
            'icon'  => 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400',
            'label' => 'text-emerald-700 dark:text-emerald-400',
            'value' => 'text-emerald-700 dark:text-emerald-300',
        ],
        'blue'  => [
            'wrap'  => 'border-slate-200 dark:border-slate-700/60 bg-white dark:bg-slate-800',
            'icon'  => 'bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-400',
            'label' => 'text-slate-500 dark:text-slate-400',
            'value' => 'text-slate-900 dark:text-slate-100',
        ],
        'amber' => [
            'wrap'  => 'border-slate-200 dark:border-slate-700/60 bg-white dark:bg-slate-800',
            'icon'  => 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400',
            'label' => 'text-slate-500 dark:text-slate-400',
            'value' => 'text-slate-900 dark:text-slate-100',
        ],
        'rose'  => [
            'wrap'  => 'border-rose-200 dark:border-rose-800/60 bg-gradient-to-br from-white to-rose-50/40 dark:from-slate-800 dark:to-rose-900/20',
            'icon'  => 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400',
            'label' => 'text-rose-700 dark:text-rose-400',
            'value' => 'text-rose-700 dark:text-rose-300',
        ],
    ];
    $t = $tones[$tone] ?? $tones['blue'];
@endphp
<div class="glass-card rounded-2xl border p-4 shadow-sm hover:shadow-md transition {{ $t['wrap'] }}">
    <div class="flex items-center justify-between mb-2">
        <span class="text-[11px] font-bold uppercase tracking-wide {{ $t['label'] }}">{{ $label }}</span>
        <div class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-xs {{ $t['icon'] }}">
            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
        </div>
    </div>
    <div class="text-2xl font-black tracking-tight {{ $t['value'] }}">
        {{ $value }}
        @if ($unit)<span class="text-sm font-semibold ml-0.5">{{ $unit }}</span>@endif
        @if (! is_null($delta))
            <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-bold
                {{ $delta >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400' }}">
                {{ $delta >= 0 ? '↑' : '↓' }} {{ abs($delta) }}%
            </span>
        @endif
    </div>
    @if ($sub)
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-500 border-t border-slate-100 dark:border-slate-700/60 pt-1.5">{{ $sub }}</p>
    @endif
</div>
