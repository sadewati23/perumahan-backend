<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password — {{ config('app.name') }}</title>
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
            <h1 class="text-xl font-semibold text-white">Lupa Password</h1>
            <p class="mt-1 text-sm text-slate-400">
                Masukkan email akun Anda — tautan reset password akan dikirim ke email tersebut.
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl sm:p-8">
            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-5 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                <x-field name="email" for="email" label="Email" required>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email" enterkeyhint="go" class="input"
                           placeholder="nama@instansi.go.id">
                </x-field>

                <button type="submit"
                        class="w-full min-h-11 rounded-control bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-card transition hover:bg-brand-700 active:bg-brand-800">
                    Kirim Tautan Reset
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
