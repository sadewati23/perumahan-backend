@extends('layouts.app')

@section('title', 'Peta Sebaran Perumahan')
@section('subtitle', 'Visualisasi titik lokasi perumahan (GIS)')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('map-sebaran');
            if (!el || typeof window.L === 'undefined') return;

            const PETA = @json(config('peta'));
            const titik = @json($titik);
            const map = L.map(el).setView([PETA.latitude, PETA.longitude], PETA.zoom);

            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(map);

            const colors = { Subsidi: '#10b981', Komersil: '#6366f1' };
            const markers = {};
            const formatAngka = (angka) => new Intl.NumberFormat('id-ID').format(angka);

            const bounds = [];
            const warnaHex = { subsidi: '#10b981', komersil: '#6366f1' };

            // Gambar polygon batas area perumahan (bila ada).
            titik.forEach((p) => {
                if (!Array.isArray(p.polygon) || p.polygon.length < 3) return;

                L.polygon(p.polygon, {
                    color: warnaHex[p.status_kode] || '#64748b',
                    fillColor: warnaHex[p.status_kode] || '#64748b',
                    fillOpacity: 0.15,
                    weight: 2,
                }).addTo(map).bindPopup(`<strong>${p.nama}</strong><br>Batas area perumahan`);

                p.polygon.forEach((t) => bounds.push(t));
            });

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
                        <p style="font-weight:600;margin:0 0 2px">${p.nama}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${p.status}</p>
                        <p style="margin:0 0 6px;color:#475569;font-size:12px">${p.alamat ?? ''}</p>
                        <table style="font-size:12px;color:#334155">
                            <tr><td>Jumlah unit</td><td style="text-align:right;padding-left:8px">${formatAngka(p.unit)}</td></tr>
                            <tr><td>Luas lahan</td><td style="text-align:right;padding-left:8px">${formatAngka(p.luas_lahan)} m&sup2;</td></tr>
                            <tr><td>Luas RTH</td><td style="text-align:right;padding-left:8px">${formatAngka(p.rth)} m&sup2;</td></tr>
                            <tr><td>KDB / KDH</td><td style="text-align:right;padding-left:8px">${p.kdb ?? '-'}% / ${p.kdh ?? '-'}%</td></tr>
                            ${p.luas_polygon ? `<tr><td>Luas polygon</td><td style="text-align:right;padding-left:8px">${formatAngka(Math.round(p.luas_polygon))} m&sup2;</td></tr>` : ''}
                        </table>
                        <a href="${p.url}" style="display:inline-block;margin-top:8px;color:#4f46e5;font-size:12px;font-weight:500">Lihat detail &rarr;</a>
                    </div>`;

                const marker = L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(popup);
                marker.perumahanId = p.id;
                markers[p.id] = marker;
                bounds.push([p.lat, p.lng]);
            });

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
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
