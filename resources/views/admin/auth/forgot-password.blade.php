<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password — {{ config('app.name') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo-narrow:500,600,700|instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-tinta px-4 py-10 font-sans text-slate-800 antialiased">
    <main class="w-full max-w-md">
        <div class="mb-6 text-center">
            <x-logo tile ukuran="mx-auto mb-3 h-14 w-14" />
            <h1 class="font-judul text-2xl font-semibold tracking-tight text-white">Lupa Password</h1>
            <p class="mt-1 text-sm text-slate-400">
                Masukkan email yang terdaftar pada akun Anda (bukan username). Tautan reset password akan dikirim ke email tersebut.
            </p>
        </div>

        <div class="overflow-hidden rounded-modal bg-white shadow-pop">
            <div class="h-1 bg-brand-600" aria-hidden="true"></div>
            <div class="p-6 sm:p-8">
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

                    <x-button type="submit" class="w-full">
                        <x-icon name="masuk" class="h-4 w-4"/>
                        Kirim Tautan Reset
                    </x-button>

                    <p class="text-center text-sm text-slate-600">
                        <a href="{{ route('login') }}" class="font-medium text-brand-700 hover:text-brand-800 hover:underline">
                            &larr; Kembali ke halaman masuk
                        </a>
                    </p>
                </form>
            </div>
        </div>

        <p class="mt-4 text-center text-xs text-slate-400">
            Tautan reset berlaku terbatas demi keamanan akun.
        </p>
    </main>
</body>
</html>
