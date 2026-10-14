<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') — {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Informasi data perumahan: lokasi, luasan lahan, jumlah unit, RTH, KDB/KDH, PSU, dan status perumahan.')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
    {{-- Skip link untuk navigasi keyboard --}}
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">
        Lewati ke konten utama
    </a>

    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 lg:px-8">
            <a href="{{ route('publik.beranda') }}" class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">SP</span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold text-slate-900">Data Perumahan</span>
                    <span class="block truncate text-xs text-slate-500">{{ config('peta.nama_wilayah') }}</span>
                </span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Menu utama">
                @php
                    $menu = [
                        ['label' => 'Beranda', 'route' => 'publik.beranda', 'active' => 'publik.beranda'],
                        ['label' => 'Data Perumahan', 'route' => 'publik.perumahan.index', 'active' => 'publik.perumahan.*'],
                        ['label' => 'Peta Sebaran', 'route' => 'publik.peta', 'active' => 'publik.peta'],
                        ['label' => 'Lapor', 'route' => 'lapor.create', 'active' => 'lapor.*'],
                    ];
                @endphp

                @foreach ($menu as $item)
                    @php $aktif = request()->routeIs($item['active']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @if ($aktif) aria-current="page" @endif
                       class="rounded-lg px-3 py-2 text-sm font-medium transition {{ $aktif ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('login') }}"
               class="shrink-0 whitespace-nowrap rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 sm:px-4">
                <span class="hidden sm:inline">Masuk </span>Admin
            </a>
        </div>

        <nav class="no-scrollbar flex gap-1 overflow-x-auto border-t border-slate-100 px-4 py-2 md:hidden" aria-label="Menu utama">
            @foreach ($menu as $item)
                @php $aktif = request()->routeIs($item['active']); @endphp
                <a href="{{ route($item['route']) }}"
                   @if ($aktif) aria-current="page" @endif
                   class="whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $aktif ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </header>

    <main id="main" class="flex-1">
        @if (session('success'))
            <div class="mx-auto max-w-7xl px-4 pt-4 lg:px-8">
                <x-flash-message tone="success" auto-dismiss>
                    {{ session('success') }}
                </x-flash-message>
            </div>
        @endif

        @if (session('error'))
            <div class="mx-auto max-w-7xl px-4 pt-4 lg:px-8">
                <x-flash-message tone="error">
                    {{ session('error') }}
                </x-flash-message>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-xs font-bold text-white">SP</span>
                    <span class="font-semibold text-slate-900">{{ config('app.name') }}</span>
                </div>
                <p class="mt-3 max-w-md text-sm text-slate-600">
                    Portal informasi data perumahan {{ config('peta.nama_wilayah') }} yang memuat identitas perumahan, lokasi administratif, titik koordinat,
                    luasan lahan, jumlah unit, RTH, KDB/KDH, lahan pemakaman, prasarana-sarana-utilitas (PSU), serta status subsidi/komersil.
                </p>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-900">Jelajahi</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('publik.perumahan.index') }}" class="hover:text-indigo-600">Daftar Perumahan</a></li>
                    <li><a href="{{ route('publik.peta') }}" class="hover:text-indigo-600">Peta Sebaran</a></li>
                    <li><a href="{{ route('lapor.create') }}" class="hover:text-indigo-600">Lapor Kerusakan / Pengaduan</a></li>
                    <li><a href="{{ route('publik.peta.geojson') }}" target="_blank" rel="noopener" class="hover:text-indigo-600">Data GeoJSON</a></li>
                </ul>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-900">Akses Petugas</p>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('login') }}" class="hover:text-indigo-600">Masuk Admin / Petugas</a></li>
                    <li class="text-slate-500">Pendataan &amp; pemutakhiran data dilakukan oleh petugas terverifikasi.</li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-100 px-4 py-4 text-center text-xs text-slate-500 lg:px-8">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Data diperbarui secara berkala oleh petugas pendataan.
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
