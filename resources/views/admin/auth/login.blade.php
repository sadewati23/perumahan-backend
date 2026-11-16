<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo-narrow:500,600,700|instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-tinta px-4 py-10 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <x-logo tile ukuran="mx-auto mb-3 h-14 w-14" />
            <h1 class="font-judul text-2xl font-semibold tracking-tight text-white">Sistem Informasi Data Perumahan</h1>
            <p class="mt-1 text-sm text-slate-400">
                {{ config('peta.nama_wilayah') }} &middot; masuk untuk mengelola data perumahan, PSU, dan titik lokasi
            </p>
        </div>

        <div class="rounded-modal bg-white p-6 shadow-pop sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf

                <x-field name="username" for="username" label="Username" required>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                           autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
                           enterkeyhint="next" class="input">
                </x-field>

                <x-field name="password" for="password" label="Password" required>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               autocomplete="current-password" enterkeyhint="go" class="input pr-24"
                               placeholder="••••••••">
                        <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-2 text-xs font-medium text-slate-600 transition hover:bg-kertas-tua hover:text-slate-700">
                            <span data-password-text>Tampilkan</span>
                        </button>
                    </div>
                </x-field>

                <label class="flex min-h-11 items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Ingat saya
                </label>

                <button type="submit"
                        class="tombol-utama w-full">
                    Masuk
                </button>

                <p class="text-center text-sm text-slate-600">
                    <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">
                        Lupa password?
                    </a>
                </p>
            </form>
        </div>

        @env('local')
            <p class="mt-6 text-center text-xs text-slate-400">
                Akun awal lokal: <span class="font-medium text-slate-200">admin / password</span>
            </p>
        @endenv
    </div>
</body>
</html>
