<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Konfirmasi Password — {{ config('app.name') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 px-4 py-10 font-sans text-slate-800 antialiased">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center">
            <x-logo tile ukuran="mx-auto mb-3 h-14 w-14" />
            <h1 class="text-xl font-semibold text-white">Konfirmasi Password</h1>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
            <p class="mb-5 text-sm leading-6 text-slate-600">
                Demi keamanan, masukkan password Anda untuk melanjutkan. Setelah dikonfirmasi, Anda akan kembali ke halaman sebelumnya dan perlu mengulangi aksi tersebut. Konfirmasi berlaku {{ $menit }} menit.
            </p>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
                @csrf
                <x-field name="password" for="password" label="Password" required>
                    <input id="password" name="password" type="password" required autofocus autocomplete="current-password" class="input">
                </x-field>
                <button type="submit" class="w-full min-h-11 rounded-control bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-card transition hover:bg-brand-700 active:bg-brand-800">
                    Konfirmasi
                </button>
            </form>
        </div>
    </div>
</body>
</html>
