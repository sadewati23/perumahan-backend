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

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $item)
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $item['warna'] }}">{{ $item['label'] }}</span>
                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $item['value'] }}</p>
                <p class="text-xs text-slate-500">{{ $item['satuan'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Belum Punya Polygon</p>
            <p class="mt-2 text-xl font-semibold text-amber-600">{{ number_format($statistik['belum_polygon'], 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500">batas area belum digambar</p>
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
                <div class="mt-4 h-64"><canvas id="grafik-tahun"></canvas></div>
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Kondisi PSU</p>

            @if ($totalPsuTerdata === 0)
                <p class="mt-6 text-sm text-slate-500">Belum ada data kondisi PSU.</p>
            @else
                <div class="mt-4 flex h-64 items-center">
                    <div class="relative h-full w-1/2"><canvas id="grafik-kondisi"></canvas></div>
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
                <div class="mt-4 h-56"><canvas id="grafik-jenis-psu"></canvas></div>
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
@endsection

@if ($perTahun->isNotEmpty() || $totalPsuTerdata > 0 || $jenisPsu->isNotEmpty())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
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
