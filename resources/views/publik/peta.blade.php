@extends('layouts.publik')

@section('title', 'Peta Sebaran')
@section('description', 'Peta sebaran lokasi perumahan beserta status subsidi, komersil, atau campuran.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    <section class="border-b border-garis bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-600">
                <a href="{{ route('publik.beranda') }}" class="hover:text-brand-600">Beranda</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700">Peta Sebaran</span>
            </nav>

            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="font-judul text-3xl font-semibold tracking-tight text-tinta sm:text-4xl">Peta sebaran perumahan</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $titik->count() }} perumahan memiliki titik koordinat dari {{ $perumahans->count() }} data yang cocok filter.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-sm text-slate-700">
                    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-500" aria-hidden="true"></span> Subsidi</span>
                    <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-sky-500" aria-hidden="true"></span> Komersil</span>
                    <span class="flex items-center gap-2"><span class="h-3 w-3 rotate-45 rounded-sm bg-violet-500" aria-hidden="true"></span> Campuran</span>
                    <a href="{{ route('publik.peta.geojson') }}" target="_blank" rel="noopener" class="tombol-sekunder">
                        Unduh GeoJSON
                    </a>
                </div>
            </div>

            <form method="GET" class="mt-6 grid gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label for="filter-peta-q" class="mb-1 block text-xs font-medium text-slate-600">Pencarian</label>
                    <input type="text" id="filter-peta-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama perumahan / kelurahan / kecamatan…"
                           class="input">
                </div>
                <div>
                    <label for="filter-peta-kecamatan" class="mb-1 block text-xs font-medium text-slate-600">Kecamatan</label>
                    <select id="filter-peta-kecamatan" name="kecamatan_id" class="input">
                        <option value="">Semua kecamatan</option>
                        @foreach ($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}" @selected(($filters['kecamatan_id'] ?? '') == $kecamatan->id)>{{ $kecamatan->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-peta-status" class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                    <select id="filter-peta-status" name="status" class="input">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 md:col-span-4">
                    <button type="submit" class="tombol-utama">Terapkan filter</button>
                    <a href="{{ route('publik.peta') }}" class="tombol-sekunder">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="order-2 lg:order-1 lg:col-span-3">
                <div id="peta-publik" class="h-[65vh] w-full overflow-hidden rounded-card border border-garis bg-kertas-tua shadow-card max-sm:h-[60vh]"></div>
            </div>

            <div class="order-1 space-y-4 lg:order-2">
                <div class="rounded-card border border-garis bg-white p-4 shadow-card">
                    <label for="cari-lokasi" class="mb-1 block text-xs font-medium text-slate-600">Saring daftar lokasi</label>
                    <input type="text" id="cari-lokasi" placeholder="Ketik nama perumahan…" class="input">
                    <ul id="daftar-lokasi" class="no-scrollbar mt-3 max-h-80 divide-y divide-garis overflow-y-auto text-sm">
                        @foreach ($titik as $item)
                            <li data-cari="{{ strtolower($item['nama'].' '.$item['alamat']) }}">
                                <button type="button" data-fokus="{{ $item['id'] }}"
                                        class="w-full py-2 text-left transition hover:text-brand-600">
                                    <span class="flex items-center gap-2">
                                         <span class="h-2 w-2 shrink-0 {{ match ($item['status']) { 'Subsidi' => 'rounded-full bg-emerald-500', 'Komersil' => 'rounded-sm bg-sky-500', 'Campuran' => 'rotate-45 rounded-sm bg-violet-500', default => 'rounded-full bg-slate-500' } }}"></span>
                                        <span class="truncate font-medium text-slate-700">{{ $item['nama'] }}</span>
                                    </span>
                                    <span class="block truncate pl-4 text-xs text-slate-600">{{ $item['alamat'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    @if ($titik->isEmpty())
                        <p class="mt-3 text-sm text-slate-600">Tidak ada perumahan dengan titik koordinat pada filter ini.</p>
                    @endif
                </div>

                @php $tanpaKoordinat = $perumahans->filter(fn ($p) => ! $p->punya_koordinat); @endphp

                @if ($tanpaKoordinat->isNotEmpty())
                    <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm shadow-card">
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
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
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

             const colors = @json(\App\Models\Perumahan::warnaPerLabel());
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
                const bentuk = p.status === 'Komersil'
                    ? 'border-radius:2px'
                    : p.status === 'Campuran'
                        ? 'border-radius:2px;transform:rotate(45deg)'
                        : 'border-radius:9999px';
                const icon = L.divIcon({
                    className: '',
                    html: `<div style="width:16px;height:16px;${bentuk};background:${warna};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
                    iconSize: [16, 16],
                    iconAnchor: [8, 8],
                });

                const marker = L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(`
                    <div style="min-width:230px;font-family:'Instrument Sans',sans-serif">
                        <p style="font-weight:600;margin:0 0 2px;color:#16211c">${escapeHtml(p.nama)}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                        <p style="margin:0 0 6px;color:#3f4a44;font-size:12px">${escapeHtml(p.alamat)}</p>
                        <table style="font-size:12px;color:#3f4a44">
                            <tr><td>Jumlah unit</td><td style="text-align:right;padding-left:8px">${formatAngka(p.unit)}</td></tr>
                            <tr><td>Luas lahan</td><td style="text-align:right;padding-left:8px">${formatAngka(p.luas_lahan)} m&sup2;</td></tr>
                            <tr><td>Luas RTH</td><td style="text-align:right;padding-left:8px">${formatAngka(p.rth)} m&sup2;</td></tr>
                            <tr><td>KDB / KDH</td><td style="text-align:right;padding-left:8px">${p.kdb ?? '-'}% / ${p.kdh ?? '-'}%</td></tr>
                            <tr><td>Tahun selesai</td><td style="text-align:right;padding-left:8px">${p.tahun_selesai ?? '-'}</td></tr>
                        </table>
                        <a href="${encodeURI(p.url)}" style="display:inline-block;margin-top:8px;color:#1d4ed8;font-size:12px;font-weight:600">Lihat detail &rarr;</a>
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
