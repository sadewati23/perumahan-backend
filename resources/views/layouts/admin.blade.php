<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f1f5f9">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo-narrow:500,600,700|instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="bg-kertas font-sans text-slate-700 antialiased">
{{-- Skip link untuk navigasi keyboard --}}
<a href="#main"
   class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">
    Lewati ke konten utama
</a>

<div class="min-h-screen lg:flex">
    {{-- Overlay gelap di belakang sidebar (hanya tampil saat sidebar terbuka di layar kecil) --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-tinta text-slate-300 transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="flex items-center gap-3 border-b border-white/10 px-4 py-4">
            <x-logo tile ukuran="h-9 w-9" />

            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-sm font-semibold text-white">Sistem Data</p>
                <p class="truncate text-xs text-slate-400">Perumahan</p>
            </div>

            <button type="button" data-toggle-sidebar
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Tutup menu">
                <x-icon name="tutup" class="h-5 w-5"/>
            </button>
        </div>

        <nav class="no-scrollbar flex-1 space-y-6 overflow-y-auto px-3 py-4 text-sm" aria-label="Menu utama">
            @php
                $groups = [
                    'Data' => [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
                        ['label' => 'Data Perumahan', 'route' => 'perumahan.index', 'active' => 'perumahan.*', 'icon' => 'perumahan'],
                        ['label' => 'Peta Sebaran', 'route' => 'perumahan.peta', 'active' => 'perumahan.peta', 'icon' => 'peta'],
                        ['label' => 'Master Wilayah', 'route' => 'wilayah.index', 'active' => 'wilayah.*', 'icon' => 'wilayah'],
                        ['label' => 'Manajemen Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'pengguna', 'role' => 'admin'],
                    ],
                    'Layanan' => [
                        ['label' => 'Laporan Warga', 'route' => 'laporan.index', 'active' => 'laporan.*', 'icon' => 'laporan', 'badgeLaporan' => true],
                    ],
                ];
            @endphp

            @foreach ($groups as $groupLabel => $items)
                @php
                    $visibleItems = collect($items)->filter(fn ($item) => ! isset($item['role']) || auth()->user()?->isAdmin())->values();
                @endphp
                @if ($visibleItems->isNotEmpty())
                    <div>
                        <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $groupLabel }}</p>
                        <div class="space-y-1">
                            @foreach ($visibleItems as $item)
                                @php $isActive = request()->routeIs($item['active']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   @if ($isActive) aria-current="page" @endif
                                   class="group relative flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 transition lg:min-h-0 {{ $isActive ? 'bg-brand-600 text-white shadow-card' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    @if ($isActive)
                                        <span class="absolute inset-y-2 left-0 w-1 rounded-full bg-white/80" aria-hidden="true"></span>
                                    @endif

                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md transition {{ $isActive ? 'bg-white/15 text-white' : 'bg-white/5 text-slate-400 group-hover:bg-white/10 group-hover:text-white' }}">
                                        <x-icon :name="$item['icon']" class="h-4 w-4"/>
                                    </span>

                                    <span class="min-w-0 truncate">{{ $item['label'] }}</span>

                                    @if (! empty($item['badgeLaporan']) && ($jumlahLaporanBaru ?? 0) > 0)
                                        <span class="ml-auto rounded-full bg-amber-300 px-2 py-0.5 text-[11px] font-bold tabular-nums text-amber-950"
                                              aria-label="{{ $jumlahLaporanBaru }} laporan baru">{{ $jumlahLaporanBaru }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="space-y-2 border-t border-white/10 p-3">
            <a href="{{ route('publik.beranda') }}" target="_blank" rel="noopener"
               class="flex min-h-11 items-center justify-between gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-300 transition hover:bg-white/5 hover:text-white lg:min-h-0">
                <span class="flex items-center gap-2">
                    <x-icon name="eksternal" class="h-4 w-4 text-slate-400"/>
                    Lihat situs publik
                </span>
                <span class="sr-only">(terbuka di tab baru)</span>
            </a>

            <div class="rounded-lg bg-white/5 p-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()?->name }}</p>
                        <p class="truncate text-xs text-slate-400">{{ auth()->user()?->label_role }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-danger-600 lg:min-h-0">
                        <x-icon name="keluar" class="h-4 w-4"/>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div id="konten-utama" class="flex min-h-screen flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 border-b border-garis bg-kertas/90 px-4 py-2.5 backdrop-blur lg:px-8">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <button type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-kertas lg:hidden">
                        <x-icon name="menu" class="h-5 w-5"/>
                    </button>

                    <div class="min-w-0">
                        <nav aria-label="Breadcrumb">
                            <ol class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                <li><a href="{{ route('dashboard') }}" class="transition hover:text-brand-700">Admin</a></li>
                                <li aria-hidden="true" class="text-slate-400">/</li>
                                <li class="truncate" aria-current="page">@yield('title', 'Dashboard')</li>
                            </ol>
                        </nav>
                        <h1 class="truncate font-judul text-lg font-semibold tracking-tight text-tinta sm:text-xl">
                            @yield('title', 'Dashboard')
                        </h1>
                        @hasSection('subtitle')
                            <p class="hidden truncate text-xs text-slate-600 sm:block">@yield('subtitle')</p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('publik.beranda') }}" target="_blank" rel="noopener"
                       class="hidden min-h-11 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-slate-600 transition hover:bg-white hover:text-brand-700 lg:inline-flex lg:min-h-9">
                        <x-icon name="eksternal" class="h-4 w-4"/>
                        Situs publik
                    </a>

                    <details class="relative">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-full border border-slate-300 bg-white px-1.5 py-1 text-sm text-slate-700 transition hover:bg-kertas lg:min-h-0">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden max-w-40 truncate pr-1 sm:inline">{{ auth()->user()?->name }}</span>
                            <span class="sr-only">Buka menu akun</span>
                        </summary>
                        <div class="pop-masuk absolute right-0 z-40 mt-2 w-64 rounded-card border border-garis bg-white p-3 shadow-pop">
                            <p class="text-sm font-semibold text-tinta">{{ auth()->user()?->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ auth()->user()?->label_role }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                                @csrf
                                <button type="submit" class="flex min-h-11 w-full items-center justify-center gap-2 rounded-control bg-danger-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-danger-700 lg:min-h-0">
                                    <x-icon name="keluar" class="h-4 w-4"/>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>
        </header>

        <main id="main" class="flex-1 px-4 py-6 lg:px-8">
            <div class="mx-auto w-full max-w-7xl">
                @if (auth()->user()?->isAdmin() && \App\Support\TurnstileKonfigurasi::perluPerhatian())
                    <div class="mb-4 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                        <x-icon name="laporan" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700"/>
                        <p>
                            Perlindungan CAPTCHA pada form laporan belum aktif: kunci Turnstile kosong atau masih kunci uji.
                            Isi kunci asli di file .env server (lihat HOSTING.md).
                        </p>
                    </div>
                @endif

                @if (session('success'))
                    <x-flash-message tone="success" auto-dismiss>
                        {{ session('success') }}
                    </x-flash-message>
                @endif

                @if (session('error'))
                    <x-flash-message tone="error">
                        {{ session('error') }}
                    </x-flash-message>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="border-t border-garis px-4 py-4 text-center text-xs text-slate-600 lg:px-8">
            {{ config('app.name') }} &middot; &copy; {{ date('Y') }}
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
