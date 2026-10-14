@extends('layouts.app')

@section('title', $perumahan->nama)
@section('subtitle', 'Detail data perumahan')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-lg font-semibold text-slate-900">{{ $perumahan->nama }}</h2>
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->badge_status }}">
                    {{ $perumahan->label_status }}
                </span>
                @if ($perumahan->punya_koordinat)
                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-300">
                        {{ $perumahan->latitude }}, {{ $perumahan->longitude }}
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-600">{{ $perumahan->alamat_lengkap ?: 'Alamat belum dilengkapi' }}</p>
        </div>

        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <a href="{{ route('perumahan.peta') }}"
               class="flex-1 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:flex-none">
                Peta Sebaran
            </a>
            <a href="{{ route('perumahan.edit', $perumahan) }}"
               class="flex-1 whitespace-nowrap rounded-lg bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-amber-600 sm:flex-none">
                Ubah Data
            </a>
            <a href="{{ route('perumahan.index') }}"
               class="flex-1 whitespace-nowrap rounded-lg border border-slate-300 bg-white px-4 py-2 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:flex-none">
                Kembali
            </a>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi Administratif</p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Kelurahan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->nama ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Kecamatan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Kabupaten / Kota</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->kabupaten_kota ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">RT / RW</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $perumahan->rt ?: '-' }} / {{ $perumahan->rw ?: '-' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Pengembang</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->pengembang ?: '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Luasan &amp; Unit</p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Luas lahan</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }} m&sup2;
                                ({{ number_format($perumahan->luas_lahan_ha, 2, ',', '.') }} ha)
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Jumlah unit</dt>
                            <dd class="text-right font-medium text-slate-800">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }} unit</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Jumlah RTH</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->jumlah_rth }} titik</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Luas RTH</dt>
                            <dd class="text-right font-medium text-slate-800">{{ number_format((float) $perumahan->luas_rth, 0, ',', '.') }} m&sup2;</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">KDB / KDH</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }} /
                                {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Waktu Pembangunan</p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Tahun pembuatan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->tahun_pembuatan ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Tahun selesai</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">Lama pembangunan</dt>
                            <dd class="text-right font-medium text-slate-800">
                                @if ($perumahan->tahun_pembuatan && $perumahan->tahun_selesai)
                                    {{ max(0, $perumahan->tahun_selesai - $perumahan->tahun_pembuatan) }} tahun
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lahan Pemakaman</p>
                    @if ($perumahan->ada_lahan_pemakaman)
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Ketersediaan</dt>
                                <dd class="font-medium text-emerald-700">Tersedia</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Luas</dt>
                                <dd class="text-right font-medium text-slate-800">
                                    {{ $perumahan->luas_lahan_pemakaman !== null ? number_format((float) $perumahan->luas_lahan_pemakaman, 0, ',', '.') . ' m²' : '-' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Lokasi</dt>
                                <dd class="text-right font-medium text-slate-800">{{ $perumahan->lokasi_lahan_pemakaman ?: '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-slate-500">Tidak tersedia lahan pemakaman.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Unit &amp; Tipe Rumah</p>

                @if ($perumahan->tipeUnits->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada rincian tipe unit.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Tipe</th>
                                    <th class="px-3 py-2 text-right">Jumlah</th>
                                    <th class="px-3 py-2 text-right">L. Tanah</th>
                                    <th class="px-3 py-2 text-right">L. Bangunan</th>
                                    <th class="px-3 py-2">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($perumahan->tipeUnits as $tipe)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-700">{{ $tipe->tipe }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format($tipe->jumlah_unit, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">{{ $tipe->luas_tanah !== null ? number_format((float) $tipe->luas_tanah, 0, ',', '.') . ' m²' : '-' }}</td>
                                        <td class="px-3 py-2 text-right">{{ $tipe->luas_bangunan !== null ? number_format((float) $tipe->luas_bangunan, 0, ',', '.') . ' m²' : '-' }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $tipe->keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 text-sm font-semibold text-slate-700">
                                <tr>
                                    <td class="px-3 py-2" colspan="1">Total</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($perumahan->total_unit_tipe, 0, ',', '.') }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Data PSU (Prasarana, Sarana &amp; Utilitas)</p>

                @if ($perumahan->psu->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada data PSU.</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Jenis PSU</th>
                                    <th class="px-3 py-2 text-right">Jumlah</th>
                                    <th class="px-3 py-2">Kondisi</th>
                                    <th class="px-3 py-2">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($perumahan->psu as $psu)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-700">{{ $psu->jenis }}</td>
                                        <td class="px-3 py-2 text-right">
                                            {{ rtrim(rtrim(number_format((float) $psu->jumlah, 2, ',', '.'), '0'), ',') }}
                                            {{ $psu->satuan }}
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $psu->label_kondisi }}</td>
                                        <td class="px-3 py-2 text-slate-500">{{ $psu->keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Titik Lokasi</p>
                @if ($perumahan->punya_koordinat)
                    <div id="map-detail" class="mt-3 h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100"></div>
                    <a href="https://www.openstreetmap.org/?mlat={{ $perumahan->latitude }}&mlon={{ $perumahan->longitude }}#map=17/{{ $perumahan->latitude }}/{{ $perumahan->longitude }}"
                       target="_blank" rel="noopener"
                       class="mt-2 inline-block text-xs font-medium text-indigo-600 hover:underline">
                        Buka di OpenStreetMap &rarr;
                    </a>
                @else
                    <p class="mt-3 text-sm text-slate-500">
                        Titik koordinat belum diisi.
                        <a href="{{ route('perumahan.edit', $perumahan) }}" class="font-medium text-indigo-600 hover:underline">Lengkapi sekarang</a>.
                    </p>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Keterangan</p>
                <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $perumahan->keterangan ?: 'Tidak ada keterangan tambahan.' }}</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-800">Riwayat Data</p>
                <dl class="mt-2 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Dibuat</dt>
                        <dd class="font-medium text-slate-700">{{ $perumahan->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Diperbarui</dt>
                        <dd class="font-medium text-slate-700">{{ $perumahan->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}" class="mt-4"
                      data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                        Hapus Perumahan
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@if ($perumahan->punya_koordinat)
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('map-detail');
                if (!el || typeof window.L === 'undefined') return;

                const PETA = @json(config('peta'));
                const lat = {{ (float) $perumahan->latitude }};
                const lng = {{ (float) $perumahan->longitude }};

                const map = L.map(el).setView([lat, lng], PETA.zoom_detail);
                L.tileLayer(PETA.tile_url, {
                    maxZoom: 19,
                    attribution: PETA.tile_attribution,
                }).addTo(map);

                L.marker([lat, lng]).addTo(map).bindPopup(@json(e($perumahan->nama))).openPopup();

                setTimeout(() => map.invalidateSize(), 200);
            });
        </script>
    @endpush
@endif
