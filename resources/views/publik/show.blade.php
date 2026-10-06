@extends('layouts.publik')

@section('title', $perumahan->nama)
@section('description', 'Detail data perumahan '.$perumahan->nama.' di Kel. '.($perumahan->kelurahan->nama ?? '-').', Kec. '.($perumahan->kelurahan->kecamatan->nama ?? '-').'.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-500">
                <a href="{{ route('publik.beranda') }}" class="hover:text-indigo-600">Beranda</a>
                <span class="mx-1">/</span>
                <a href="{{ route('publik.perumahan.index') }}" class="hover:text-indigo-600">Data Perumahan</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700">{{ $perumahan->nama }}</span>
            </nav>

            <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-slate-900">{{ $perumahan->nama }}</h1>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->badge_status }}">
                            {{ $perumahan->label_status }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ $perumahan->alamat_lengkap ?: 'Alamat belum dilengkapi' }}</p>
                    <p class="mt-1 text-xs text-slate-500">Pengembang: {{ $perumahan->pengembang ?: 'tidak tercatat' }}</p>
                </div>

                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <a href="{{ route('lapor.create', ['perumahan' => $perumahan->id]) }}"
                       class="flex-1 whitespace-nowrap rounded-lg bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-amber-600 sm:flex-none">
                        Lapor Kerusakan
                    </a>
                    <a href="{{ route('publik.peta') }}"
                       class="flex-1 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:flex-none">
                        Peta Sebaran
                    </a>
                    <a href="{{ route('publik.perumahan.index') }}"
                       class="flex-1 whitespace-nowrap rounded-lg bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-slate-800 sm:flex-none">
                        Daftar Perumahan
                    </a>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Luas Lahan</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }} m&sup2;</p>
                    <p class="text-xs text-slate-500">{{ number_format($perumahan->luas_lahan_ha, 2, ',', '.') }} hektar</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Jumlah Unit</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500">{{ $perumahan->tipeUnits->count() }} tipe unit</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">Ruang Terbuka Hijau</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ number_format((float) $perumahan->luas_rth, 0, ',', '.') }} m&sup2;</p>
                    <p class="text-xs text-slate-500">{{ $perumahan->jumlah_rth }} titik RTH</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">KDB / KDH</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }} /
                        {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}
                    </p>
                    <p class="text-xs text-slate-500">Koefisien dasar bangunan / hijau</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Lokasi Administratif</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-slate-500">Kelurahan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->nama ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Kecamatan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Kabupaten / Kota</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->kabupaten_kota ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">RT / RW</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->rt ?: '-' }} / {{ $perumahan->rw ?: '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-500">Alamat</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->alamat ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Waktu &amp; Lahan Pemakaman</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-slate-500">Tahun pembuatan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->tahun_pembuatan ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Tahun selesai pembangunan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Lahan pemakaman</dt>
                            <dd class="text-sm font-medium {{ $perumahan->ada_lahan_pemakaman ? 'text-emerald-700' : 'text-slate-800' }}">
                                {{ $perumahan->ada_lahan_pemakaman ? 'Tersedia' : 'Tidak tersedia' }}
                            </dd>
                        </div>
                        @if ($perumahan->ada_lahan_pemakaman)
                            <div>
                                <dt class="text-xs text-slate-500">Luas lahan pemakaman</dt>
                                <dd class="text-sm font-medium text-slate-800">
                                    {{ $perumahan->luas_lahan_pemakaman !== null ? number_format((float) $perumahan->luas_lahan_pemakaman, 0, ',', '.') . ' m²' : '-' }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-xs text-slate-500">Lokasi lahan pemakaman</dt>
                                <dd class="text-sm font-medium text-slate-800">{{ $perumahan->lokasi_lahan_pemakaman ?: '-' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">Unit &amp; Tipe Rumah</h2>
                        @if ($perumahan->tipeUnits->isNotEmpty())
                            <span class="text-xs text-slate-500">
                                Total {{ number_format($perumahan->total_unit_tipe, 0, ',', '.') }} unit terinci
                            </span>
                        @endif
                    </div>

                    @if ($perumahan->tipeUnits->isEmpty())
                        <p class="mt-4 text-sm text-slate-500">Rincian tipe unit belum tersedia.</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="whitespace-nowrap py-2 pr-4">Tipe</th>
                                        <th class="whitespace-nowrap py-2 pr-4 text-right">Jumlah</th>
                                        <th class="whitespace-nowrap py-2 pr-4 text-right">L. Tanah</th>
                                        <th class="whitespace-nowrap py-2 pr-4 text-right">L. Bangunan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($perumahan->tipeUnits as $tipe)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-slate-700">{{ $tipe->tipe }}</td>
                                            <td class="py-3 pr-4 text-right text-slate-600">{{ number_format($tipe->jumlah_unit, 0, ',', '.') }} unit</td>
                                            <td class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_tanah !== null ? number_format((float) $tipe->luas_tanah, 0, ',', '.') . ' m²' : '-' }}</td>
                                            <td class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_bangunan !== null ? number_format((float) $tipe->luas_bangunan, 0, ',', '.') . ' m²' : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">Prasarana, Sarana &amp; Utilitas (PSU)</h2>
                        <a href="{{ route('lapor.create', ['perumahan' => $perumahan->id]) }}"
                           class="text-xs font-medium text-amber-600 hover:underline">
                            Ada yang rusak? Laporkan &rarr;
                        </a>
                    </div>

                    @if ($perumahan->psu->isEmpty())
                        <p class="mt-4 text-sm text-slate-500">Data PSU belum tersedia.</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="whitespace-nowrap py-2 pr-4">Jenis PSU</th>
                                        <th class="whitespace-nowrap py-2 pr-4 text-right">Jumlah</th>
                                        <th class="whitespace-nowrap py-2 pr-4">Kondisi</th>
                                        <th class="whitespace-nowrap py-2">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($perumahan->psu as $psu)
                                        <tr>
                                            <td class="py-3 pr-4 font-medium text-slate-700">{{ $psu->jenis }}</td>
                                            <td class="py-3 pr-4 text-right text-slate-600">
                                                {{ rtrim(rtrim(number_format((float) $psu->jumlah, 2, ',', '.'), '0'), ',') }} {{ $psu->satuan }}
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $psu->label_kondisi }}</td>
                                            <td class="py-3 text-slate-500">{{ $psu->keterangan ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                @if ($perumahan->keterangan)
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-900">Keterangan</h2>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $perumahan->keterangan }}</p>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Titik Lokasi</h2>

                    @if ($perumahan->punya_koordinat)
                        <div id="peta-detail-publik" class="mt-3 h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100"></div>
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Latitude</dt>
                                <dd class="font-medium text-slate-800">{{ $perumahan->latitude }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Longitude</dt>
                                <dd class="font-medium text-slate-800">{{ $perumahan->longitude }}</dd>
                            </div>
                        </dl>
                        <a href="https://www.openstreetmap.org/?mlat={{ $perumahan->latitude }}&mlon={{ $perumahan->longitude }}#map=17/{{ $perumahan->latitude }}/{{ $perumahan->longitude }}"
                           target="_blank" rel="noopener"
                           class="mt-3 inline-block text-xs font-medium text-indigo-600 hover:underline">
                            Buka di OpenStreetMap &rarr;
                        </a>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Titik koordinat perumahan ini belum tersedia.</p>
                    @endif
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Perumahan Lain di Kecamatan Ini</h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        @forelse ($lainnya as $item)
                            <li class="py-3">
                                <a href="{{ route('publik.perumahan.show', $item) }}" class="block">
                                    <p class="text-sm font-medium text-slate-800 hover:text-indigo-600">{{ $item->nama }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $item->label_status }} &middot; {{ $item->jumlah_unit }} unit &middot;
                                        Kel. {{ $item->kelurahan->nama ?? '-' }}
                                    </p>
                                </a>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-slate-500">Belum ada perumahan lain di kecamatan ini.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-xs text-slate-500">
                    <p>Terakhir diperbarui: {{ $perumahan->updated_at?->translatedFormat('d F Y, H:i') ?? '-' }}</p>
                    <p class="mt-2">
                        Data bersumber dari pendataan petugas. Menemukan data yang perlu diperbarui?
                        Silakan hubungi petugas pendataan wilayah Anda.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($perumahan->punya_koordinat)
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('peta-detail-publik');
                if (!el || typeof window.L === 'undefined') return;

                const PETA = @json(config('peta'));
                const lat = {{ (float) $perumahan->latitude }};
                const lng = {{ (float) $perumahan->longitude }};

                const map = L.map(el).setView([lat, lng], PETA.zoom_detail);
                L.tileLayer(PETA.tile_url, {
                    maxZoom: 19,
                    attribution: PETA.tile_attribution,
                }).addTo(map);

                L.marker([lat, lng]).addTo(map).bindPopup(@json($perumahan->nama)).openPopup();

                setTimeout(() => map.invalidateSize(), 200);
            });
        </script>
    @endpush
@endif
