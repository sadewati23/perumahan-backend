<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 px-4 py-10 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-brand-500 text-xl font-bold text-white">
                SP
            </div>
            <h1 class="text-xl font-semibold text-white">Reset Password</h1>
            <p class="mt-1 text-sm text-slate-400">
                Buat password baru untuk akun {{ $email ?? 'Anda' }}.
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
                    <p class="font-semibold">Periksa kembali isian berikut:</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <x-field name="email" for="email" label="Email" required>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required
                           autocomplete="email" enterkeyhint="next" class="input"
                           placeholder="nama@instansi.go.id">
                </x-field>

                <x-field name="password" for="password" label="Password Baru" required>
                    <div class="relative">
                        <input id="password" name="password" type="password" required autofocus
                               autocomplete="new-password" enterkeyhint="next" class="input pr-24"
                               placeholder="Minimal 6 karakter">
                        <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                            <span data-password-text>Tampilkan</span>
                        </button>
                    </div>
                </x-field>

                <x-field name="password_confirmation" for="password_confirmation" label="Ulangi Password Baru" required>
                    <div class="relative">
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" enterkeyhint="go" class="input pr-24"
                               placeholder="Ulangi password baru">
                        <button type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Tampilkan password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                            <span data-password-text>Tampilkan</span>
                        </button>
                    </div>
                </x-field>

                <button type="submit"
                        class="w-full min-h-11 rounded-control bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-card transition hover:bg-brand-700 active:bg-brand-800">
                    Simpan Password Baru
                </button>

                <p class="text-center text-sm text-slate-500">
                    <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">
                        &larr; Kembali ke halaman masuk
                    </a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
