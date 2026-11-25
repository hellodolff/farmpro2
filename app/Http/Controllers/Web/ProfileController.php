<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('farmpro.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $request->user()->update($validated);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->tokens()->delete();
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $user->delete();

        return redirect()->route('login');
    }
}
