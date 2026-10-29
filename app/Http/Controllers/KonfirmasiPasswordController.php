<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class KonfirmasiPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.konfirmasi-password', [
            'menit' => (int) ceil(config('auth.password_timeout', 900) / 60),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['password'], $request->user()->getAuthPassword())) {
            return back()->withInput()->withErrors([
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        $tujuan = $request->session()->pull('konfirmasi_password.url');

        return $tujuan ? redirect()->to($tujuan) : redirect()->intended(route('dashboard'));
    }
}
