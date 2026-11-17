@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data perumahan')

@section('content')
    @php
        $kartu = [
            [
                'label' => 'Total Perumahan',
                'value' => number_format($statistik['total_perumahan'], 0, ',', '.'),
                'satuan' => 'lokasi terdata',
                'icon' => 'perumahan',
                'tone' => 'brand',
                'href' => route('perumahan.index'),
            ],
            [
                'label' => 'Total Unit Rumah',
                'value' => number_format($statistik['total_unit'], 0, ',', '.'),
                'satuan' => 'unit rumah',
                'icon' => 'rumah',
                'tone' => 'info',
                'href' => route('perumahan.index'),
            ],
            [
                'label' => 'Luas Lahan',
                'value' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'),
                'satuan' => 'hektar',
                'icon' => 'lahan',
                'tone' => 'neutral',
                'href' => route('perumahan.index'),
            ],
            [
                'label' => 'Luas RTH',
                'value' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'),
                'satuan' => 'hektar',
                'icon' => 'dedaunan',
                'tone' => 'success',
                'href' => route('perumahan.index'),
            ],
        ];

        $totalPerumahan = (int) $statistik['total_perumahan'];
        $persen = fn (int $jumlah): int => $totalPerumahan > 0 ? (int) round($jumlah / $totalPerumahan * 100) : 0;

        $komposisiStatus = [
            ['label' => 'Subsidi', 'jumlah' => (int) $statistik['jumlah_subsidi'], 'bar' => 'bg-emerald-500', 'teks' => 'text-emerald-700'],
            ['label' => 'Komersil', 'jumlah' => (int) $statistik['jumlah_komersil'], 'bar' => 'bg-sky-500', 'teks' => 'text-sky-700'],
            ['label' => 'Campuran', 'jumlah' => (int) $statistik['jumlah_campuran'], 'bar' => 'bg-violet-500', 'teks' => 'text-violet-700'],
        ];

        $maxUnit = max(1, (int) $perKecamatan->max('total_unit'));

        $labelKondisi = [
            'baik' => 'Baik',
            'rusak_ringan' => 'Rusak Ringan',
            'rusak_berat' => 'Rusak Berat',
        ];
        $warnaKondisi = [
            'baik' => 'var(--chart-3)',
            'rusak_ringan' => 'var(--chart-4)',
            'rusak_berat' => 'var(--chart-5)',
        ];
        $kondisiLabels = $kondisiPsu->keys()->map(fn ($k) => $labelKondisi[$k] ?? $k)->values();
        $kondisiWarna = $kondisiPsu->keys()->map(fn ($k) => $warnaKondisi[$k] ?? 'var(--chart-6)')->values();
        $totalKondisi = max(1, (int) $totalPsuTerdata);
    @endphp

    {{-- Aksi cepat --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Diperbarui <span class="font-medium text-tinta">{{ now()->translatedFormat('d F Y') }}</span>
        </p>
        <x-button href="{{ route('perumahan.create') }}">
            <x-icon name="plus" class="h-4 w-4"/>
            Tambah perumahan
        </x-button>
    </div>

    {{-- Angka utama --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $item)
            <x-stat
                :label="$item['label']"
                :value="$item['value']"
                :satuan="$item['satuan']"
                :icon="$item['icon']"
                :tone="$item['tone']"
                :href="$item['href']"/>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- Perlu ditindak --}}
        <x-card class="border-l-4 border-l-warning-500" heading="Perlu ditindak" meta="Laporan warga dan data lapangan">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-judul text-4xl font-semibold tabular-nums tracking-tight text-tinta">
                        {{ number_format($laporanStatistik['baru'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-600">laporan baru menunggu tindak lanjut</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-warning-50 text-warning-700 ring-1 ring-inset ring-warning-200">
                    <x-icon name="laporan" class="h-5 w-5"/>
                </span>
            </div>

            <x-button href="{{ route('laporan.index', ['status' => 'baru']) }}" class="mt-4 w-full">
                Tangani sekarang
                <x-icon name="panah-kanan" class="h-4 w-4"/>
            </x-button>

            <dl class="mt-4 space-y-2 border-t border-garis pt-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-600">PSU Rusak</dt>
                    <dd class="font-semibold tabular-nums text-danger-700">{{ number_format($statistik['psu_rusak'], 0, ',', '.') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-600">Belum punya titik koordinat</dt>
                    <dd class="font-semibold tabular-nums text-tinta">{{ number_format($statistik['belum_koordinat'], 0, ',', '.') }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-600">Punya lahan pemakaman</dt>
                    <dd class="font-semibold tabular-nums text-tinta">{{ number_format($statistik['jumlah_pemakaman'], 0, ',', '.') }}</dd>
                </div>
            </dl>
        </x-card>

        {{-- Tren pembangunan --}}
        <x-card class="lg:col-span-2" heading="Perumahan selesai dibangun per tahun" meta="Sepuluh tahun terakhir yang memiliki data">
            @if ($perTahun->isEmpty())
                <p class="text-sm text-slate-600">Belum ada data tahun selesai pembangunan.</p>
            @else
                <div class="h-64">
                    <canvas id="grafik-tahun" role="img"
                            aria-label="Grafik batang jumlah perumahan yang selesai dibangun dan unit rumah per tahun"
                            data-grafik="tahun"
                            data-kategori="{{ json_encode($perTahun->pluck('tahun_selesai')) }}"
                            data-seri-a="{{ json_encode($perTahun->pluck('jumlah')) }}"
                            data-seri-b="{{ json_encode($perTahun->pluck('unit')) }}"></canvas>
                </div>
                <p class="sr-only">Grafik menampilkan jumlah perumahan yang selesai dibangun dan jumlah unit rumah per tahun dari data terdata.</p>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        {{-- Kondisi PSU --}}
        <x-card heading="Kondisi PSU" meta="{{ number_format($statistik['total_psu'], 0, ',', '.') }} entri PSU terdata">
            @if ($totalPsuTerdata === 0)
                <p class="text-sm text-slate-600">Belum ada data kondisi PSU.</p>
            @else
                <div class="flex flex-wrap items-center gap-5">
                    <div class="relative h-48 w-48 shrink-0">
                        <canvas id="grafik-kondisi" role="img" aria-label="Diagram kondisi prasarana dan sarana umum"
                                data-grafik="kondisi"
                                data-kategori="{{ json_encode($kondisiLabels) }}"
                                data-seri-a="{{ json_encode($kondisiPsu->values()) }}"
                                data-warna="{{ json_encode($kondisiWarna) }}"></canvas>
                    </div>

                    <ul class="min-w-0 flex-1 space-y-2.5 text-sm">
                        @foreach ($kondisiPsu as $kondisi => $jumlah)
                            <li class="flex items-center justify-between gap-3">
                                <span class="flex min-w-0 items-center gap-2 text-slate-700">
                                    <span class="h-3 w-3 shrink-0 rounded-sm" style="background-color: {{ $warnaKondisi[$kondisi] ?? 'var(--chart-6)' }}" aria-hidden="true"></span>
                                    <span class="truncate">{{ $labelKondisi[$kondisi] ?? $kondisi }}</span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="font-semibold tabular-nums text-tinta">{{ number_format((int) $jumlah, 0, ',', '.') }}</span>
                                    <span class="ml-1 text-xs text-slate-600">{{ round((int) $jumlah / $totalKondisi * 100) }}%</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <p class="sr-only">Diagram lingkaran menunjukkan jumlah PSU dengan kondisi baik, rusak ringan, dan rusak berat.</p>
            @endif
        </x-card>

        {{-- Jenis PSU --}}
        <x-card heading="Jenis PSU terbanyak" meta="Enam jenis dengan entri terbanyak">
            @if ($jenisPsu->isEmpty())
                <p class="text-sm text-slate-600">Belum ada data PSU.</p>
            @else
                <div class="h-56">
                    <canvas id="grafik-jenis-psu" role="img" aria-label="Grafik jenis prasarana dan sarana umum terbanyak"
                            data-grafik="jenis"
                            data-kategori="{{ json_encode($jenisPsu->keys()) }}"
                            data-seri-a="{{ json_encode($jenisPsu->values()) }}"></canvas>
                </div>
                <p class="sr-only">Grafik batang menampilkan jenis PSU dengan jumlah entri terbanyak.</p>
            @endif
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- Sebaran per kecamatan --}}
        <x-card class="lg:col-span-2" heading="Perumahan per kecamatan" meta="Delapan kecamatan dengan perumahan terbanyak">
            <x-slot:actions>
                <a href="{{ route('perumahan.index') }}" class="tautan-aksi">Lihat semua</a>
            </x-slot:actions>

            @forelse ($perKecamatan as $kecamatan)
                <div class="mt-4 first:mt-0">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 text-sm">
                        <span class="font-medium text-slate-700">{{ $kecamatan->nama }}</span>
                        <span class="text-xs text-slate-600">
                            {{ $kecamatan->perumahans_count }} perumahan &middot;
                            {{ number_format((int) $kecamatan->total_unit, 0, ',', '.') }} unit &middot;
                            {{ number_format((float) $kecamatan->total_luas_lahan / 10000, 2, ',', '.') }} ha
                        </span>
                    </div>
                    <div class="mt-1 h-2 w-full rounded-full bg-kertas-tua">
                        <div class="h-2 rounded-full bg-brand-500" style="width: {{ round((int) $kecamatan->total_unit / $maxUnit * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-600">Belum ada data kecamatan.</p>
            @endforelse
        </x-card>

        {{-- Komposisi status --}}
        <x-card heading="Komposisi status" meta="{{ number_format($totalPerumahan, 0, ',', '.') }} perumahan">
            <div class="flex h-3 overflow-hidden rounded-pill bg-kertas-tua"
                 role="img"
                 aria-label="{{ $komposisiStatus[0]['label'] }} {{ $komposisiStatus[0]['jumlah'] }} perumahan, {{ $komposisiStatus[1]['label'] }} {{ $komposisiStatus[1]['jumlah'] }}, {{ $komposisiStatus[2]['label'] }} {{ $komposisiStatus[2]['jumlah'] }}">
                @foreach ($komposisiStatus as $status)
                    @if ($status['jumlah'] > 0)
                        <div class="{{ $status['bar'] }}" style="width: {{ $persen($status['jumlah']) }}%"></div>
                    @endif
                @endforeach
            </div>

            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($komposisiStatus as $status)
                    <li class="flex items-center justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $status['bar'] }}" aria-hidden="true"></span>
                            <span class="truncate font-medium {{ $status['teks'] }}">{{ $status['label'] }}</span>
                        </span>
                        <span class="shrink-0 text-right text-slate-600">
                            <span class="font-semibold tabular-nums text-tinta">{{ number_format($status['jumlah'], 0, ',', '.') }}</span>
                            <span class="ml-1 text-xs">({{ $persen($status['jumlah']) }}%)</span>
                        </span>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('perumahan.index') }}" class="mt-4 flex min-h-11 items-center justify-between gap-2 rounded-lg border border-garis px-3 py-2.5 text-xs font-medium text-slate-700 transition hover:bg-kertas lg:min-h-0">
                <span>Lihat data perumahan</span>
                <x-icon name="panah-kanan" class="h-4 w-4 text-slate-500"/>
            </a>
        </x-card>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- Laporan warga terbaru --}}
        <x-card class="lg:col-span-2" heading="Laporan warga" meta="Status dan laporan terbaru dari warga">
            <x-slot:actions>
                <a href="{{ route('laporan.index') }}" class="tautan-aksi">Lihat semua</a>
            </x-slot:actions>

            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-garis bg-garis text-center sm:grid-cols-4">
                @php
                    $ringkasStatus = [
                        ['label' => 'Baru', 'nilai' => $laporanStatistik['baru'], 'route' => ['status' => 'baru'], 'warna' => 'bg-warning-500'],
                        ['label' => 'Diproses', 'nilai' => $laporanStatistik['diproses'], 'route' => ['status' => 'diproses'], 'warna' => 'bg-info-500'],
                        ['label' => 'Selesai', 'nilai' => $laporanStatistik['selesai'], 'route' => ['status' => 'selesai'], 'warna' => 'bg-success-500'],
                        ['label' => 'Total', 'nilai' => $laporanStatistik['total'], 'route' => [], 'warna' => 'bg-slate-400'],
                    ];
                @endphp

                @foreach ($ringkasStatus as $ringkas)
                    <a href="{{ route('laporan.index', $ringkas['route']) }}"
                       class="bg-white px-2 py-2.5 transition hover:bg-kertas">
                        <span class="flex items-center justify-center gap-1.5 text-[11px] font-medium text-slate-600">
                            <span class="h-1.5 w-1.5 rounded-full {{ $ringkas['warna'] }}" aria-hidden="true"></span>
                            {{ $ringkas['label'] }}
                        </span>
                        <span class="mt-0.5 block font-judul text-lg font-semibold tabular-nums text-tinta">{{ number_format($ringkas['nilai'], 0, ',', '.') }}</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap gap-1.5 text-xs">
                <a href="{{ route('laporan.index', ['jenis' => 'kerusakan']) }}" class="inline-flex min-h-11 items-center rounded-pill bg-kertas-tua px-2.5 py-1 font-medium text-slate-700 transition lg:min-h-0 hover:bg-warning-100">
                    Kerusakan <span class="font-semibold tabular-nums">{{ number_format($laporanStatistik['kerusakan'], 0, ',', '.') }}</span>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'pengaduan']) }}" class="inline-flex min-h-11 items-center rounded-pill bg-kertas-tua px-2.5 py-1 font-medium text-slate-700 transition lg:min-h-0 hover:bg-info-100">
                    Pengaduan <span class="font-semibold tabular-nums">{{ number_format($laporanStatistik['pengaduan'], 0, ',', '.') }}</span>
                </a>
                <a href="{{ route('laporan.index', ['jenis' => 'bangunan_liar']) }}" class="inline-flex min-h-11 items-center rounded-pill bg-kertas-tua px-2.5 py-1 font-medium text-slate-700 transition lg:min-h-0 hover:bg-danger-100">
                    Bangunan liar <span class="font-semibold tabular-nums">{{ number_format($laporanStatistik['bangunan_liar'], 0, ',', '.') }}</span>
                </a>
            </div>

            <ul class="mt-2 divide-y divide-garis">
                @forelse ($laporanTerbaru as $laporan)
                    <li class="py-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <a href="{{ route('laporan.show', $laporan) }}" class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-slate-800 transition hover:text-brand-700">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-600">
                                    {{ $laporan->kode }} &middot; {{ $laporan->lokasi_ringkas }} &middot;
                                    {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                                </p>
                            </a>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-badge :tone="match ($laporan->status) {
                                    'selesai' => 'success',
                                    'ditolak' => 'danger',
                                    'diproses' => 'info',
                                    default => 'warning',
                                }" size="sm">
                                    {{ $laporan->label_status }}
                                </x-badge>

                                @if (auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('laporan.destroy', $laporan) }}" class="inline" data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="flex min-h-11 items-center rounded-lg border border-danger-200 px-3 text-xs font-semibold text-danger-700 transition hover:bg-danger-50 lg:min-h-0 lg:py-1.5"
                                                aria-label="Hapus laporan {{ $laporan->kode }}">
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
        </x-card>

        {{-- Input terbaru --}}
        <x-card heading="Input terbaru" meta="Lima data perumahan terakhir">
            <x-slot:actions>
                <a href="{{ route('perumahan.create') }}" class="tautan-aksi">+ Tambah</a>
            </x-slot:actions>

            <ul class="divide-y divide-garis">
                @forelse ($terbaru as $item)
                    <li class="py-3 first:pt-0 last:pb-0">
                        <a href="{{ route('perumahan.show', $item) }}" class="group block">
                            <p class="truncate text-sm font-medium text-slate-800 transition group-hover:text-brand-700">{{ $item->nama }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-600">
                                Kel. {{ $item->kelurahan->nama ?? '-' }} &middot; {{ number_format($item->jumlah_unit, 0, ',', '.') }} unit
                            </p>
                        </a>
                    </li>
                @empty
                    <li class="py-4 text-sm text-slate-600">Belum ada data perumahan.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
@endsection

@if ($perTahun->isNotEmpty() || $totalPsuTerdata > 0 || $jenisPsu->isNotEmpty())
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.js" integrity="sha384-4W8gMhOYqk0FPg9sAMkb3g/P2fXtzbUyDUUgMlA1EEig8z/U/13EYUjErc1H382b" crossorigin="anonymous"></script>
    @endpush
@endif
