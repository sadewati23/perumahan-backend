@extends('layouts.publik')

@section('title', 'Data Perumahan')
@section('description', 'Daftar perumahan beserta lokasi, luasan lahan, jumlah unit, RTH, dan statusnya.')

@section('content')
    <section class="border-b border-slate-200 bg-gradient-to-br from-indigo-50 via-white to-slate-50">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8 masuk-naik tunda-1">
            <nav class="flex items-center gap-2 text-xs text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('publik.beranda') }}" class="hover:text-indigo-600">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-slate-700">Data Perumahan</span>
            </nav>

            <div class="mt-4 max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Informasi perumahan</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Daftar Perumahan</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Jelajahi data perumahan berdasarkan lokasi, status, luasan, dan jumlah unit.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap gap-2 text-sm">
                <span class="inline-flex items-baseline gap-2 rounded-xl border border-indigo-200 bg-white/80 px-3.5 py-2 text-indigo-800 shadow-sm">
                    <strong class="text-lg font-bold tabular-nums">{{ $ringkasan['jumlah'] }}</strong>
                    <span class="text-xs font-medium">perumahan</span>
                </span>
                <span class="inline-flex items-baseline gap-2 rounded-xl border border-sky-200 bg-white/80 px-3.5 py-2 text-sky-800 shadow-sm">
                    <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['unit'], 0, ',', '.') }}</strong>
                    <span class="text-xs font-medium">unit</span>
                </span>
                <span class="inline-flex items-baseline gap-2 rounded-xl border border-amber-200 bg-white/80 px-3.5 py-2 text-amber-800 shadow-sm">
                    <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['luas_lahan'] / 10000, 2, ',', '.') }}</strong>
                    <span class="text-xs font-medium">ha lahan</span>
                </span>
                <span class="inline-flex items-baseline gap-2 rounded-xl border border-emerald-200 bg-white/80 px-3.5 py-2 text-emerald-800 shadow-sm">
                    <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['luas_rth'] / 10000, 2, ',', '.') }}</strong>
                    <span class="text-xs font-medium">ha RTH</span>
                </span>
            </div>

            <form method="GET" class="mt-7 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 via-white to-white p-4 shadow-sm backdrop-blur masuk-naik tunda-2">
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Cari perumahan</h2>
                        <p class="text-xs text-slate-500">Gunakan filter untuk menemukan data yang Anda perlukan.</p>
                    </div>
                </div>
                <div class="grid gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Pencarian</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="Nama perumahan, pengembang, kelurahan, kecamatan..."
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Kecamatan</label>
                    <select name="kecamatan_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                        <option value="">Semua kecamatan</option>
                        @foreach ($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}" @selected(($filters['kecamatan_id'] ?? '') == $kecamatan->id)>
                                {{ $kecamatan->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 md:col-span-4">
                    <button type="submit" class="flex-1 whitespace-nowrap rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 sm:flex-none">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('publik.perumahan.index') }}"
                       class="flex-1 whitespace-nowrap rounded-lg border border-slate-300 px-5 py-2 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50 sm:flex-none">
                        Reset
                    </a>
                    <a href="{{ route('publik.peta', request()->only(['q', 'status', 'kecamatan_id'])) }}"
                       class="ml-auto whitespace-nowrap rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Tampilkan di peta
                    </a>
                </div>
                </div>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Perumahan terdata</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih kartu untuk melihat informasi lengkap.</p>
            </div>
            @if (array_filter($filters, fn ($value) => filled($value)) !== [])
                <span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700">Filter sedang aktif</span>
            @endif
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span class="font-medium text-slate-600">Status:</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 ring-1 ring-inset ring-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 ring-1 ring-inset ring-sky-200">
                <span class="h-2 w-2 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-violet-700 ring-1 ring-inset ring-violet-200">
                <span class="h-2 w-2 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran
            </span>
        </div>

        <div class="reveal-scroll grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($perumahans as $perumahan)
                @include('publik._card', ['perumahan' => $perumahan, 'animasi' => 'masuk-naik '.($loop->iteration <= 8 && $loop->iteration <= 4 ? 'tunda-'.$loop->iteration : '')])
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center md:col-span-2 lg:col-span-3">
                    <p class="text-sm font-medium text-slate-700">Tidak ada perumahan yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah kata kunci, kecamatan, atau status pencarian Anda.</p>
                    <a href="{{ route('publik.perumahan.index') }}"
                       class="mt-4 inline-block rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Tampilkan semua data
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $perumahans->links() }}
        </div>
    </div>
@endsection
