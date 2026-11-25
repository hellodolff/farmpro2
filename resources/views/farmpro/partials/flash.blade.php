@if (session('success'))
    <div role="status" class="mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 dark:border-emerald-800/50
                               bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm font-medium
                               text-emerald-800 dark:text-emerald-300 shadow-sm">
        <i data-lucide="check-circle" class="h-4 w-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0"></i>
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div role="alert" class="mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 dark:border-red-800/50
                              bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm font-medium
                              text-red-800 dark:text-red-300 shadow-sm">
        <i data-lucide="x-circle" class="h-4 w-4 text-red-600 dark:text-red-400 flex-shrink-0"></i>
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div role="alert" class="mb-4 rounded-xl border border-red-200 dark:border-red-800/50
                              bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm
                              text-red-800 dark:text-red-300 shadow-sm">
        <p class="font-bold flex items-center gap-1.5">
            <i data-lucide="alert-circle" class="h-4 w-4 flex-shrink-0"></i>
            Periksa kembali isian Anda:
        </p>
        <ul class="mt-1.5 list-disc pl-6 space-y-0.5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
