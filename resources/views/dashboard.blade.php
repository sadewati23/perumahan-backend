@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data perumahan')

@section('content')
    @php
        $kartu = [
            ['label' => 'Total Perumahan', 'value' => number_format($statistik['total_perumahan'], 0, ',', '.'), 'satuan' => 'lokasi', 'warna' => 'bg-indigo-50 text-indigo-700'],
            ['label' => 'Total Unit Rumah', 'value' => number_format($statistik['total_unit'], 0, ',', '.'), 'satuan' => 'unit', 'warna' => 'bg-sky-50 text-sky-700'],
            ['label' => 'Luas Lahan', 'value' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'), 'satuan' => 'hektar', 'warna' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Luas RTH', 'value' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'), 'satuan' => 'hektar', 'warna' => 'bg-emerald-50 text-emerald-700'],
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

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Laporan menunggu penanganan</p>
                <p class="mt-1 text-3xl font-semibold text-amber-900">{{ number_format($laporanStatistik['baru'], 0, ',', '.') }}</p>
                <p class="text-xs text-amber-700">laporan baru menunggu tindak lanjut</p>
            </div>
            <a href="{{ route('laporan.index', ['status' => 'baru']) }}"
               class="rounded-control bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">
                Tangani sekarang
            </a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $item)
            <a href="{{ route('perumahan.index') }}"
               class="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-300 hover:shadow-card">
                <p class="text-3xl font-semibold text-slate-900">{{ $item['value'] }}</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $item['label'] }}</p>
                <p class="text-xs text-slate-500">{{ $item['satuan'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total Entri PSU</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format($statistik['total_psu'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500">prasarana, sarana &amp; utilitas terdata</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">PSU Rusak</p>
            <p class="mt-2 text-xl font-semibold text-rose-600">{{ number_format($statistik['psu_rusak'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500">rusak ringan + rusak berat</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Belum Punya Titik</p>
            <p class="mt-2 text-xl font-semibold text-slate-900">{{ number_format($statistik['belum_koordinat'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500">koordinat GIS belum diisi</p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Status Perumahan</p>
            <div class="mt-4 space-y-3">
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-emerald-700">Subsidi</span>
                        <span class="text-slate-600">{{ $statistik['jumlah_subsidi'] }} perumahan</span>
                    </div>
                    @php $persenSubsidi = $statistik['total_perumahan'] ? round($statistik['jumlah_subsidi'] / $statistik['total_perumahan'] * 100) : 0; @endphp
                    <div class="mt-1 h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $persenSubsidi }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-indigo-700">Komersil</span>
                        <span class="text-slate-600">{{ $statistik['jumlah_komersil'] }} perumahan</span>
                    </div>
                    @php $persenKomersil = $statistik['total_perumahan'] ? round($statistik['jumlah_komersil'] / $statistik['total_perumahan'] * 100) : 0; @endphp
                    <div class="mt-1 h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $persenKomersil }}%"></div>
                    </div>
                </div>
            </div>

            <div class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Perumahan punya lahan pemakaman</span>
                    <span class="font-medium text-slate-800">{{ $statistik['jumlah_pemakaman'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Belum ada titik koordinat</span>
                    <span class="font-medium text-rose-600">{{ $statistik['belum_koordinat'] }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Jumlah perumahan per kecamatan</p>
                <a href="{{ route('perumahan.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat semua</a>
            </div>

            @forelse ($perKecamatan as $kecamatan)
                <div class="mt-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $kecamatan->nama }}</span>
                        <span class="text-slate-500">
                            {{ $kecamatan->perumahans_count }} perumahan &middot;
                            {{ number_format((int) $kecamatan->total_unit, 0, ',', '.') }} unit &middot;
                            {{ number_format((float) $kecamatan->total_luas_lahan / 10000, 2, ',', '.') }} ha
                        </span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-indigo-500"
                             style="width: {{ round((int) $kecamatan->total_unit / $maxUnit * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="mt-6 text-sm text-slate-500">Belum ada data kecamatan.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Perumahan selesai dibangun per tahun</p>

            @if ($perTahun->isEmpty())
                <p class="mt-6 text-sm text-slate-500">Belum ada data tahun selesai pembangunan.</p>
            @else
                <div class="mt-4 h-64"><canvas id="grafik-tahun" role="img" aria-label="Grafik batang jumlah perumahan yang selesai dibangun dan unit rumah per tahun" class="h-64 w-full"></canvas></div>
                <p class="sr-only">Grafik menampilkan jumlah perumahan yang selesai dibangun dan jumlah unit rumah per tahun dari data terdata.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Kondisi PSU</p>

            @if ($totalPsuTerdata === 0)
                <p class="mt-6 text-sm text-slate-500">Belum ada data kondisi PSU.</p>
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
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-sm font-medium text-slate-500">Jenis PSU terbanyak</p>

            @if ($jenisPsu->isEmpty())
                <p class="mt-6 text-sm text-slate-500">Belum ada data PSU.</p>
            @else
                <div class="mt-4 h-56"><canvas id="grafik-jenis-psu" role="img" aria-label="Grafik jenis prasarana dan sarana umum terbanyak"></canvas></div>
                <p class="sr-only">Grafik batang menampilkan jenis PSU dengan jumlah entri terbanyak.</p>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Input terbaru</p>
                <a href="{{ route('perumahan.create') }}" class="text-xs font-medium text-indigo-600 hover:underline">+ Tambah</a>
            </div>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($terbaru as $item)
                    <li class="py-3">
                        <a href="{{ route('perumahan.show', $item) }}" class="block">
                            <p class="text-sm font-medium text-slate-800 hover:text-indigo-600">{{ $item->nama }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                Kel. {{ $item->kelurahan->nama ?? '-' }} &middot; {{ $item->jumlah_unit }} unit
                            </p>
                        </a>
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-500">Belum ada data perumahan.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Laporan Warga</p>

            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <a href="{{ route('laporan.index', ['jenis' => 'kerusakan']) }}" class="rounded-lg bg-indigo-50 p-2 transition hover:bg-indigo-100">
                    <p class="text-xs text-indigo-700">Kerusakan</p>
                    <p class="text-lg font-semibold text-indigo-800">{{ number_format($laporanStatistik['kerusakan'], 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'pengaduan']) }}" class="rounded-lg bg-violet-50 p-2 transition hover:bg-violet-100">
                    <p class="text-xs text-violet-700">Pengaduan</p>
                    <p class="text-lg font-semibold text-violet-800">{{ number_format($laporanStatistik['pengaduan'], 0, ',', '.') }}</p>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'bangunan_liar']) }}" class="rounded-lg bg-orange-50 p-2 transition hover:bg-orange-100">
                    <p class="text-xs text-orange-700">Bgn. Liar</p>
                    <p class="text-lg font-semibold text-orange-800">{{ number_format($laporanStatistik['bangunan_liar'], 0, ',', '.') }}</p>
                </a>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-amber-50 p-3">
                    <p class="text-xs text-amber-700">Baru</p>
                    <p class="mt-0.5 text-xl font-semibold text-amber-800">{{ number_format($laporanStatistik['baru'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-sky-50 p-3">
                    <p class="text-xs text-sky-700">Diproses</p>
                    <p class="mt-0.5 text-xl font-semibold text-sky-800">{{ number_format($laporanStatistik['diproses'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-emerald-50 p-3">
                    <p class="text-xs text-emerald-700">Selesai</p>
                    <p class="mt-0.5 text-xl font-semibold text-emerald-800">{{ number_format($laporanStatistik['selesai'], 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="text-xs text-slate-600">Total laporan</p>
                    <p class="mt-0.5 text-xl font-semibold text-slate-800">{{ number_format($laporanStatistik['total'], 0, ',', '.') }}</p>
                </div>
            </div>

            <a href="{{ route('laporan.index') }}"
               class="mt-4 block rounded-lg bg-slate-900 px-4 py-2 text-center text-sm font-semibold text-white transition hover:bg-slate-800">
                Kelola Laporan
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Laporan warga terbaru</p>
                <a href="{{ route('laporan.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Lihat semua</a>
            </div>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($laporanTerbaru as $laporan)
                    <li class="py-3">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('laporan.show', $laporan) }}" class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800 hover:text-indigo-600">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">
                                    {{ $laporan->kode }} &middot; {{ $laporan->lokasi_ringkas }} &middot;
                                    {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                                </p>
                            </a>
                            <div class="shrink-0 flex items-center gap-2">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_status }}">{{ $laporan->label_status }}</span>
                                <button type="button"
                                        data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan."
                                        data-action="{{ route('laporan.destroy', $laporan) }}"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-500">Belum ada laporan dari warga.</li>
                @endforelse
            </ul>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-confirm]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!window.confirm(button.dataset.confirm)) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = button.dataset.action;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
@endpush
@endsection

@if ($perTahun->isNotEmpty() || $totalPsuTerdata > 0 || $jenisPsu->isNotEmpty())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.js" integrity="sha384-4W8gMhOYqk0FPg9sAMkb3g/P2fXtzbUyDUUgMlA1EEig8z/U/13EYUjErc1H382b" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const opsiDasar = { responsive: true, maintainAspectRatio: false };

                @if ($perTahun->isNotEmpty())
                    new Chart(document.getElementById('grafik-tahun'), {
                        type: 'bar',
                        data: {
                            labels: @json($perTahun->pluck('tahun_selesai')),
                            datasets: [{
                                label: 'Perumahan selesai',
                                data: @json($perTahun->pluck('jumlah')),
                                backgroundColor: '#6366f1',
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
