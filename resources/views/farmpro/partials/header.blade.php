@php $user = auth()->user(); $unread = \App\Models\FarmNotification::whereNull('read_at')->count(); @endphp
<header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 dark:border-slate-700/60
               bg-white dark:bg-slate-900 px-4 py-3 lg:px-6 shadow-sm">

    {{-- Hamburger --}}
    <button type="button" @click="sidebar = true"
            class="rounded-xl p-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600
                   text-white transition flex items-center gap-2 text-xs font-bold shadow-sm"
            aria-label="Buka menu">
        <i data-lucide="menu" class="h-4 w-4"></i>
        <span class="hidden sm:inline text-xs font-bold">Menu</span>
    </button>

    {{-- Search --}}
    <label class="relative hidden max-w-xs flex-1 sm:block">
        <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
        <input x-ref="q" @keydown.window.ctrl.k.prevent="$refs.q.focus()" type="search"
               placeholder="Cari fitur atau data... (Ctrl+K)"
               class="w-full rounded-xl border-0 bg-slate-100 dark:bg-slate-800 dark:text-slate-200
                      dark:placeholder:text-slate-500 py-2 pl-9 pr-3 text-sm font-medium
                      focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
    </label>

    {{-- Page Title (mobile) --}}
    <div class="flex items-center gap-2 sm:hidden">
        <span class="text-sm font-bold text-slate-900 dark:text-white">@yield('title', 'Dashboard')</span>
    </div>

    {{-- Right side --}}
    <div class="ml-auto flex items-center gap-2 sm:gap-3">

        {{-- Live Badge --}}
        <span class="hidden md:flex items-center gap-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/40
                     px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Live
        </span>

        {{-- ===== DARK / LIGHT TOGGLE ===== --}}
        <button id="darkToggleBtn"
                onclick="window.FarmTheme.toggle()"
                title="Aktifkan Mode Gelap"
                class="relative rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-700/60
                       text-slate-500 dark:text-slate-400 transition"
                aria-label="Toggle Dark Mode">
            {{-- Sun icon (shown in dark mode → click = go light) --}}
            <i data-lucide="sun"  data-icon-sun  class="h-5 w-5 hidden text-amber-400"></i>
            {{-- Moon icon (shown in light mode → click = go dark) --}}
            <i data-lucide="moon" data-icon-moon class="h-5 w-5"></i>
        </button>

        {{-- Notifications --}}
        <a href="{{ route('notifications.index') }}"
           class="relative rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-700/60 transition"
           aria-label="Notifikasi">
            <i data-lucide="bell" class="h-5 w-5 text-slate-500 dark:text-slate-400"></i>
            @if ($unread)
                <span class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center
                             rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $unread }}</span>
            @endif
        </a>

        {{-- User Dropdown --}}
        <div x-data="{ o: false }" class="relative">
            <button type="button" @click="o = !o" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-tr
                             from-emerald-600 to-emerald-400 text-sm font-bold text-white shadow">
                    {{ Str::of($user->name)->explode(' ')->map(fn ($w) => Str::substr($w, 0, 1))->take(2)->implode('') }}
                </span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-100">{{ $user->name }}</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400 capitalize">
                        {{ $user->role === 'admin' ? 'Administrator' : $user->role }}
                    </span>
                </span>
                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 hidden sm:block"></i>
            </button>

            <form x-show="o" x-cloak @click.outside="o = false" method="POST" action="{{ route('logout') }}"
                  class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-100 dark:border-slate-700
                         bg-white dark:bg-slate-800 p-1 shadow-xl z-50">
                @csrf
                <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700 mb-1">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-100">{{ $user->name }}</p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $user->email }}</p>
                </div>
                {{-- Toggle inside dropdown --}}
                <button type="button" onclick="window.FarmTheme.toggle(); document.querySelector('[x-data]').__x.$data.o = false"
                        class="flex items-center gap-2 w-full rounded-lg px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <i data-lucide="monitor" class="h-3.5 w-3.5"></i>
                    Ganti Tampilan
                </button>
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2 w-full rounded-lg px-3 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                    <i data-lucide="user" class="h-3.5 w-3.5"></i> Profil Saya
                </a>
                <button class="flex items-center gap-2 w-full rounded-lg px-3 py-2 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition">
                    <i data-lucide="log-out" class="h-3.5 w-3.5"></i> Keluar
                </button>
            </form>
        </div>
    </div>
</header>
