@extends('layouts.publik')

@section('title', 'Beranda')
@section('description', 'Cari data perumahan di '.config('peta.nama_wilayah').': lokasi, jumlah unit, luas lahan, RTH, KDB/KDH, PSU, dan status perumahan.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    @php
        $tanggalUpdate = $terakhirDiperbarui
            ? \Illuminate\Support\Carbon::parse($terakhirDiperbarui)->translatedFormat('d F Y')
            : null;

        $totalStatus = max(1, $statistik['total_perumahan']);
        $persenSubsidi = round($statistik['jumlah_subsidi'] / $totalStatus * 100);
        $persenKomersil = round($statistik['jumlah_komersil'] / $totalStatus * 100);
        $persenCampuran = round($statistik['jumlah_campuran'] / $totalStatus * 100);

        $angka = [
            ['label' => 'Perumahan terdata', 'nilai' => number_format($statistik['total_perumahan'], 0, ',', '.'), 'satuan' => 'lokasi'],
            ['label' => 'Total unit rumah', 'nilai' => number_format($statistik['total_unit'], 0, ',', '.'), 'satuan' => 'unit'],
            ['label' => 'Luas lahan', 'nilai' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
            ['label' => 'Ruang terbuka hijau', 'nilai' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
        ];

        $maxKecamatan = max(1, (int) $perKecamatan->max('perumahans_count'));
    @endphp

    {{-- Pembuka: pencarian + peta sebaran --}}
    <section class="border-b border-garis bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
            <div class="grid gap-8 lg:grid-cols-12 lg:items-center lg:gap-10">
                <div class="lg:col-span-5">
                    <h1 class="font-judul text-4xl font-semibold leading-[1.04] tracking-tight text-tinta sm:text-5xl">
                        Data perumahan di {{ config('peta.nama_wilayah') }}
                    </h1>
                    <p class="mt-4 max-w-md text-base leading-7 text-slate-600">
                        Temukan lokasi, jumlah unit, dan fasilitas perumahan di sekitar Anda, lalu laporkan
                        kerusakan sarana yang ditemui.
                    </p>

                    <form method="GET" action="{{ route('publik.perumahan.index') }}" class="mt-6 flex max-w-md gap-2">
                        <input type="text" name="q" aria-label="Cari perumahan" enterkeyhint="search"
                               placeholder="Nama perumahan, kelurahan, kecamatan…"
                               class="input flex-1">
                        <button type="submit" class="tombol-utama shrink-0 px-5">Cari</button>
                    </form>

                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                        <a href="{{ route('publik.peta') }}" class="font-semibold text-brand-700 hover:underline">Lihat peta sebaran</a>
                        <a href="{{ route('lapor.create') }}" class="font-semibold text-brand-700 hover:underline">Lapor kerusakan</a>
                    </div>

                    @if ($tanggalUpdate)
                        <p class="mt-6 text-xs text-slate-600">
                            Data terakhir diperbarui {{ $tanggalUpdate }}.
                        </p>
                    @endif
                </div>

                <div class="lg:col-span-7">
                    <div class="overflow-hidden rounded-card border border-garis bg-white shadow-card">
                        <div class="flex items-center justify-between gap-3 border-b border-garis px-4 py-3">
                            <p class="text-sm font-semibold text-tinta">Sebaran perumahan</p>
                            <span class="text-xs text-slate-600">{{ $titik->count() }} titik terpetakan</span>
                        </div>

                        @if ($titik->isEmpty())
                            <div class="flex h-[320px] items-center justify-center bg-kertas-tua px-6 text-center text-sm text-slate-600 sm:h-[380px]">
                                Belum ada perumahan dengan titik koordinat.
                            </div>
                        @else
                            <div id="peta-beranda" class="h-[320px] w-full bg-kertas-tua sm:h-[380px]"></div>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-garis px-4 py-3 text-xs text-slate-700" aria-label="Legenda status perumahan">
                            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi</span>
                            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil</span>
                            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran</span>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-slate-600">
                        Klik penanda untuk melihat ringkasan.
                        <a href="{{ route('publik.peta') }}" class="font-medium text-brand-700 hover:underline">Buka peta lengkap</a>
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Strip angka utama --}}
    <section class="border-b border-garis bg-white" aria-label="Ringkasan data perumahan">
        <div class="mx-auto max-w-7xl px-4 lg:px-8">
            <dl class="grid grid-cols-2 gap-px overflow-hidden bg-garis lg:grid-cols-4">
                @foreach ($angka as $item)
                    <div class="bg-white px-4 py-5 lg:px-6">
                        <dt class="text-sm text-slate-600">{{ $item['label'] }}</dt>
                        <dd class="angka-utama mt-2">{{ $item['nilai'] }}</dd>
                        <p class="mt-1 text-xs text-slate-600">{{ $item['satuan'] }}</p>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-12">
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Komposisi status --}}
            <section class="rounded-card border border-garis bg-white p-5" aria-labelledby="judul-status">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 id="judul-status" class="text-sm font-semibold text-tinta">Komposisi status</h2>
                    <p class="text-xs text-slate-600">{{ $statistik['total_perumahan'] }} perumahan</p>
                </div>

                <div class="mt-4 flex h-3 overflow-hidden rounded-pill bg-kertas-tua"
                     role="img"
                     aria-label="Subsidi {{ $statistik['jumlah_subsidi'] }} perumahan, komersil {{ $statistik['jumlah_komersil'] }}, campuran {{ $statistik['jumlah_campuran'] }}">
                    <div class="bg-emerald-500" style="width: {{ $persenSubsidi }}%"></div>
                    <div class="bg-sky-500" style="width: {{ $persenKomersil }}%"></div>
                    <div class="bg-violet-500" style="width: {{ $persenCampuran }}%"></div>
                </div>

                <dl class="mt-4 divide-y divide-garis text-sm">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi</dt>
                        <dd class="tabular-nums text-slate-700"><span class="font-semibold text-tinta">{{ $statistik['jumlah_subsidi'] }}</span> · {{ $persenSubsidi }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil</dt>
                        <dd class="tabular-nums text-slate-700"><span class="font-semibold text-tinta">{{ $statistik['jumlah_komersil'] }}</span> · {{ $persenKomersil }}%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="flex items-center gap-2 text-slate-700"><span class="h-2.5 w-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran</dt>
                        <dd class="tabular-nums text-slate-700"><span class="font-semibold text-tinta">{{ $statistik['jumlah_campuran'] }}</span> · {{ $persenCampuran }}%</dd>
                    </div>
                </dl>

                <p class="mt-2 border-t border-garis pt-3 text-sm text-slate-600">
                    Tersebar di {{ $statistik['jumlah_kecamatan'] }} kecamatan.
                </p>
            </section>

            {{-- Sebaran per kecamatan --}}
            <section class="rounded-card border border-garis bg-white p-5 lg:col-span-2" aria-labelledby="judul-kecamatan">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="judul-kecamatan" class="text-sm font-semibold text-tinta">Sebaran per kecamatan</h2>
                    <a href="{{ route('publik.perumahan.index') }}" class="text-sm font-medium text-brand-700 hover:underline">Semua perumahan</a>
                </div>

                <div class="mt-3 divide-y divide-garis">
                    @forelse ($perKecamatan as $kecamatan)
                        <a href="{{ route('publik.perumahan.index', ['kecamatan_id' => $kecamatan->id]) }}"
                           class="group block py-3">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-800 group-hover:text-brand-700">{{ $kecamatan->nama }}</span>
                                <span class="text-slate-600">{{ $kecamatan->perumahans_count }} perumahan</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-pill bg-kertas-tua">
                                <div class="h-full rounded-pill bg-brand-600" style="width: {{ round($kecamatan->perumahans_count / $maxKecamatan * 100) }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="py-4 text-sm text-slate-600">Belum ada data kecamatan.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Perumahan terbaru --}}
        <section class="mt-10" aria-labelledby="judul-terbaru">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="judul-terbaru" class="font-judul text-2xl font-semibold tracking-tight text-tinta">Perumahan terbaru</h2>
                    <p class="mt-1 text-sm text-slate-600">Berdasarkan tahun selesai pembangunan.</p>
                </div>
                <a href="{{ route('publik.perumahan.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Semua perumahan</a>
            </div>

            <div class="mt-4 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($terbaru as $perumahan)
                    @include('publik._card', ['perumahan' => $perumahan])
                @empty
                    <div class="rounded-card border border-dashed border-garis bg-white p-8 text-center text-sm text-slate-600 md:col-span-2 lg:col-span-3">
                        Belum ada data perumahan yang dipublikasikan.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@if ($titik->isNotEmpty())
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('peta-beranda');
                if (!el || typeof window.L === 'undefined') return;

                const PETA = @json(config('peta'));
                const titik = @json($titik);

                const map = L.map(el, { scrollWheelZoom: false }).setView([PETA.latitude, PETA.longitude], PETA.zoom);
                L.tileLayer(PETA.tile_url, {
                    maxZoom: 19,
                    attribution: PETA.tile_attribution,
                }).addTo(map);

                const colors = @json(\App\Models\Perumahan::warnaPerLabel());
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
                        html: `<div style="width:14px;height:14px;${p.status === 'Komersil' ? 'border-radius:2px' : p.status === 'Campuran' ? 'border-radius:2px;transform:rotate(45deg)' : 'border-radius:9999px'};background:${warna};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
                        iconSize: [14, 14],
                        iconAnchor: [7, 7],
                    });

                    L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(`
                        <div style="min-width:200px;font-family:'Instrument Sans',sans-serif">
                            <p style="font-weight:600;margin:0 0 2px;color:#16211c">${escapeHtml(p.nama)}</p>
                            <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                            <p style="margin:0 0 6px;color:#3f4a44;font-size:12px">${escapeHtml(p.alamat)}</p>
                            <p style="margin:0 0 6px;font-size:12px;color:#3f4a44">
                                ${formatAngka(p.unit)} unit &middot; ${formatAngka(p.luas_lahan)} m&sup2;
                            </p>
                            <a href="${encodeURI(p.url)}" style="color:#1d4ed8;font-size:12px;font-weight:600">Lihat detail &rarr;</a>
                        </div>`);

                    bounds.push([p.lat, p.lng]);
                });

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
                }
                setTimeout(() => map.invalidateSize(), 200);
            });
        </script>
    @endpush
@endif
