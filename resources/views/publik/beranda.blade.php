@extends('layouts.publik')

@section('title', 'Beranda')
@section('description', 'Portal informasi data perumahan: lokasi, luasan lahan, jumlah unit, RTH, KDB/KDH, PSU, dan status subsidi atau komersil.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
    {{-- Hero --}}
    <section class="bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-900 text-white">
        <div class="mx-auto max-w-7xl px-4 py-14 lg:px-8 lg:py-20">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-indigo-200 ring-1 ring-inset ring-white/20">
                Portal Data Terbuka &middot; {{ config('peta.nama_wilayah') }}
            </span>
            <h1 class="mt-4 max-w-3xl text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                Informasi Data Perumahan {{ config('peta.nama_wilayah') }}
            </h1>
            <p class="mt-4 max-w-2xl text-sm text-slate-300 sm:text-base">
                Telusuri data perumahan beserta lokasi kelurahan dan kecamatan, RT/RW, titik koordinat,
                luas lahan, jumlah unit, RTH, KDB/KDH, lahan pemakaman, prasarana-sarana-utilitas (PSU),
                tahun pembangunan, tipe unit, dan status subsidi/komersil.
            </p>

            <form method="GET" action="{{ route('publik.perumahan.index') }}" class="mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row">
                <input type="text" name="q" placeholder="Cari nama perumahan, pengembang, kelurahan, atau kecamatan..."
                       class="w-full rounded-lg border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder-slate-300 outline-none backdrop-blur focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30">
                <button type="submit" class="rounded-lg bg-indigo-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400">
                    Cari Perumahan
                </button>
            </form>

            <div class="mt-6 flex flex-wrap gap-3 text-sm">
                <a href="{{ route('publik.peta') }}" class="rounded-lg border border-white/20 px-4 py-2 font-medium text-white transition hover:bg-white/10">
                    Lihat Peta Sebaran
                </a>
                <a href="{{ route('publik.perumahan.index', ['status' => 'subsidi']) }}" class="rounded-lg border border-white/20 px-4 py-2 font-medium text-white transition hover:bg-white/10">
                    Perumahan Subsidi
                </a>
                <a href="{{ route('publik.perumahan.index', ['status' => 'komersil']) }}" class="rounded-lg border border-white/20 px-4 py-2 font-medium text-white transition hover:bg-white/10">
                    Perumahan Komersil
                </a>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        {{-- Statistik --}}
        @php
            $kartu = [
                ['label' => 'Perumahan Terdata', 'value' => number_format($statistik['total_perumahan'], 0, ',', '.'), 'satuan' => 'lokasi'],
                ['label' => 'Total Unit Rumah', 'value' => number_format($statistik['total_unit'], 0, ',', '.'), 'satuan' => 'unit'],
                ['label' => 'Luas Lahan', 'value' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
                ['label' => 'Luas RTH', 'value' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($kartu as $item)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">{{ $item['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $item['value'] }}</p>
                    <p class="text-xs text-slate-500">{{ $item['satuan'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Komposisi status --}}
        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Komposisi Status</p>
                @php
                    $totalStatus = max(1, $statistik['total_perumahan']);
                    $persenSubsidi = round($statistik['jumlah_subsidi'] / $totalStatus * 100);
                    $persenKomersil = round($statistik['jumlah_komersil'] / $totalStatus * 100);
                @endphp
                <div class="mt-4 space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-emerald-700">Subsidi</span>
                            <span class="text-slate-600">{{ $statistik['jumlah_subsidi'] }} ({{ $persenSubsidi }}%)</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $persenSubsidi }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-indigo-700">Komersil</span>
                            <span class="text-slate-600">{{ $statistik['jumlah_komersil'] }} ({{ $persenKomersil }}%)</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $persenKomersil }}%"></div>
                        </div>
                    </div>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                    Tersebar di {{ $statistik['jumlah_kecamatan'] }} kecamatan.
                </p>
            </div>

            {{-- Sebaran per kecamatan --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">Sebaran per Kecamatan</p>
                    <a href="{{ route('publik.perumahan.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat semua</a>
                </div>

                @php $maxKecamatan = max(1, (int) $perKecamatan->max('perumahans_count')); @endphp

                <div class="mt-4 space-y-3">
                    @forelse ($perKecamatan as $kecamatan)
                        <a href="{{ route('publik.perumahan.index', ['kecamatan_id' => $kecamatan->id]) }}" class="block">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $kecamatan->nama }}</span>
                                <span class="text-slate-500">{{ $kecamatan->perumahans_count }} perumahan</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-indigo-500"
                                     style="width: {{ round($kecamatan->perumahans_count / $maxKecamatan * 100) }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada data kecamatan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Peta ringkas --}}
        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Peta Sebaran Perumahan</h2>
                    <p class="text-sm text-slate-600">Klik marker untuk melihat ringkasan perumahan.</p>
                </div>
                <a href="{{ route('publik.peta') }}"
                   class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Buka peta lengkap
                </a>
            </div>

            <div id="peta-beranda" class="mt-4 h-80 w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm"></div>

            @if ($titik->isEmpty())
                <p class="mt-3 text-sm text-slate-500">Belum ada perumahan dengan titik koordinat.</p>
            @endif
        </section>

        {{-- Perumahan terbaru --}}
        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Perumahan Terbaru</h2>
                    <p class="text-sm text-slate-600">Berdasarkan tahun selesai pembangunan.</p>
                </div>
                <a href="{{ route('publik.perumahan.index') }}"
                   class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Semua perumahan &rarr;</a>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($terbaru as $perumahan)
                    @include('publik._card', ['perumahan' => $perumahan])
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 lg:col-span-3">
                        Belum ada data perumahan yang dipublikasikan.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('peta-beranda');
            if (!el || typeof window.L === 'undefined') return;

            const PETA = @json(config('peta'));
            const titik = @json($titik);
            if (!titik.length) return;

            const map = L.map(el).setView([PETA.latitude, PETA.longitude], PETA.zoom);
            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(map);

            const colors = { Subsidi: '#10b981', Komersil: '#6366f1' };
            const bounds = [];
            const formatAngka = (angka) => new Intl.NumberFormat('id-ID').format(angka);

            // Nilai dari data titik dirender APA ADANYA ke HTML popup — escape
            // dulu agar karakter <, >, &, dan " dari nama/alamat perumahan
            // tidak dianggap bagian dari HTML di dalam Leaflet popup (XSS).
            const escapeHtml = (nilai) => String(nilai ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            titik.forEach((p) => {
                const warna = colors[p.status] || '#64748b';
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="width:14px;height:14px;border-radius:9999px;background:${warna};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });

                L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(`
                    <div style="min-width:200px">
                        <p style="font-weight:600;margin:0 0 2px">${escapeHtml(p.nama)}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                        <p style="margin:0 0 6px;color:#475569;font-size:12px">${escapeHtml(p.alamat)}</p>
                        <p style="margin:0 0 6px;font-size:12px;color:#334155">
                            ${formatAngka(p.unit)} unit &middot; ${formatAngka(p.luas_lahan)} m&sup2;
                        </p>
                        <a href="${encodeURI(p.url)}" style="color:#4f46e5;font-size:12px;font-weight:500">Lihat detail &rarr;</a>
                    </div>`);

                bounds.push([p.lat, p.lng]);
            });

            map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
            setTimeout(() => map.invalidateSize(), 200);
        });
    </script>
@endpush
