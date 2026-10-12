@extends('layouts.publik')

@section('title', 'Peta Sebaran')
@section('description', 'Peta sebaran lokasi perumahan beserta status subsidi atau komersil.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-500">
                <a href="{{ route('publik.beranda') }}" class="hover:text-indigo-600">Beranda</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700">Peta Sebaran</span>
            </nav>

            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900">Peta Sebaran Perumahan</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $titik->count() }} perumahan memiliki titik koordinat dari {{ $perumahans->count() }} data yang cocok filter.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-500"></span> Subsidi</span>
                    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-indigo-500"></span> Komersil</span>
                    <a href="{{ route('publik.peta.geojson') }}" target="_blank" rel="noopener"
                       class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        GeoJSON
                    </a>
                </div>
            </div>

            <form method="GET" class="mt-6 grid gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Pencarian</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama perumahan / kelurahan / kecamatan..."
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Kecamatan</label>
                    <select name="kecamatan_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                        <option value="">Semua kecamatan</option>
                        @foreach ($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}" @selected(($filters['kecamatan_id'] ?? '') == $kecamatan->id)>{{ $kecamatan->nama }}</option>
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

                <div class="flex gap-2 md:col-span-4">
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('publik.peta') }}"
                       class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="order-2 lg:order-1 lg:col-span-3">
                <div id="peta-publik" class="h-[65vh] max-sm:h-[60vh] w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-sm"></div>
            </div>

            <div class="order-1 space-y-4 lg:order-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Saring daftar lokasi</label>
                    <input type="text" id="cari-lokasi" placeholder="Ketik nama perumahan..."
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">                        <ul id="daftar-lokasi" class="no-scrollbar mt-3 max-h-80 divide-y divide-slate-100 overflow-y-auto text-sm">
                        @foreach ($titik as $item)
                            <li data-cari="{{ strtolower($item['nama'].' '.$item['alamat']) }}">
                                <button type="button" data-fokus="{{ $item['id'] }}"
                                        class="w-full py-2 text-left transition hover:text-indigo-600">
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
                        <p class="mt-3 text-sm text-slate-500">Tidak ada perumahan dengan titik koordinat pada filter ini.</p>
                    @endif
                </div>

                @php $tanpaKoordinat = $perumahans->filter(fn ($p) => ! $p->punya_koordinat); @endphp

                @if ($tanpaKoordinat->isNotEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm shadow-sm">
                        <p class="font-semibold text-amber-800">Belum memiliki titik koordinat ({{ $tanpaKoordinat->count() }})</p>
                        <ul class="mt-2 max-h-56 space-y-1 overflow-y-auto text-amber-900">
                            @foreach ($tanpaKoordinat as $item)
                                <li><a href="{{ route('publik.perumahan.show', $item) }}" class="hover:underline">{{ $item->nama }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('peta-publik');
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
                    html: `<div style="width:16px;height:16px;border-radius:9999px;background:${warna};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                });

                const marker = L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(`
                    <div style="min-width:230px">
                        <p style="font-weight:600;margin:0 0 2px">${escapeHtml(p.nama)}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                        <p style="margin:0 0 6px;color:#475569;font-size:12px">${escapeHtml(p.alamat)}</p>
                        <table style="font-size:12px;color:#334155">
                            <tr><td>Jumlah unit</td><td style="text-align:right;padding-left:8px">${formatAngka(p.unit)}</td></tr>
                            <tr><td>Luas lahan</td><td style="text-align:right;padding-left:8px">${formatAngka(p.luas_lahan)} m&sup2;</td></tr>
                            <tr><td>Luas RTH</td><td style="text-align:right;padding-left:8px">${formatAngka(p.rth)} m&sup2;</td></tr>
                            <tr><td>KDB / KDH</td><td style="text-align:right;padding-left:8px">${p.kdb ?? '-'}% / ${p.kdh ?? '-'}%</td></tr>
                            <tr><td>Tahun selesai</td><td style="text-align:right;padding-left:8px">${p.tahun_selesai ?? '-'}</td></tr>
                        </table>
                        <a href="${encodeURI(p.url)}" style="display:inline-block;margin-top:8px;color:#4f46e5;font-size:12px;font-weight:500">Lihat detail &rarr;</a>
                    </div>`);

                markers[p.id] = marker;
                bounds.push([p.lat, p.lng]);
            });

            if (bounds.length) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
            }

            document.querySelectorAll('[data-fokus]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const marker = markers[btn.dataset.fokus];
                    if (marker) {
                        map.setView(marker.getLatLng(), PETA.zoom_detail);
                        marker.openPopup();
                    }
                });
            });

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
