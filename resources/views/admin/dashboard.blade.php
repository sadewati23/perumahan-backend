@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data perumahan')

@section('content')
    @php
        $kartu = [
            ['label' => 'Total Perumahan', 'value' => number_format($statistik['total_perumahan'], 0, ',', '.'), 'satuan' => 'lokasi'],
            ['label' => 'Total Unit Rumah', 'value' => number_format($statistik['total_unit'], 0, ',', '.'), 'satuan' => 'unit'],
            ['label' => 'Luas Lahan', 'value' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
            ['label' => 'Luas RTH', 'value' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'), 'satuan' => 'hektar'],
        ];
        $maxUnit = max(1, (int) $perKecamatan->max('total_unit'));
        $maxTahun = max(1, (int) $perTahun->max('jumlah'));

        $labelKondisi = [
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
        ];
        $warnaKondisi = [
            'baik' => '#10b981',
            'rusak_ringan' => '#f59e0b',
            'rusak_berat' => '#ef4444',
        ];
        $kondisiLabels = $kondisiPsu->keys()->map(fn ($k) => $labelKondisi[$k] ?? $k)->values();
        $kondisiWarna = $kondisiPsu->keys()->map(fn ($k) => $warnaKondisi[$k] ?? '#64748b')->values();
    @endphp

    <div class="rounded-card border border-garis border-l-4 border-l-amber-500 bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-tinta">Laporan menunggu penanganan</p>
                <p class="mt-1 font-judul text-4xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['baru'], 0, ',', '.') }}</p>
                <p class="text-xs text-slate-600">laporan baru menunggu tindak lanjut</p>
            </div>
            <a href="{{ route('laporan.index', ['status' => 'baru']) }}" class="tombol-utama">
                Tangani sekarang
            </a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $item)
            <a href="{{ route('perumahan.index') }}"
               class="block rounded-card border border-garis bg-white p-5 shadow-card transition hover:border-brand-300 hover:shadow-card">
                <p class="angka-utama">{{ $item['value'] }}</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $item['label'] }}</p>
                <p class="text-xs text-slate-600">{{ $item['satuan'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Total Entri PSU</p>
            <p class="mt-2 text-xl font-semibold text-tinta">{{ number_format($statistik['total_psu'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-600">prasarana, sarana &amp; utilitas terdata</p>
        </div>
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">PSU Rusak</p>
            <p class="mt-2 text-xl font-semibold text-rose-600">{{ number_format($statistik['psu_rusak'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-600">rusak ringan + rusak berat</p>
        </div>
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Belum Punya Titik</p>
            <p class="mt-2 text-xl font-semibold text-tinta">{{ number_format($statistik['belum_koordinat'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-600">koordinat GIS belum diisi</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Status Perumahan</p>
            <div class="mt-4 space-y-3">
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-emerald-700">Subsidi</span>
                        <span class="text-slate-600">{{ $statistik['jumlah_subsidi'] }} perumahan</span>
                    </div>
                    @php $persenSubsidi = $statistik['total_perumahan'] ? round($statistik['jumlah_subsidi'] / $statistik['total_perumahan'] * 100) : 0; @endphp
                    <div class="mt-1 h-2 w-full rounded-full bg-kertas-tua">
                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $persenSubsidi }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-sky-700">Komersil</span>
                        <span class="text-slate-600">{{ $statistik['jumlah_komersil'] }} perumahan</span>
                    </div>
                    @php $persenKomersil = $statistik['total_perumahan'] ? round($statistik['jumlah_komersil'] / $statistik['total_perumahan'] * 100) : 0; @endphp
                    <div class="mt-1 h-2 w-full rounded-full bg-kertas-tua">
                        <div class="h-2 rounded-full bg-sky-500" style="width: {{ $persenKomersil }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-violet-700">Campuran</span>
                        <span class="text-slate-600">{{ $statistik['jumlah_campuran'] }} perumahan</span>
                    </div>
                    @php $persenCampuran = $statistik['total_perumahan'] ? round($statistik['jumlah_campuran'] / $statistik['total_perumahan'] * 100) : 0; @endphp
                    <div class="mt-1 h-2 w-full rounded-full bg-kertas-tua">
                        <div class="h-2 rounded-full bg-violet-500" style="width: {{ $persenCampuran }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-5 space-y-2 border-t border-garis pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Perumahan punya lahan pemakaman</span>
                    <span class="font-medium text-slate-800">{{ $statistik['jumlah_pemakaman'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Belum ada titik koordinat</span>
                    <span class="font-medium text-rose-600">{{ $statistik['belum_koordinat'] }}</span>
                </div>
            </div>
        </div>

        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Jumlah perumahan per kecamatan</p>
                <a href="{{ route('perumahan.index') }}" class="text-xs font-medium text-brand-600 hover:underline">Lihat semua</a>
            </div>

            @forelse ($perKecamatan as $kecamatan)
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $kecamatan->nama }}</span>
                        <span class="text-slate-600">
                            {{ $kecamatan->perumahans_count }} perumahan &middot;
                            {{ number_format((int) $kecamatan->total_unit, 0, ',', '.') }} unit &middot;
                            {{ number_format((float) $kecamatan->total_luas_lahan / 10000, 2, ',', '.') }} ha
                        </span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-kertas-tua">
                        <div class="h-2 rounded-full bg-brand-500"
                             style="width: {{ round((int) $kecamatan->total_unit / $maxUnit * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="mt-6 text-sm text-slate-600">Belum ada data kecamatan.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Perumahan selesai dibangun per tahun</p>

            @if ($perTahun->isEmpty())
                <p class="mt-6 text-sm text-slate-600">Belum ada data tahun selesai pembangunan.</p>
            @else
                <div class="mt-4 h-64"><canvas id="grafik-tahun" role="img" aria-label="Grafik batang jumlah perumahan yang selesai dibangun dan unit rumah per tahun" class="h-64 w-full"></canvas></div>
                <p class="sr-only">Grafik menampilkan jumlah perumahan yang selesai dibangun dan jumlah unit rumah per tahun dari data terdata.</p>
            @endif
        </div>

        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Kondisi PSU</p>

            @if ($totalPsuTerdata === 0)
                <p class="mt-6 text-sm text-slate-600">Belum ada data kondisi PSU.</p>
            @else
                <div class="mt-4 flex h-64 items-center">
                    <div class="relative h-full w-1/2"><canvas id="grafik-kondisi" role="img" aria-label="Diagram kondisi prasarana dan sarana umum"></canvas></div>
                    <p class="sr-only">Diagram lingkaran menunjukkan jumlah PSU dengan kondisi baik, rusak ringan, dan rusak berat.</p>
                    <ul class="w-1/2 space-y-2 pl-4 text-sm">
                        @foreach ($kondisiPsu as $kondisi => $jumlah)
                            <li class="flex items-center justify-between gap-2">
                                <span class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-sm" style="background: {{ $warnaKondisi[$kondisi] ?? '#64748b' }}"></span>
                                    {{ $labelKondisi[$kondisi] ?? $kondisi }}
                                </span>
                                <span class="font-medium text-slate-700">{{ $jumlah }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card lg:col-span-2">
            <p class="text-sm font-medium text-slate-600">Jenis PSU terbanyak</p>

            @if ($jenisPsu->isEmpty())
                <p class="mt-6 text-sm text-slate-600">Belum ada data PSU.</p>
            @else
                <div class="mt-4 h-56"><canvas id="grafik-jenis-psu" role="img" aria-label="Grafik jenis prasarana dan sarana umum terbanyak"></canvas></div>
                <p class="sr-only">Grafik batang menampilkan jenis PSU dengan jumlah entri terbanyak.</p>
            @endif
        </div>

        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Input terbaru</p>
                <a href="{{ route('perumahan.create') }}" class="text-xs font-medium text-brand-600 hover:underline">+ Tambah</a>
            </div>

            <ul class="mt-4 divide-y divide-garis">
                @forelse ($terbaru as $item)
                    <li class="py-3">
                        <a href="{{ route('perumahan.show', $item) }}" class="block">
                            <p class="text-sm font-medium text-slate-800 hover:text-brand-600">{{ $item->nama }}</p>
                            <p class="mt-0.5 text-xs text-slate-600">
                                Kel. {{ $item->kelurahan->nama ?? '-' }} &middot; {{ $item->jumlah_unit }} unit
                            </p>
                        </a>
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-600">Belum ada data perumahan.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card">
            <p class="text-sm font-medium text-slate-600">Laporan Warga</p>

            <div class="mt-3 grid grid-cols-3 divide-x divide-garis border-y border-garis text-center">
                <a href="{{ route('laporan.index', ['jenis' => 'kerusakan']) }}" class="px-2 py-3 transition hover:bg-kertas">
                    <p class="text-xs text-slate-600">Kerusakan</p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['kerusakan'], 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'pengaduan']) }}" class="px-2 py-3 transition hover:bg-kertas">
                    <p class="text-xs text-slate-600">Pengaduan</p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['pengaduan'], 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'bangunan_liar']) }}" class="px-2 py-3 transition hover:bg-kertas">
                    <p class="text-xs text-slate-600">Bgn. liar</p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['bangunan_liar'], 0, ',', '.') }}</p>
                </a>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-lg border border-garis bg-white p-3">
                    <p class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>Baru
                    </p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['baru'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-garis bg-white p-3">
                    <p class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-sky-500" aria-hidden="true"></span>Diproses
                    </p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['diproses'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-garis bg-white p-3">
                    <p class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Selesai
                    </p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['selesai'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-garis bg-kertas p-3">
                    <p class="text-xs text-slate-600">Total laporan</p>
                    <p class="mt-0.5 font-judul text-xl font-semibold tabular-nums text-tinta">{{ number_format($laporanStatistik['total'], 0, ',', '.') }}</p>
                </div>
            </div>

            <a href="{{ route('laporan.index') }}" class="tombol-sekunder mt-4 w-full">
                Kelola laporan
            </a>
        </div>

        <div class="min-w-0 rounded-card border border-garis bg-white p-5 shadow-card lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Laporan warga terbaru</p>
                <a href="{{ route('laporan.index') }}" class="text-xs font-medium text-brand-600 hover:underline">Lihat semua</a>
            </div>

            <ul class="mt-4 divide-y divide-garis">
                @forelse ($laporanTerbaru as $laporan)
                    <li class="py-3">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('laporan.show', $laporan) }}" class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800 hover:text-brand-600">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-600">
                                    {{ $laporan->kode }} &middot; {{ $laporan->lokasi_ringkas }} &middot;
                                    {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                                </p>
                            </a>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_status }}">{{ $laporan->label_status }}</span>
                                 @if (auth()->user()->isAdmin())
                                     <form method="POST" action="{{ route('laporan.destroy', $laporan) }}" class="inline" data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan.">
                                         @csrf
                                         @method('DELETE')
                                         <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                             Hapus
                                         </button>
                                     </form>
                                 @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-600">Belum ada laporan dari warga.</li>
                @endforelse
            </ul>
        </div>
    </div>

@endsection

@if ($perTahun->isNotEmpty() || $totalPsuTerdata > 0 || $jenisPsu->isNotEmpty())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.js" integrity="sha384-4W8gMhOYqk0FPg9sAMkb3g/P2fXtzbUyDUUgMlA1EEig8z/U/13EYUjErc1H382b" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { Chart.defaults.animation = false; }
                const opsiDasar = { responsive: true, maintainAspectRatio: false };

                @if ($perTahun->isNotEmpty())
                    new Chart(document.getElementById('grafik-tahun'), {
                        type: 'bar',
                        data: {
                            labels: @json($perTahun->pluck('tahun_selesai')),
                            datasets: [{
                                label: 'Perumahan selesai',
                                data: @json($perTahun->pluck('jumlah')),
                                backgroundColor: '#1d4ed8',
                                borderRadius: 6,
                            }, {
                                label: 'Unit rumah',
                                data: @json($perTahun->pluck('unit')),
                                backgroundColor: '#93c5fd',
                                borderRadius: 6,
                            }],
                        },
                        options: { ...opsiDasar, scales: { y: { beginAtZero: true } } },
                    });
                @endif

                @if ($totalPsuTerdata > 0)
                    new Chart(document.getElementById('grafik-kondisi'), {
                        type: 'doughnut',
                        data: {
                            labels: @json($kondisiLabels),
                            datasets: [{
                                data: @json($kondisiPsu->values()),
                                backgroundColor: @json($kondisiWarna),
                                borderWidth: 2,
                            }],
                        },
                        options: {
                            ...opsiDasar,
                            cutout: '62%',
                            plugins: { legend: { display: false } },
                        },
                    });
                @endif

                @if ($jenisPsu->isNotEmpty())
                    new Chart(document.getElementById('grafik-jenis-psu'), {
                        type: 'bar',
                        data: {
                            labels: @json($jenisPsu->keys()),
                            datasets: [{
                                label: 'Jumlah entri',
                                data: @json($jenisPsu->values()),
                                backgroundColor: '#10b981',
                                borderRadius: 6,
                            }],
                        },
                        options: {
                            ...opsiDasar,
                            indexAxis: 'y',
                            scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                @endif
            });
        </script>
    @endpush
@endif
