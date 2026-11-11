@extends('layouts.publik')

@section('title', 'Data Perumahan')
@section('description', 'Daftar perumahan beserta lokasi, luasan lahan, jumlah unit, RTH, dan statusnya.')

@section('content')
    @php
        $filterAktif = [];

        if (filled($filters['q'] ?? null)) {
            $filterAktif[] = ['kunci' => 'q', 'label' => 'Pencarian', 'nilai' => $filters['q']];
        }

        if (filled($filters['kecamatan_id'] ?? null)) {
            $kecamatanAktif = $kecamatans->firstWhere('id', (int) $filters['kecamatan_id']);
            $filterAktif[] = ['kunci' => 'kecamatan_id', 'label' => 'Kecamatan', 'nilai' => $kecamatanAktif->nama ?? '-'];
        }

        if (filled($filters['status'] ?? null)) {
            $filterAktif[] = [
                'kunci' => 'status',
                'label' => 'Status',
                'nilai' => \App\Models\Perumahan::STATUS[$filters['status']] ?? $filters['status'],
            ];
        }

        $adaFilter = $filterAktif !== [];

        $deskripsiKosong = $adaFilter
            ? 'Tidak ada data yang sesuai dengan filter Anda. Coba hapus salah satu filter atau tampilkan seluruh daftar.'
            : 'Belum ada data perumahan yang dapat ditampilkan saat ini.';
    @endphp

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 pb-6 pt-6 lg:px-8">
            <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('publik.beranda') }}" class="hover:text-brand-700 hover:underline">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-700" aria-current="page">Data Perumahan</span>
            </nav>

            <div class="mt-3 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">Informasi perumahan</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Daftar Perumahan</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Jelajahi data perumahan berdasarkan lokasi, status, luasan, dan jumlah unit.
                </p>
            </div>

            <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-4">
                <div class="bg-white px-4 py-3">
                    <dt class="text-xs text-slate-500">Perumahan</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ number_format($ringkasan['jumlah'], 0, ',', '.') }}</dd>
                </div>
                <div class="bg-white px-4 py-3">
                    <dt class="text-xs text-slate-500">Jumlah unit</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ number_format($ringkasan['unit'], 0, ',', '.') }}</dd>
                </div>
                <div class="bg-white px-4 py-3">
                    <dt class="text-xs text-slate-500">Luas lahan</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ number_format($ringkasan['luas_lahan'] / 10000, 2, ',', '.') }} <span class="text-xs font-medium text-slate-500">ha</span></dd>
                </div>
                <div class="bg-white px-4 py-3">
                    <dt class="text-xs text-slate-500">Ruang terbuka hijau</dt>
                    <dd class="mt-0.5 text-lg font-semibold tabular-nums text-slate-900">{{ number_format($ringkasan['luas_rth'] / 10000, 2, ',', '.') }} <span class="text-xs font-medium text-slate-500">ha</span></dd>
                </div>
            </dl>

            <form method="GET" data-filter-otomatis class="mt-5">
                <details data-filter-lipat @if ($adaFilter) data-filter-aktif="1" @endif open
                         class="filter-lipat overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 md:hidden">
                        <span class="inline-flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <svg class="h-4 w-4 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/>
                            </svg>
                            Filter perumahan
                        </span>
                        <span class="text-xs font-medium text-brand-700">
                            {{ $adaFilter ? count($filterAktif).' aktif' : 'Buka' }}
                        </span>
                    </summary>

                    <div class="border-t border-slate-200 p-4 md:border-t-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
                            <h2 class="text-sm font-semibold text-slate-900">Cari perumahan</h2>
                            <p class="text-xs text-slate-500">Kecamatan dan status langsung diterapkan saat dipilih.</p>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-4">
                            <div class="md:col-span-2">
                                <label for="filter-q" class="mb-1 block text-xs font-medium text-slate-600">Pencarian</label>
                                <input type="text" id="filter-q" name="q" value="{{ $filters['q'] ?? '' }}"
                                       placeholder="Nama perumahan, pengembang, kelurahan, kecamatan..."
                                       class="input">
                            </div>
                            <div>
                                <label for="filter-kecamatan" class="mb-1 block text-xs font-medium text-slate-600">Kecamatan</label>
                                <select id="filter-kecamatan" name="kecamatan_id" class="input">
                                    <option value="">Semua kecamatan</option>
                                    @foreach ($kecamatans as $kecamatan)
                                        <option value="{{ $kecamatan->id }}" @selected(($filters['kecamatan_id'] ?? '') == $kecamatan->id)>
                                            {{ $kecamatan->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="filter-status" class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                                <select id="filter-status" name="status" class="input">
                                    <option value="">Semua status</option>
                                    @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="submit" class="tombol-utama w-full sm:w-auto">Terapkan Filter</button>
                            @if ($adaFilter)
                                <a href="{{ route('publik.perumahan.index') }}" class="tombol-sekunder flex-1 sm:flex-none">
                                    Reset filter
                                </a>
                            @endif
                            <a href="{{ route('publik.peta', request()->only(['q', 'status', 'kecamatan_id'])) }}"
                               class="tombol-sekunder w-full sm:ml-auto sm:w-auto">
                                Tampilkan di peta
                            </a>
                        </div>

                        @if ($adaFilter)
                            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                                <span class="text-xs font-medium text-slate-500">Filter aktif:</span>
                                @foreach ($filterAktif as $chip)
                                    <a href="{{ route('publik.perumahan.index', request()->except($chip['kunci'], 'page')) }}"
                                       class="group/chip inline-flex max-w-full items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 py-1 pl-3 pr-1.5 text-xs font-medium text-brand-800 transition hover:border-brand-300 hover:bg-brand-100">
                                        <span class="truncate">
                                            <span class="text-slate-500">{{ $chip['label'] }}:</span> {{ $chip['nilai'] }}
                                        </span>
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-brand-600 transition group-hover/chip:bg-brand-200" aria-hidden="true">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/>
                                            </svg>
                                        </span>
                                        <span class="sr-only">Hapus filter {{ $chip['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Perumahan terdata</h2>
                <p class="mt-1 text-sm text-slate-600">
                    <strong class="font-semibold text-slate-900">{{ number_format($perumahans->total(), 0, ',', '.') }}</strong>
                    perumahan ditemukan. Pilih kartu untuk melihat informasi lengkap.
                </p>
            </div>
            @if ($adaFilter)
                <span class="rounded-full bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-800 ring-1 ring-inset ring-brand-200">
                    {{ count($filterAktif) }} filter aktif
                </span>
            @endif
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span class="font-medium text-slate-600">Status:</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-800 ring-1 ring-inset ring-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-sky-800 ring-1 ring-inset ring-sky-200">
                <span class="h-2 w-2 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-violet-800 ring-1 ring-inset ring-violet-200">
                <span class="h-2 w-2 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran
            </span>
        </div>

        <div class="reveal-scroll grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($perumahans as $perumahan)
                @include('publik._card', ['perumahan' => $perumahan, 'animasi' => 'kartu-muncul'])
            @empty
                <x-empty-state title="Tidak ada perumahan yang cocok" :description="$deskripsiKosong" class="md:col-span-2 lg:col-span-3">
                    <x-slot:actions>
                        @if ($adaFilter)
                            <a href="{{ route('publik.perumahan.index') }}" class="tombol-utama">
                                Hapus semua filter
                            </a>
                            <a href="{{ route('publik.peta', request()->only(['q', 'status', 'kecamatan_id'])) }}" class="tombol-sekunder">
                                Lihat di peta
                            </a>
                        @else
                            <a href="{{ route('publik.beranda') }}" class="tombol-sekunder">
                                Kembali ke beranda
                            </a>
                        @endif
                    </x-slot:actions>
                </x-empty-state>
            @endforelse
        </div>

        @if ($perumahans->hasPages())
            <nav aria-label="Navigasi halaman" class="mt-6">
                {{ $perumahans->links() }}
            </nav>
        @endif
    </div>
@endsection
