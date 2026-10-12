<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->batasiPercobaanLogin($request);

        try {
            $this->batasiPercobaanPerEmail($request);
        } catch (ValidationException $e) {
            $this->batasiPercobaanLogin($request);

            throw $e;
        }

        // is_active ikut dicek di sini: akun nonaktif diperlakukan sama seperti
        // kredensial salah, sehingga tidak membocorkan bahwa password-nya benar.
        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($this->kunciPercobaanLogin($request));
            RateLimiter::hit($this->kunciPercobaanPerEmail($request));

            throw ValidationException::withMessages([
                'email' => 'Email atau password yang Anda masukkan salah, atau akun dinonaktifkan.',
            ]);
        }

        RateLimiter::clear($this->kunciPercobaanLogin($request));
        RateLimiter::clear($this->kunciPercobaanPerEmail($request));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }

    /**
     * Anti brute-force login: maksimal 5 percobaan gagal per menit,
     * ber-key email + IP. Login yang berhasil menghapus penghitung.
     *
     * Kunci INI yang di-blind oleh spoofing X-Forwarded-For (karena $request->ip()
     * hanya dipengaruhi proxy terpercaya — lihat bootstrap/app.php) — namun
     * pengaman kedua (percobaan per email) tetap berjalan walau IP-nya falsu.
     */
    private function batasiPercobaanLogin(Request $request): void
    {
        $kunci = $this->kunciPercobaanLogin($request);

        if (! RateLimiter::tooManyAttempts($kunci, 5)) {
            return;
        }

        $detik = RateLimiter::availableIn($kunci);

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.$detik.' detik.',
        ]);
    }

    /**
     * Pengaman kedua: maksimal 5 percobaan gagal per menit **per email saja**
     * (tanpa IP). Ini yang menjaga satu akun tidak di-brute-force dari banyak
     * IP sekaligus / dengan IP palsu.
     * Dipanggil setelah batasiPercobaanLogin agar pesan yang tampil tetap
     * prioritaskan pembatasan per IP; pengecekan dua-duanya tetap di-hit.
     */
    private function batasiPercobaanPerEmail(Request $request): void
    {
        $kunci = $this->kunciPercobaanPerEmail($request);

        if (! RateLimiter::tooManyAttempts($kunci, 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Terlalu banyak percobaan login untuk akun ini. Silakan coba lagi '.RateLimiter::availableIn($kunci).' detik lagi.',
        ]);
    }

    /**
     * Key throttle percobaan login: email (dinormalisasi) + IP pengirim.
     */
    private function kunciPercobaanLogin(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }

    /**
     * Key throttle percobaan login per email saja (tanpa IP) — lapis kedua.
     */
    private function kunciPercobaanPerEmail(Request $request): string
    {
        return 'login-email:'.Str::transliterate(Str::lower($request->input('email')));
    }

    /*
    |--------------------------------------------------------------------------
    | Lupa Password
    |--------------------------------------------------------------------------
    */

    /**
     * Form permintaan tautan reset password.
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim tautan reset password ke email yang terdaftar.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Tautan reset password sudah dikirim ke email Anda. Periksa kotak masuk atau folder spam.')
            : back()->withInput()->withErrors(['email' => 'Email tidak terdaftar. Periksa kembali alamat email Anda.']);
    }

    /**
     * Form reset password (dibuka dari tautan di email).
     */
    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Proses simpan password baru dari tautan reset.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(6)],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Password berhasil direset. Silakan masuk dengan password baru Anda.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.']);
    }
}