<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('farmpro.name') }}</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body class="min-h-screen bg-slate-100">
    <main class="grid min-h-screen place-items-center px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl">
            <div class="mb-6 text-center">
                <div class="text-5xl">🐔</div>
                <h1 class="mt-3 text-2xl font-bold text-slate-900">Masuk ke FarmPro</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola peternakan Anda dalam satu tempat</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700">Kata sandi</span>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-700">
                    Ingat saya
                </label>
                <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 font-semibold text-white hover:bg-emerald-800">Masuk</button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-400">Demo: admin@farmpro.test / password</p>
        </div>
    </main>
</body>
</html>
