@extends('layouts.publik')

@section('title', $perumahan->nama)
@section('description', 'Detail data perumahan '.$perumahan->nama.' di Kel. '.($perumahan->kelurahan->nama ?? '-').', Kec. '.($perumahan->kelurahan->kecamatan->nama ?? '-').'.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    @php
        $tema = $perumahan->status_theme;

        $kelasKondisiPsu = fn (?string $kondisi) => match ($kondisi) {
            'rusak_berat' => 'bg-danger-100 text-danger-800 ring-danger-300',
            'rusak_ringan' => 'bg-danger-50 text-danger-700 ring-danger-200',
            'baik' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };

        $psuBermasalah = $perumahan->psu
            ->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])
            ->count();
    @endphp

    @include('publik.partials.detail-ringkasan', ['perumahan' => $perumahan, 'tema' => $tema])

    <div class="mx-auto max-w-7xl px-4 pb-10 lg:px-8">
        {{-- Navigasi bagian: di desktop menempel di bawah header; di HP tetap satu baris gulir. --}}
        <nav data-nav-bagian aria-label="Navigasi bagian halaman"
             class="no-scrollbar -mx-4 mb-6 mt-4 flex gap-1 overflow-x-auto border-y border-slate-200 bg-white/95 px-4 py-2 backdrop-blur md:sticky md:top-[65px] md:z-30 md:mx-0 md:mt-6 md:rounded-xl md:border md:px-2 md:shadow-sm">
            @foreach ([
                ['lokasi', 'Lokasi'],
                ['tahun-pemakaman', 'Tahun & Pemakaman'],
                ['unit', 'Unit'],
                ['psu', 'PSU'],
                ['peta', 'Peta'],
            ] as [$bagianId, $bagianLabel])
                <a href="#{{ $bagianId }}"
                   class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900">
                    {{ $bagianLabel }}
                </a>
            @endforeach
        </nav>

        <div class="reveal-scroll grid gap-6 lg:grid-cols-3">
            <div class="kartu-muncul space-y-6 lg:col-span-2">
                @include('publik.partials.detail-lokasi', ['perumahan' => $perumahan])
                @include('publik.partials.detail-tahun-pemakaman', ['perumahan' => $perumahan])
                @include('publik.partials.detail-tipe-unit', ['perumahan' => $perumahan])
                @include('publik.partials.detail-psu', [
                    'perumahan' => $perumahan,
                    'kelasKondisiPsu' => $kelasKondisiPsu,
                    'psuBermasalah' => $psuBermasalah,
                ])
                @include('publik.partials.detail-peta', ['perumahan' => $perumahan])

                @if ($perumahan->keterangan)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 class="text-base font-semibold text-slate-900">Keterangan</h2>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $perumahan->keterangan }}</p>
                    </div>
                @endif
            </div>

            @include('publik.partials.detail-aside', ['perumahan' => $perumahan, 'lainnya' => $lainnya])
        </div>
    </div>
@endsection

@if ($perumahan->punya_koordinat)
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    @endpush
@endif
