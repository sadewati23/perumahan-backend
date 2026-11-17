@extends('layouts.admin')

@section('title', 'Peta Sebaran Perumahan')
@section('subtitle', 'Visualisasi titik lokasi perumahan (GIS)')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <style>
        .leaflet-popup-content { margin: 12px 14px; }
        .marker-pin { width: 16px; height: 16px; border-radius: 9999px; border: 2px solid #fff; box-shadow: 0 1px 4px rgb(0 0 0 / .4); }
    </style>
@endpush

@section('content')
    @php
        $tanpaKoordinat = $perumahans->filter(fn ($p) => ! $p->punya_koordinat);
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-1.5 rounded-pill bg-white px-2.5 py-1 ring-1 ring-inset ring-garis">
                <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                Subsidi
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-pill bg-white px-2.5 py-1 ring-1 ring-inset ring-garis">
                <span class="h-2 w-2 rounded-full bg-sky-500" aria-hidden="true"></span>
                Komersil
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-pill bg-white px-2.5 py-1 ring-1 ring-inset ring-garis">
                <span class="h-2 w-2 rounded-full bg-violet-500" aria-hidden="true"></span>
                Campuran
            </span>
            <span class="text-xs text-slate-600 sm:text-sm">
                <span class="font-semibold text-tinta">{{ $titik->count() }}</span> dari
                <span class="font-semibold text-tinta">{{ $perumahans->count() }}</span> perumahan sudah punya titik
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-button href="{{ route('perumahan.geojson') }}" variant="secondary" size="sm" target="_blank" rel="noopener">
                <x-icon name="eksternal" class="h-4 w-4"/>
                Lihat GeoJSON
            </x-button>
            <x-button href="{{ route('perumahan.index') }}" variant="secondary" size="sm">
                <x-icon name="panah-kiri" class="h-4 w-4"/>
                Daftar data
            </x-button>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <div id="map-sebaran"
                 class="h-[60vh] min-h-80 w-full overflow-hidden rounded-card border border-garis bg-kertas-tua shadow-card"
                 role="img" aria-label="Peta sebaran titik perumahan"></div>
        </div>

        <div class="min-w-0 space-y-4">
            @if ($kecamatanFokus)
                <x-card heading="Fokus Kecamatan" meta="Pilih kecamatan untuk memperbesar area">
                    <x-slot:actions>
                        <button type="button" data-fokus-kecamatan="0"
                                class="inline-flex min-h-11 items-center rounded-control px-2 text-xs font-medium text-slate-600 transition hover:bg-kertas hover:text-brand-700 lg:min-h-0">
                            Reset
                        </button>
                    </x-slot:actions>

                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($kecamatanFokus as $kec)
                            <button type="button" data-fokus-kecamatan="{{ $kec['id'] }}"
                                    class="inline-flex min-h-11 items-center rounded-pill border border-slate-300 px-3 py-1 text-xs font-medium text-slate-700 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 lg:min-h-0 lg:py-1">
                                {{ $kec['nama'] }}
                            </button>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <x-card heading="Cari Lokasi" meta="Nama perumahan atau kecamatan">
                <label for="cari-lokasi" class="sr-only">Cari lokasi</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true">
                        <x-icon name="cari" class="h-4 w-4"/>
                    </span>
                    <input type="text" id="cari-lokasi" placeholder="Nama perumahan / kecamatan..."
                           class="input pl-9">
                </div>

                @if ($titik->isEmpty())
                    <p class="mt-3 rounded-lg bg-kertas px-3 py-2 text-sm text-slate-600">Belum ada perumahan dengan titik koordinat.</p>
                @else
                    <ul id="daftar-lokasi" class="no-scrollbar mt-3 max-h-72 space-y-0.5 overflow-y-auto border-t border-garis pt-2 text-sm">
                        @foreach ($titik as $item)
                            <li data-cari="{{ strtolower($item['nama'].' '.$item['alamat']) }}">
                                <button type="button" data-fokus="{{ $item['id'] }}"
                                        class="w-full rounded-control px-2 py-1.5 text-left transition hover:bg-kertas">
                                    <span class="flex items-center gap-2">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ match ($item['status']) { 'Subsidi' => 'bg-emerald-500', 'Komersil' => 'bg-sky-500', 'Campuran' => 'bg-violet-500', default => 'bg-slate-500' } }}" aria-hidden="true"></span>
                                        <span class="truncate font-medium text-slate-700">{{ $item['nama'] }}</span>
                                    </span>
                                    <span class="block truncate pl-4 text-xs text-slate-600">{{ $item['alamat'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            @if ($tanpaKoordinat->isNotEmpty())
                <div class="rounded-card border border-warning-200 bg-warning-50 p-4 text-sm shadow-card">
                    <p class="flex items-center gap-2 font-semibold text-warning-800">
                        <x-icon name="gps" class="h-4 w-4"/>
                        Belum ada titik koordinat ({{ $tanpaKoordinat->count() }})
                    </p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($tanpaKoordinat as $item)
                            <li>
                                <a href="{{ route('perumahan.edit', $item) }}" class="text-warning-800 underline decoration-warning-300 underline-offset-2 hover:text-warning-900">
                                    {{ $item->nama }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('map-sebaran');
            if (!el || typeof window.L === 'undefined') return;

            const PETA = @json(config('peta'));
            const titik = @json($titik);
            const KECAMATAN = @json($kecamatanFokus);
            const map = L.map(el).setView([PETA.latitude, PETA.longitude], PETA.zoom);

            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(map);

             const colors = @json(\App\Models\Perumahan::warnaPerLabel());
            const markers = {};
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

            const bounds = [];

            titik.forEach((p) => {
                const warna = colors[p.status] || '#64748b';
                const icon = L.divIcon({
                    className: '',
                    html: `<div class="marker-pin" style="background:${warna}"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                });

                const popup = `
                    <div style="min-width:220px">
                        <p style="font-weight:600;margin:0 0 2px">${escapeHtml(p.nama)}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                        <p style="margin:0 0 6px;color:#475569;font-size:12px">${escapeHtml(p.alamat)}</p>
                        <table style="font-size:12px;color:#334155">
                            <tr><td>Jumlah unit</td><td style="text-align:right;padding-left:8px">${formatAngka(p.unit)}</td></tr>
                            <tr><td>Luas lahan</td><td style="text-align:right;padding-left:8px">${formatAngka(p.luas_lahan)} m&sup2;</td></tr>
                            <tr><td>Luas RTH</td><td style="text-align:right;padding-left:8px">${formatAngka(p.rth)} m&sup2;</td></tr>
                            <tr><td>KDB / KDH</td><td style="text-align:right;padding-left:8px">${p.kdb ?? '-'}% / ${p.kdh ?? '-'}%</td></tr>
                        </table>
                        <a href="${encodeURI(p.url)}" style="display:inline-block;margin-top:8px;color:#4f46e5;font-size:12px;font-weight:500">Lihat detail &rarr;</a>
                    </div>`;

                const marker = L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(popup);
                marker.perumahanId = p.id;
                markers[p.id] = marker;
                bounds.push([p.lat, p.lng]);
            });

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
            }

            // Fokus peta ke seluruh titik milik satu kecamatan.
            // id = 0 berarti reset: tampilkan semua titik kembali.
            const fokusKecamatan = (id) => {
                const kec = KECAMATAN.find((k) => k.id === id);

                if (kec && Array.isArray(kec.koordinat) && kec.koordinat.length > 0) {
                    map.fitBounds(L.latLngBounds(kec.koordinat), { padding: [40, 40], maxZoom: PETA.zoom_detail });
                } else if (!id && bounds.length) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
                }

                // Simpan pilihan ke URL agar fokus terjaga saat halaman dibuka ulang.
                const url = new URL(window.location.href);
                if (id) {
                    url.searchParams.set('kecamatan', id);
                } else {
                    url.searchParams.delete('kecamatan');
                }
                window.history.replaceState({}, '', url);
            };

            document.querySelectorAll('[data-fokus-kecamatan]').forEach((btn) => {
                btn.addEventListener('click', () => fokusKecamatan(Number(btn.dataset.fokusKecamatan)));
            });

            // Parameter ?kecamatan=ID memfokuskan peta saat halaman dibuka.
            const fokusAwal = {{ $fokusAktif ?? 0 }};
            if (fokusAwal) {
                fokusKecamatan(fokusAwal);
            }

            // Klik daftar -> fokus marker
            document.querySelectorAll('[data-fokus]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const marker = markers[btn.dataset.fokus];
                    if (marker) {
                        map.setView(marker.getLatLng(), PETA.zoom_detail);
                        marker.openPopup();
                    }
                });
            });

            // Pencarian sederhana
            const inputCari = document.getElementById('cari-lokasi');
            inputCari?.addEventListener('input', () => {
                const kata = inputCari.value.trim().toLowerCase();
                document.querySelectorAll('#daftar-lokasi li').forEach((li) => {
                    li.classList.toggle('hidden', kata !== '' && !li.dataset.cari.includes(kata));
                });
            });

            setTimeout(() => map.invalidateSize(), 200);
        });
    </script>
@endpush
