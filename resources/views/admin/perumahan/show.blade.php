@extends('layouts.admin')

@section('title', $perumahan->nama)
@section('subtitle', 'Detail data perumahan')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    @php $tema = $perumahan->status_theme; @endphp

    {{-- Identitas utama --}}
    <div class="overflow-hidden rounded-card border border-garis border-l-4 {{ $tema['rail_left'] }} bg-white p-4 shadow-card sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-kertas-tua text-slate-600 ring-1 ring-garis" aria-hidden="true">
                        <x-icon name="perumahan" class="h-5 w-5"/>
                    </span>
                    <h2 class="font-judul text-xl font-semibold tracking-tight text-tinta">{{ $perumahan->nama }}</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-pill px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->status_badge_class }}">
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                        {{ $perumahan->status_label }}
                    </span>
                    @if ($perumahan->punya_koordinat)
                        <span class="inline-flex items-center gap-1.5 rounded-pill bg-kertas-tua px-2.5 py-1 font-mono text-xs font-medium text-slate-700 ring-1 ring-inset ring-garis">
                            <x-icon name="gps" class="h-3.5 w-3.5"/>
                            {{ $perumahan->latitude }}, {{ $perumahan->longitude }}
                        </span>
                    @endif
                </div>

                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                    <span>{{ $perumahan->alamat_lengkap ?: 'Alamat belum dilengkapi' }}</span>
                    @if ($perumahan->pengembang)
                        <span aria-hidden="true" class="text-slate-300">&middot;</span>
                        <span>Pengembang: <span class="font-medium text-slate-700">{{ $perumahan->pengembang }}</span></span>
                    @endif
                </p>
            </div>

            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <x-button href="{{ route('perumahan.peta') }}" variant="secondary" class="flex-1 sm:flex-none">
                    <x-icon name="peta" class="h-4 w-4"/>
                    Peta sebaran
                </x-button>
                <x-button href="{{ route('perumahan.edit', $perumahan) }}" class="flex-1 sm:flex-none">
                    <x-icon name="pensil" class="h-4 w-4"/>
                    Ubah data
                </x-button>
                <x-button href="{{ route('perumahan.index') }}" variant="ghost" class="flex-1 sm:flex-none">
                    <x-icon name="panah-kiri" class="h-4 w-4"/>
                    Kembali
                </x-button>
            </div>
        </div>
    </div>

    {{-- Ringkasan angka kunci --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Luas Lahan" :value="number_format($perumahan->luas_lahan_ha, 2, ',', '.')" satuan="hektar"
                :meta="number_format((float) $perumahan->luas_lahan, 0, ',', '.').' m²'" icon="lahan" tone="brand" />
        <x-stat label="Jumlah Unit" :value="number_format($perumahan->jumlah_unit, 0, ',', '.')" satuan="unit rumah"
                :meta="$perumahan->total_unit_tipe ? number_format($perumahan->total_unit_tipe, 0, ',', '.').' unit terinci' : null"
                icon="rumah" tone="info" />
        <x-stat label="Ruang Terbuka Hijau" :value="$perumahan->jumlah_rth" satuan="titik"
                :meta="number_format((float) $perumahan->luas_rth, 0, ',', '.').' m²'" icon="dedaunan" tone="success" />
        <x-stat label="KDB / KDH" :value="($perumahan->kdb !== null ? (float) $perumahan->kdb : '–').' / '.($perumahan->kdh !== null ? (float) $perumahan->kdh : '–')" satuan="persen"
                meta="Koefisien dasar / hijau" icon="kotak" tone="neutral" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-card heading="Lokasi Administratif">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Kelurahan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->nama ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Kecamatan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Kabupaten / Kota</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->kelurahan->kecamatan->kabupaten_kota ?? '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">RT / RW</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $perumahan->rt ?: '-' }} / {{ $perumahan->rw ?: '-' }}
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Pengembang</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->pengembang ?: '-' }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card heading="Luasan & Unit">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Luas lahan</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }} m&sup2;
                                <span class="block text-xs font-normal text-slate-600">{{ number_format($perumahan->luas_lahan_ha, 2, ',', '.') }} ha</span>
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Jumlah unit</dt>
                            <dd class="text-right font-medium text-slate-800">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }} unit</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Jumlah RTH</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->jumlah_rth }} titik</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Luas RTH</dt>
                            <dd class="text-right font-medium text-slate-800">{{ number_format((float) $perumahan->luas_rth, 0, ',', '.') }} m&sup2;</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">KDB / KDH</dt>
                            <dd class="text-right font-medium text-slate-800">
                                {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }} /
                                {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card heading="Waktu Pembangunan">
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Tahun pembuatan</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->tahun_pembuatan ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Tahun selesai</dt>
                            <dd class="text-right font-medium text-slate-800">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-600">Lama pembangunan</dt>
                            <dd class="text-right font-medium text-slate-800">
                                @if ($perumahan->tahun_pembuatan && $perumahan->tahun_selesai)
                                    {{ max(0, $perumahan->tahun_selesai - $perumahan->tahun_pembuatan) }} tahun
                                @else
                                    -
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-card>

                <x-card heading="Lahan Pemakaman">
                    @if ($perumahan->ada_lahan_pemakaman)
                        <dl class="space-y-2.5 text-sm">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-600">Ketersediaan</dt>
                                <dd class="text-right">
                                    <x-badge tone="success">Tersedia</x-badge>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-600">Luas</dt>
                                <dd class="text-right font-medium text-slate-800">
                                    {{ $perumahan->luas_lahan_pemakaman !== null ? number_format((float) $perumahan->luas_lahan_pemakaman, 0, ',', '.') . ' m²' : '-' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-600">Lokasi</dt>
                                <dd class="text-right font-medium text-slate-800">{{ $perumahan->lokasi_lahan_pemakaman ?: '-' }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-600">Tidak tersedia lahan pemakaman.</p>
                    @endif
                </x-card>
            </div>

            <x-card heading="Unit & Tipe Rumah"
                    :meta="$perumahan->tipeUnits->isNotEmpty() ? 'Total '.number_format($perumahan->total_unit_tipe, 0, ',', '.').' unit terinci' : null">
                @if ($perumahan->tipeUnits->isEmpty())
                    <p class="text-sm text-slate-600">Belum ada rincian tipe unit.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="tabel-mini min-w-full divide-y divide-garis text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="whitespace-nowrap py-2 pr-4">Tipe</th>
                                    <th class="whitespace-nowrap py-2 pr-4 text-right">Jumlah</th>
                                    <th class="whitespace-nowrap py-2 pr-4 text-right">L. Tanah</th>
                                    <th class="whitespace-nowrap py-2 pr-4 text-right">L. Bangunan</th>
                                    <th class="py-2 pr-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-garis">
                                @foreach ($perumahan->tipeUnits as $tipe)
                                    <tr>
                                        <td data-label="Tipe" class="py-3 pr-4 font-medium text-slate-700">{{ $tipe->tipe }}</td>
                                        <td data-label="Jumlah" class="py-3 pr-4 text-right text-slate-700">{{ number_format($tipe->jumlah_unit, 0, ',', '.') }}</td>
                                        <td data-label="L. Tanah" class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_tanah !== null ? number_format((float) $tipe->luas_tanah, 0, ',', '.') . ' m²' : '-' }}</td>
                                        <td data-label="L. Bangunan" class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_bangunan !== null ? number_format((float) $tipe->luas_bangunan, 0, ',', '.') . ' m²' : '-' }}</td>
                                        <td data-label="Keterangan" class="py-3 pr-4 text-slate-600">{{ $tipe->keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card heading="Data PSU"
                    meta="Prasarana, sarana & utilitas">
                @if ($perumahan->psu->isEmpty())
                    <p class="text-sm text-slate-600">Belum ada data PSU.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="tabel-mini min-w-full divide-y divide-garis text-sm">
                            <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                <tr>
                                    <th class="py-2 pr-4">Jenis PSU</th>
                                    <th class="py-2 pr-4 text-right">Jumlah</th>
                                    <th class="py-2 pr-4">Kondisi</th>
                                    <th class="py-2 pr-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-garis">
                                @foreach ($perumahan->psu as $psu)
                                    <tr>
                                        <td data-label="Jenis PSU" class="py-3 pr-4 font-medium text-slate-700">{{ $psu->jenis }}</td>
                                        <td data-label="Jumlah" class="py-3 pr-4 text-right text-slate-700">
                                            {{ rtrim(rtrim(number_format((float) $psu->jumlah, 2, ',', '.'), '0'), ',') }}
                                            {{ $psu->satuan }}
                                        </td>
                                        <td data-label="Kondisi" class="py-3 pr-4 text-slate-600">{{ $psu->label_kondisi }}</td>
                                        <td data-label="Keterangan" class="py-3 pr-4 text-slate-600">{{ $psu->keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        <div class="min-w-0 space-y-4">
            <x-card heading="Titik Lokasi" meta="Posisi perumahan pada peta">
                @if ($perumahan->punya_koordinat)
                    <div id="map-detail" class="h-64 w-full overflow-hidden rounded-lg border border-garis bg-kertas-tua"
                         role="img" aria-label="Peta lokasi {{ $perumahan->nama }}"></div>
                    <a href="https://www.openstreetmap.org/?mlat={{ $perumahan->latitude }}&mlon={{ $perumahan->longitude }}#map=17/{{ $perumahan->latitude }}/{{ $perumahan->longitude }}"
                       target="_blank" rel="noopener" class="tautan-aksi mt-3">
                        Buka di OpenStreetMap
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @else
                    <p class="text-sm text-slate-600">
                        Titik koordinat belum diisi.
                        <a href="{{ route('perumahan.edit', $perumahan) }}" class="font-medium text-brand-700 hover:underline">Lengkapi sekarang</a>.
                    </p>
                @endif
            </x-card>

            <x-card heading="Keterangan">
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $perumahan->keterangan ?: 'Tidak ada keterangan tambahan.' }}</p>
            </x-card>

            <x-card heading="Riwayat Data">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-600">Dibuat</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $perumahan->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-600">Diperbarui</dt>
                        <dd class="text-right font-medium text-slate-700">{{ $perumahan->updated_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>

                @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}" class="mt-4 border-t border-garis pt-4"
                          data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" class="w-full">
                            <x-icon name="sampah" class="h-4 w-4"/>
                            Hapus perumahan
                        </x-button>
                    </form>
                @endif
            </x-card>
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
