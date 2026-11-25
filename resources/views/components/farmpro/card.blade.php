@props(['title' => null, 'icon' => null, 'link' => null])
<section {{ $attributes->merge(['class' => 'glass-card rounded-2xl border border-slate-200 dark:border-slate-700/60 bg-white dark:bg-slate-800 p-5 shadow-sm hover:shadow-md transition']) }}>
    @if ($title)
        <div class="mb-4 flex items-center justify-between gap-3">
            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
                @if ($icon)<i data-lucide="{{ $icon }}" class="h-4 w-4 text-emerald-600 dark:text-emerald-400"></i>@endif
                {{ $title }}
            </h3>
            @if ($link)
                <a href="{{ $link }}" class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:underline transition">
                    Lihat Semua →
                </a>
            @endif
        </div>
    @endif
    {{ $slot }}
</section>
