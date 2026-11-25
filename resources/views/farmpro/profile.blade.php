@extends('farmpro.layout')
@section('title', 'Profil')

@section('content')
<div class="mx-auto max-w-2xl space-y-4">
    <h1 class="text-3xl font-bold text-slate-900">Profil Pengguna</h1>

    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="rounded-xl border border-slate-200 bg-white p-6 space-y-4">
        @csrf
        @method('PATCH')
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Nama</span>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="block">
            <span class="mb-1 block text-sm font-medium">Email</span>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        @foreach ($errors->getMessages() as $messages)
            @foreach ($messages as $message)<p class="text-sm text-red-600">{{ $message }}</p>@endforeach
        @endforeach
        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Simpan perubahan</button>
    </form>

    <form method="POST" action="{{ route('profile.destroy') }}" class="rounded-xl border border-red-200 bg-white p-6" onsubmit="return confirm('Hapus akun ini secara permanen?')">
        @csrf
        @method('DELETE')
        <h2 class="font-semibold text-red-700">Hapus Akun</h2>
        <p class="mt-1 text-sm text-slate-500">Konfirmasi dengan kata sandi Anda.</p>
        <input type="password" name="password" required class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2">
        <button type="submit" class="mt-3 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Hapus akun</button>
    </form>
</div>
@endsection
