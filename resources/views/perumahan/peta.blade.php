@extends('layouts.app')

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

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-emerald-500"></span> Subsidi
            </span>
            <span class="flex items-center gap-2">
                <span class="h-3 w-3 rounded-full bg-indigo-500"></span> Komersil
            </span>
            <span class="text-slate-500">{{ $titik->count() }} dari {{ $perumahans->count() }} perumahan sudah punya titik</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('perumahan.geojson') }}" target="_blank"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Lihat GeoJSON
            </a>
            <a href="{{ route('perumahan.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Daftar Data
            </a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <div id="map-sebaran" class="h-[70vh] w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm"></div>
        </div>

        <div class="space-y-4">
            @if ($kecamatanFokus)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fokus Kecamatan</p>
                        <button type="button" data-fokus-kecamatan="0"
                                class="text-xs font-medium text-slate-500 transition hover:text-indigo-600">Reset</button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($kecamatanFokus as $kec)
                            <button type="button" data-fokus-kecamatan="{{ $kec['id'] }}"
                                    class="rounded-full border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700">
                                {{ $kec['nama'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <label class="mb-1 block text-xs font-medium text-slate-600">Cari lokasi</label>
                <input type="text" id="cari-lokasi" placeholder="Nama perumahan / kecamatan..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">

                <ul id="daftar-lokasi" class="mt-3 max-h-72 space-y-1 overflow-y-auto text-sm">
                    @foreach ($titik as $item)
                        <li data-cari="{{ strtolower($item['nama'].' '.$item['alamat']) }}">
                            <button type="button" data-fokus="{{ $item['id'] }}"
                                    class="w-full rounded-md px-2 py-1.5 text-left transition hover:bg-slate-50">
                                <span class="flex items-center gap-2">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $item['status'] === 'Subsidi' ? 'bg-emerald-500' : 'bg-indigo-500' }}"></span>
                                    <span class="truncate font-medium text-slate-700">{{ $item['nama'] }}</span>
                                </span>
                                <span class="block truncate pl-4 text-xs text-slate-500">{{ $item['alamat'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>

                @if ($titik->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada perumahan dengan titik koordinat.</p>
                @endif
            </div>

            @if ($tanpaKoordinat->isNotEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm shadow-sm">
                    <p class="font-semibold text-amber-800">Belum ada titik koordinat ({{ $tanpaKoordinat->count() }})</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($tanpaKoordinat as $item)
                            <li>
                                <a href="{{ route('perumahan.edit', $item) }}" class="text-amber-900 hover:underline">
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

            const colors = { Subsidi: '#10b981', Komersil: '#6366f1' };
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
