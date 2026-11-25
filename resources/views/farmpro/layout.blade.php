<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('farmpro.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:  '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    {{-- ===== DARK MODE INIT (before paint to avoid flash) ===== --}}
    <script>
        (function () {
            const saved = localStorage.getItem('farmpro-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* ===== Scrollbar ===== */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
        .dark ::-webkit-scrollbar-track { background: #0f172a; }

        /* ===== Sidebar custom scrollbar ===== */
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

        /* ===== Glass card ===== */
        .glass-card {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(10px);
            transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
        }
        .dark .glass-card {
            background: rgba(30, 41, 59, 0.97);
        }

        /* ===== Dark mode smooth transition ===== */
        *, *::before, *::after {
            transition-property: background-color, border-color, color;
            transition-duration: 150ms;
            transition-timing-function: ease;
        }
        /* Exclude transitions on animations / transforms to prevent jank */
        .animate-pulse, [class*="transition-transform"], [class*="duration-"] {
            transition-property: transform, opacity;
        }

        /* ===== Dark mode toggle button ===== */
        #darkToggleBtn { transition: transform 0.3s cubic-bezier(.4,0,.2,1); }
        #darkToggleBtn:active { transform: scale(0.9) rotate(20deg); }

        /* ===== Dark mode base overrides ===== */
        .dark body { background-color: #0f172a; color: #cbd5e1; }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 font-sans text-slate-700 dark:text-slate-300 antialiased" x-data="{ sidebar: false }">

<div class="flex min-h-screen">
    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false"
         class="fixed inset-0 z-30 bg-slate-950/70 backdrop-blur-sm lg:hidden"></div>

    {{-- ===== SIDEBAR ===== --}}
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="sidebar-scroll fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col overflow-y-auto
                  bg-slate-900 text-white transition-transform duration-300
                  lg:sticky lg:top-0 lg:h-screen border-r border-slate-800 shadow-2xl">
        @include('farmpro.partials.sidebar')
    </aside>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="min-w-0 flex-1 flex flex-col">
        @include('farmpro.partials.header')
        <main class="flex-1 p-4 lg:p-6">
            @include('farmpro.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<script>
    lucide.createIcons();

    /* ===== DARK MODE CONTROLLER ===== */
    window.FarmTheme = {
        get isDark() {
            return document.documentElement.classList.contains('dark');
        },
        toggle() {
            const html = document.documentElement;
            const goingDark = !html.classList.contains('dark');
            html.classList.toggle('dark', goingDark);
            localStorage.setItem('farmpro-theme', goingDark ? 'dark' : 'light');
            this._updateIcon(goingDark);
            this._updateCharts(goingDark);
        },
        _updateIcon(isDark) {
            const btn = document.getElementById('darkToggleBtn');
            if (!btn) return;
            btn.querySelector('[data-icon-sun]').classList.toggle('hidden', !isDark);
            btn.querySelector('[data-icon-moon]').classList.toggle('hidden', isDark);
            btn.setAttribute('title', isDark ? 'Aktifkan Mode Terang' : 'Aktifkan Mode Gelap');
            /* re-create lucide icons inside the button */
            lucide.createIcons({ elements: [btn] });
        },
        _updateCharts(isDark) {
            /* Update all existing Chart.js instances to match dark/light theme */
            const gridColor   = isDark ? 'rgba(148,163,184,0.12)' : '#f1f5f9';
            const tickColor   = isDark ? '#94a3b8' : '#64748b';
            const legendColor = isDark ? '#cbd5e1' : '#475569';
            Object.values(Chart.instances || {}).forEach(chart => {
                if (!chart || !chart.options) return;
                const scales = chart.options.scales || {};
                Object.values(scales).forEach(scale => {
                    if (scale.grid) scale.grid.color = gridColor;
                    if (scale.ticks) scale.ticks.color = tickColor;
                });
                if (chart.options.plugins?.legend?.labels)
                    chart.options.plugins.legend.labels.color = legendColor;
                chart.update('none');
            });
        },
        init() {
            const isDark = this.isDark;
            this._updateIcon(isDark);
        }
    };
    document.addEventListener('DOMContentLoaded', () => window.FarmTheme.init());
</script>
@stack('scripts')
</body>
</html>
