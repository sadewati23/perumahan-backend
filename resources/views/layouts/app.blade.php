<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">
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
           class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:static lg:translate-x-0">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-500 text-lg font-bold text-white">
                SP
            </div>
            <div class="min-w-0 leading-tight">
                <p class="truncate text-sm font-semibold text-white">Sistem Data</p>
                <p class="truncate text-xs text-slate-400">Perumahan</p>
            </div>
            <button type="button" data-toggle-sidebar
                    class="rounded-md p-1.5 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Tutup menu">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="no-scrollbar flex-1 space-y-6 overflow-y-auto px-3 py-4 text-sm" aria-label="Menu utama">
            @php
                $groups = [
                    'Data' => [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
                        ['label' => 'Data Perumahan', 'route' => 'perumahan.index', 'active' => 'perumahan.*', 'icon' => 'M4 21V8l8-5 8 5v13M9 21v-6h6v6'],
                        ['label' => 'Peta Sebaran', 'route' => 'perumahan.peta', 'active' => 'perumahan.peta', 'icon' => 'M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z'],
                        ['label' => 'Master Wilayah', 'route' => 'wilayah.index', 'active' => 'wilayah.*', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
                        ['label' => 'Manajemen Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'M16 21v-2a4 4 0 00-8 0v2M12 11a4 4 0 100-8 4 4 0 000 8z', 'role' => 'admin'],
                    ],
                    'Layanan' => [
                        ['label' => 'Laporan Warga', 'route' => 'laporan.index', 'active' => 'laporan.*', 'icon' => 'M12 9v4m0 4h.01M10.3 3.9L2.5 17a1.7 1.7 0 001.5 2.5h16a1.7 1.7 0 001.5-2.5L13.7 3.9a1.7 1.7 0 00-3 0z', 'badgeLaporan' => true],
                    ],
                ];
            @endphp

            @foreach ($groups as $groupLabel => $items)
                @php
                    $visibleItems = collect($items)->filter(fn ($item) => ! isset($item['role']) || auth()->user()?->isAdmin())->values();
                @endphp
                @if ($visibleItems->isNotEmpty())
                    <div>
                        <p class="mb-1 px-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $groupLabel }}</p>
                        <div class="space-y-1">
                            @foreach ($visibleItems as $item)
                                @php $isActive = request()->routeIs($item['active']); @endphp
                                <a href="{{ route($item['route']) }}"
                                   @if ($isActive) aria-current="page" @endif
                                   class="flex items-center gap-3 rounded-lg px-3 py-2 transition {{ $isActive ? 'bg-brand-600 text-white shadow-card' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                                    </svg>
                                    <span>{{ $item['label'] }}</span>
                                    @if (! empty($item['badgeLaporan']) && ($jumlahLaporanBaru ?? 0) > 0)
                                        <span class="ml-auto rounded-full bg-amber-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $jumlahLaporanBaru }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-3">
            <a href="{{ route('publik.beranda') }}" target="_blank" rel="noopener"
               class="mb-3 flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                <span>Lihat Situs Publik</span>
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5M19 5l-7 7M9 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-3"/>
                </svg>
            </a>

            <div class="rounded-lg bg-white/5 p-3">
                <p class="truncate text-sm font-medium text-white">{{ auth()->user()?->name }}</p>
                <p class="mt-0.5 text-xs text-slate-400">{{ auth()->user()?->label_role }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-md bg-slate-700 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-rose-600">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur lg:px-8">
            <div class="flex items-center gap-3">
                <button type="button" data-toggle-sidebar aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"
                        class="rounded-md border border-slate-300 p-2 text-slate-600 transition hover:bg-slate-100 lg:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <nav aria-label="Breadcrumb" class="mb-0.5">
                        <ol class="flex items-center gap-1 text-xs text-slate-500">
                            <li><a href="{{ route('dashboard') }}" class="hover:text-brand-700 hover:underline">Admin</a></li>
                            <li aria-hidden="true">/</li>
                            <li class="font-medium text-slate-700" aria-current="page">@yield('title', 'Dashboard')</li>
                        </ol>
                    </nav>
                    <h1 class="sr-only">@yield('title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-slate-200 bg-white px-2 py-1 text-sm text-slate-700 transition hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                        <span class="hidden sm:inline">{{ auth()->user()?->name }}</span>
                    </summary>
                    <div class="absolute right-0 z-40 mt-2 w-64 rounded-card border border-slate-200 bg-white p-3 shadow-pop">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()?->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ auth()->user()?->label_role }}</p>
                        <form method="POST" action="{{ route('logout') }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full rounded-control bg-danger-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-danger-700">
                                Keluar
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </header>

        <main id="main" class="flex-1 px-4 py-6 lg:px-8">
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
        </main>

        <footer class="border-t border-slate-200 px-4 py-4 text-center text-xs text-slate-600 lg:px-8">
            {{ config('app.name') }} &middot; &copy; {{ date('Y') }}
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
