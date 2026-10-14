<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('app.name') }}</title>
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
            <h1 class="text-xl font-semibold text-white">Sistem Informasi Data Perumahan</h1>
            <p class="mt-1 text-sm text-slate-400">
                {{ config('peta.nama_wilayah') }} &middot; masuk untuk mengelola data perumahan, PSU, dan titik lokasi
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
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

                <x-field name="email" for="email" label="Email" required>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email" enterkeyhint="next" class="input"
                           placeholder="nama@instansi.go.id">
                </x-field>

                <x-field name="password" for="password" label="Password" required>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                               autocomplete="current-password" enterkeyhint="go" class="input pr-24"
                               placeholder="••••••••">
                        <button type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan password"
                                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md px-2 py-2 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
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
                        class="w-full min-h-11 rounded-control bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-card transition hover:bg-brand-700 active:bg-brand-800">
                    Masuk
                </button>

                <p class="text-center text-sm text-slate-500">
                    <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:text-brand-700 hover:underline">
                        Lupa password?
                    </a>
                </p>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            Akun awal: <span class="font-medium text-slate-300">admin@perumahan.test / password</span>
        </p>
    </div>
</body>
</html>
