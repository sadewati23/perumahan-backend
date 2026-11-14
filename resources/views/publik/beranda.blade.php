@extends('layouts.publik')

@section('title', 'Beranda')
@section('description', 'Portal informasi data perumahan: lokasi, luasan lahan, jumlah unit, RTH, KDB/KDH, PSU, dan status subsidi, komersil, atau campuran.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-neutral-950 via-neutral-900 to-neutral-800 text-white">
        <div class="pointer-events-none absolute right-0 top-1/2 -z-10 hidden -translate-y-1/2 xl:block" aria-hidden="true">
            <div class="absolute left-1/2 top-1/2 h-[32rem] w-[32rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-400/15 blur-3xl"></div>
            <img src="{{ asset('images/logo-latar.png') }}" alt="" width="512" height="512" decoding="async"
             class="hero-mengambang relative mr-8 h-auto w-[28rem] select-none opacity-25 drop-shadow-[0_0_48px_rgba(251,191,36,0.4)] [mask-image:linear-gradient(to_left,black_45%,transparent)] [-webkit-mask-image:linear-gradient(to_left,black_45%,transparent)] 2xl:mr-24 2xl:w-[34rem]">
        </div>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-14 lg:px-8 lg:py-20">
            <x-logo tile ukuran="h-14 w-14" class="mb-4 xl:hidden" />
            <span class="inline-flex masuk-naik items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-amber-200 ring-1 ring-inset ring-white/20">
                Portal Data Terbuka &middot; {{ config('peta.nama_wilayah') }}
            </span>
            <h1 class="mt-4 max-w-3xl masuk-naik tunda-1 text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                Informasi Data Perumahan <span class="teks-kilau">{{ config('peta.nama_wilayah') }}</span>
            </h1>
            <p class="mt-4 max-w-2xl masuk-naik tunda-2 text-sm text-neutral-300 sm:text-base">
                Telusuri data perumahan beserta lokasi kelurahan dan kecamatan, RT/RW, titik koordinat,
                luas lahan, jumlah unit, RTH, KDB/KDH, lahan pemakaman, prasarana-sarana-utilitas (PSU),
                tahun pembangunan, tipe unit, dan status subsidi/komersil/campuran.
            </p>

            <div class="mt-8 flex max-w-2xl masuk-naik tunda-3 flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('lapor.create') }}"
                   class="rounded-lg bg-rose-500 px-6 py-3 text-center text-sm font-semibold text-white transition hover:bg-rose-400">
                    Lapor Kerusakan / Pengaduan
                </a>
                <a href="{{ route('publik.peta') }}"
                   class="rounded-lg border border-white/20 px-6 py-3 text-center text-sm font-semibold text-white transition hover:bg-white/10">
                    Lihat Peta
                </a>
                <form method="GET" action="{{ route('publik.perumahan.index') }}" class="flex flex-1 gap-2">
                    <input type="text" name="q" aria-label="Cari perumahan" enterkeyhint="search" placeholder="Cari nama perumahan…"
                           class="w-full rounded-lg border border-white/20 bg-white/10 px-4 py-3 text-sm text-white placeholder-neutral-300 outline-none backdrop-blur focus:border-amber-300 focus:ring-2 focus:ring-amber-300/40">
                    <button type="submit" class="rounded-lg bg-amber-400 px-4 py-3 text-sm font-semibold text-neutral-950 transition hover:bg-amber-300">
                        Cari
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="relative isolate overflow-hidden border-t-4 border-amber-400 bg-gradient-to-b from-amber-100 via-amber-50 to-orange-100">
        <div class="pointer-events-none absolute -left-24 top-24 -z-10 h-80 w-80 rounded-full bg-amber-300/40 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-24 bottom-24 -z-10 h-80 w-80 rounded-full bg-orange-300/30 blur-3xl" aria-hidden="true"></div>
        <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        {{-- Statistik --}}
        @php
            $kartu = [
                ['label' => 'Perumahan Terdata', 'value' => number_format($statistik['total_perumahan'], 0, ',', '.'), 'hitung' => $statistik['total_perumahan'], 'desimal' => 0, 'satuan' => 'lokasi', 'ikon' => 'rumah', 'kelas' => 'bg-gradient-to-br from-amber-400 to-amber-500 shadow-lg shadow-amber-500/20', 'kelas_angka' => 'text-neutral-950', 'kelas_teks' => 'text-neutral-900', 'kelas_ikon' => 'bg-neutral-950/10 text-neutral-950'],
                ['label' => 'Total Unit Rumah', 'value' => number_format($statistik['total_unit'], 0, ',', '.'), 'hitung' => $statistik['total_unit'], 'desimal' => 0, 'satuan' => 'unit', 'ikon' => 'unit', 'kelas' => 'bg-neutral-900 shadow-lg shadow-neutral-900/20', 'kelas_angka' => 'text-amber-300', 'kelas_teks' => 'text-neutral-300', 'kelas_ikon' => 'bg-amber-400/15 text-amber-300'],
                ['label' => 'Luas Lahan', 'value' => number_format($statistik['total_luas_lahan'] / 10000, 2, ',', '.'), 'hitung' => $statistik['total_luas_lahan'] / 10000, 'desimal' => 2, 'satuan' => 'hektar', 'ikon' => 'lahan', 'kelas' => 'bg-gradient-to-br from-amber-700 to-amber-800 shadow-lg shadow-amber-800/20', 'kelas_angka' => 'text-white', 'kelas_teks' => 'text-white', 'kelas_ikon' => 'bg-white/15 text-white'],
                ['label' => 'Luas RTH', 'value' => number_format($statistik['total_luas_rth'] / 10000, 2, ',', '.'), 'hitung' => $statistik['total_luas_rth'] / 10000, 'desimal' => 2, 'satuan' => 'hektar', 'ikon' => 'daun', 'kelas' => 'bg-gradient-to-br from-neutral-700 to-neutral-900 shadow-lg shadow-neutral-900/20', 'kelas_angka' => 'text-amber-300', 'kelas_teks' => 'text-neutral-300', 'kelas_ikon' => 'bg-amber-400/15 text-amber-300'],
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($kartu as $item)
                <div class="kartu-muncul {{ $item['kelas'] }} rounded-2xl p-5 transition motion-safe:hover:-translate-y-1 hover:shadow-xl">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $item['kelas_ikon'] }}">
                        @if ($item['ikon'] === 'rumah')
                            <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
                                <path d="m3 10 9-7 9 7" />
                                <path d="M5 9v12h14V9M9 21v-6h6v6" />
                            </svg>
                        @elseif ($item['ikon'] === 'unit')
                            <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
                                <path d="M3 21V9l6-3v15M9 21V3l6 3v15M15 21V11l6-3v13" />
                                <path d="M6 12h.01M6 16h.01M12 9h.01M12 13h.01M18 14h.01M18 18h.01" />
                            </svg>
                        @elseif ($item['ikon'] === 'lahan')
                            <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
                                <path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z" />
                                <path d="M9 3v15M15 6v15" />
                            </svg>
                        @else
                            <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24">
                                <path d="M20 4C12 4 6 8 6 15c0 3 2 5 5 5 7 0 9-8 9-16Z" />
                                <path d="M4 20c3-5 7-8 13-11" />
                            </svg>
                        @endif
                    </div>
                    <p class="mt-4 text-sm font-medium {{ $item['kelas_teks'] }}">{{ $item['label'] }}</p>
                    <p class="mt-1 text-3xl font-bold tracking-tight {{ $item['kelas_angka'] }} tabular-nums" data-hitung="{{ $item['hitung'] }}" data-desimal="{{ $item['desimal'] }}" aria-label="{{ $item['value'] }}">{{ $item['value'] }}</p>
                    <p class="text-sm {{ $item['kelas_teks'] }}">{{ $item['satuan'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Komposisi status --}}
        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="kartu-muncul overflow-hidden rounded-2xl bg-amber-50 shadow-md ring-1 ring-amber-300/70">
                <div class="flex items-center justify-between bg-neutral-900 px-6 py-3.5"><p class="flex items-center gap-2 text-sm font-semibold text-white"><span class="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true"></span>Komposisi Status</p></div>
                <div class="p-6">
                @php
                    $totalStatus = max(1, $statistik['total_perumahan']);
                    $persenSubsidi = round($statistik['jumlah_subsidi'] / $totalStatus * 100);
                    $persenKomersil = round($statistik['jumlah_komersil'] / $totalStatus * 100);
                    $persenCampuran = round($statistik['jumlah_campuran'] / $totalStatus * 100);
                @endphp
                <div class="mt-4 space-y-4">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 font-medium text-emerald-700"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi</span>
                            <span class="text-neutral-700">{{ $statistik['jumlah_subsidi'] }} <span class="font-semibold text-neutral-900">({{ $persenSubsidi }}%)</span></span>
                        </div>
                        <div class="mt-1 h-2.5 rounded-full bg-stone-200">
                            <div class="batang h-2.5 rounded-full bg-emerald-500" style="width: {{ $persenSubsidi }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 font-medium text-sky-700"><span class="h-2.5 w-2.5 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil</span>
                            <span class="text-neutral-700">{{ $statistik['jumlah_komersil'] }} <span class="font-semibold text-neutral-900">({{ $persenKomersil }}%)</span></span>
                        </div>
                        <div class="mt-1 h-2.5 rounded-full bg-stone-200">
                            <div class="batang h-2.5 rounded-full bg-sky-500" style="width: {{ $persenKomersil }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 font-medium text-violet-700"><span class="h-2.5 w-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran</span>
                            <span class="text-neutral-700">{{ $statistik['jumlah_campuran'] }} <span class="font-semibold text-neutral-900">({{ $persenCampuran }}%)</span></span>
                        </div>
                        <div class="mt-1 h-2.5 rounded-full bg-stone-200">
                            <div class="batang h-2.5 rounded-full bg-violet-500" style="width: {{ $persenCampuran }}%"></div>
                        </div>
                    </div>
                </div>
                <p class="mt-4 border-t border-amber-200 pt-3 text-sm text-neutral-700">
                    Tersebar di {{ $statistik['jumlah_kecamatan'] }} kecamatan.
                </p>
                </div>
            </div>

            {{-- Sebaran per kecamatan --}}
            <div class="kartu-muncul overflow-hidden rounded-2xl bg-amber-50 shadow-md ring-1 ring-amber-300/70 lg:col-span-2">
                <div class="flex items-center justify-between bg-neutral-900 px-6 py-3.5">
                    <p class="flex items-center gap-2 text-sm font-semibold text-white"><span class="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true"></span>Sebaran per Kecamatan</p>
                    <a href="{{ route('publik.perumahan.index') }}" class="text-sm font-medium text-amber-300 underline-offset-4 hover:text-amber-200 hover:underline focus-visible:outline-2 focus-visible:outline-amber-400">Lihat semua <span aria-hidden="true">→</span></a>
                </div>
                <div class="p-6">

                @php $maxKecamatan = max(1, (int) $perKecamatan->max('perumahans_count')); @endphp

                <div class="mt-4 space-y-3">
                    @forelse ($perKecamatan as $kecamatan)
                        <a href="{{ route('publik.perumahan.index', ['kecamatan_id' => $kecamatan->id]) }}" class="group -mx-2 block rounded-lg px-2 py-1.5 transition hover:bg-amber-50 focus-visible:outline-2 focus-visible:outline-amber-500">
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex min-w-0 items-center gap-2"><span class="w-5 shrink-0 text-xs font-semibold text-neutral-600">{{ $loop->iteration }}</span><span class="font-medium text-neutral-900">{{ $kecamatan->nama }}</span></span>
                                <span class="text-neutral-700">{{ $kecamatan->perumahans_count }} perumahan</span>
                            </div>
                            <div class="mt-1 h-2.5 rounded-full bg-stone-200">
                                <div class="batang h-2.5 rounded-full bg-gradient-to-r from-amber-400 to-amber-600"
                                     style="width: {{ round($kecamatan->perumahans_count / $maxKecamatan * 100) }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-neutral-700">Belum ada data kecamatan.</p>
                    @endforelse
                </div>
                </div>
            </div>
        </div>

        {{-- Peta ringkas --}}
        <section class="reveal-scroll mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="border-l-4 border-amber-400 pl-3 text-lg font-semibold text-neutral-900">Peta Sebaran Perumahan</h2>
                    <p class="text-sm text-neutral-700">Klik marker untuk melihat ringkasan perumahan.</p>
                </div>
                <a href="{{ route('publik.peta') }}"
                   class="rounded-lg bg-neutral-900 px-4 py-2 text-sm font-semibold text-amber-300 shadow transition hover:bg-neutral-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500">
                     Buka peta lengkap <span aria-hidden="true">→</span>
                </a>
            </div>

            <div id="peta-beranda" class="reveal-scroll mt-4 h-80 w-full overflow-hidden rounded-2xl border-2 border-amber-300 bg-neutral-100 shadow-lg"></div>

            <div class="mt-2 flex flex-wrap items-center gap-2" aria-label="Legenda status peta">
                <span class="kartu-muncul inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm text-neutral-800 ring-1 ring-emerald-200"><span class="text-emerald-500" aria-hidden="true">●</span>Subsidi</span>
                <span class="kartu-muncul inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm text-neutral-800 ring-1 ring-sky-200"><span class="text-sky-500" aria-hidden="true">●</span>Komersil</span>
                <span class="kartu-muncul inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-sm text-neutral-800 ring-1 ring-violet-200"><span class="text-violet-500" aria-hidden="true">●</span>Campuran</span>
            </div>

            @if ($titik->isEmpty())
                <p class="mt-3 text-sm text-neutral-700">Belum ada perumahan dengan titik koordinat.</p>
            @endif
        </section>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 lg:px-8">
        {{-- Perumahan terbaru --}}
        <section class="mt-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Perumahan Terbaru</h2>
                    <p class="text-sm text-slate-600">Berdasarkan tahun selesai pembangunan.</p>
                </div>
                <a href="{{ route('publik.perumahan.index') }}"
                   class="text-sm font-semibold text-brand-600 hover:text-brand-700">Semua perumahan &rarr;</a>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($terbaru as $perumahan)
                    @include('publik._card', ['perumahan' => $perumahan])
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 md:col-span-2 lg:col-span-3">
                        Belum ada data perumahan yang dipublikasikan.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('peta-beranda');
            if (!el || typeof window.L === 'undefined') return;

            const PETA = @json(config('peta'));
            const titik = @json($titik);
            if (!titik.length) return;

            const map = L.map(el).setView([PETA.latitude, PETA.longitude], PETA.zoom);
            L.tileLayer(PETA.tile_url, {
                maxZoom: 19,
                attribution: PETA.tile_attribution,
            }).addTo(map);

             const colors = @json(\App\Models\Perumahan::warnaPerLabel());
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
                    html: `<div style="width:14px;height:14px;border-radius:9999px;background:${warna};border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
                    iconSize: [14, 14],
                    iconAnchor: [7, 7],
                });

                L.marker([p.lat, p.lng], { icon }).addTo(map).bindPopup(`
                    <div style="min-width:200px">
                        <p style="font-weight:600;margin:0 0 2px">${escapeHtml(p.nama)}</p>
                        <p style="margin:0 0 6px;color:${warna};font-size:12px">${escapeHtml(p.status)}</p>
                        <p style="margin:0 0 6px;color:#475569;font-size:12px">${escapeHtml(p.alamat)}</p>
                        <p style="margin:0 0 6px;font-size:12px;color:#334155">
                            ${formatAngka(p.unit)} unit &middot; ${formatAngka(p.luas_lahan)} m&sup2;
                        </p>
                        <a href="${encodeURI(p.url)}" style="color:#4f46e5;font-size:12px;font-weight:500">Lihat detail &rarr;</a>
                    </div>`);

                bounds.push([p.lat, p.lng]);
            });

            map.fitBounds(bounds, { padding: [40, 40], maxZoom: PETA.zoom + 1 });
            setTimeout(() => map.invalidateSize(), 200);
        });
    </script>
@endpush
