@extends('layouts.publik')

@section('title', 'Data Perumahan')
@section('description', 'Daftar perumahan beserta lokasi, luasan lahan, jumlah unit, RTH, dan statusnya.')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-500">
                <a href="{{ route('publik.beranda') }}" class="hover:text-indigo-600">Beranda</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700">Data Perumahan</span>
            </nav>

            <h1 class="mt-3 text-2xl font-semibold text-slate-900">Daftar Perumahan</h1>
            <p class="mt-1 text-sm text-slate-600">
                Menampilkan <span class="font-semibold">{{ $ringkasan['jumlah'] }}</span> perumahan &middot;
                <span class="font-semibold">{{ number_format($ringkasan['unit'], 0, ',', '.') }}</span> unit &middot;
                <span class="font-semibold">{{ number_format($ringkasan['luas_lahan'] / 10000, 2, ',', '.') }}</span> ha lahan &middot;
                <span class="font-semibold">{{ number_format($ringkasan['luas_rth'] / 10000, 2, ',', '.') }}</span> ha RTH
            </p>

            <form method="GET" class="mt-6 grid gap-3 md:grid-cols-4">
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
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($perumahans as $perumahan)
                @include('publik._card', ['perumahan' => $perumahan])
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
