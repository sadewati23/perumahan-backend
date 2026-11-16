@extends('layouts.publik')

@section('title', 'Status Laporan '.$laporan->kode)
@section('description', 'Status penanganan laporan '.$laporan->kode.' di portal data perumahan.')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    @php
        $waktuDiproses = $laporan->riwayats->firstWhere('status_baru', 'diproses')?->created_at ?? $laporan->ditangani_pada;
        $waktuSelesai = $laporan->riwayats->firstWhere('status_baru', 'selesai')?->created_at ?? $laporan->selesai_pada;
        $waktuDitolak = $laporan->riwayats->firstWhere('status_baru', 'ditolak')?->created_at;

        $status = $laporan->status;
        $ditolak = $status === 'ditolak';
        $urutan = ['baru' => 1, 'diproses' => 2, 'selesai' => 3][$status] ?? 1;

        $langkah = [
            [
                'judul' => 'Laporan diterima',
                'keterangan' => 'Laporan masuk ke sistem dan menunggu peninjauan petugas.',
                'waktu' => $laporan->created_at,
                'selesai' => $urutan >= 1,
                'aktif' => $status === 'baru',
            ],
            [
                'judul' => $ditolak ? 'Laporan ditolak' : 'Sedang ditangani',
                'keterangan' => $ditolak
                    ? 'Laporan tidak dapat ditindaklanjuti. Lihat catatan di bawah.'
                    : 'Petugas meninjau lokasi dan menyiapkan tindak lanjut.',
                'waktu' => $ditolak ? $waktuDitolak : $waktuDiproses,
                'selesai' => $ditolak || $urutan >= 2,
                'aktif' => $status === 'diproses',
                'bahaya' => $ditolak,
            ],
            [
                'judul' => 'Selesai ditangani',
                'keterangan' => 'Tindak lanjut selesai dan laporan ditutup.',
                'waktu' => $waktuSelesai,
                'selesai' => $urutan >= 3,
                'aktif' => false,
            ],
        ];

        if ($ditolak) {
            array_pop($langkah);
        }

        $formatWaktu = fn ($waktu) => $waktu ? $waktu->translatedFormat('d M Y, H:i').' WIB' : null;
    @endphp

    <section class="mx-auto max-w-3xl px-4 py-10 lg:px-8">
        <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-600" aria-label="Breadcrumb">
            <a href="{{ route('publik.beranda') }}" class="hover:text-brand-700 hover:underline">Beranda</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('cek-laporan.index') }}" class="hover:text-brand-700 hover:underline">Cek Status Laporan</a>
            <span aria-hidden="true">/</span>
            <span class="text-slate-700" aria-current="page">{{ $laporan->kode }}</span>
        </nav>

        <div class="mt-4 overflow-hidden rounded-modal border border-garis bg-white shadow-card">
            <div class="border-b border-garis px-6 py-5 sm:px-8">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="font-judul text-3xl font-semibold tracking-tight text-tinta">Status laporan</h1>
                        <p class="mt-1 text-sm text-slate-600">{{ $laporan->judul }}</p>
                    </div>
                    <x-badge tone="neutral" class="font-mono tracking-wide">{{ $laporan->kode }}</x-badge>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full {{ match ($status) { 'selesai' => 'bg-emerald-500', 'ditolak' => 'bg-rose-500', 'diproses' => 'bg-sky-500', default => 'bg-amber-500' } }}" aria-hidden="true"></span>
                        {{ $laporan->label_status }}
                    </span>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $laporan->label_jenis }}</span>
                </div>
            </div>

            <div class="grid gap-8 px-6 py-6 sm:px-8 lg:grid-cols-5">
                {{-- Stepper --}}
                <ol class="lg:col-span-3">
                    @foreach ($langkah as $i => $item)
                        <li class="relative flex gap-4 {{ $loop->last ? '' : 'pb-7' }}">
                            @if (! $loop->last)
                                <span class="absolute left-4 top-9 h-[calc(100%-1.25rem)] w-px {{ $item['selesai'] ? 'bg-brand-600' : 'bg-garis' }}" aria-hidden="true"></span>
                            @endif

                            <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-xs font-bold
                                {{ ($item['bahaya'] ?? false)
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : ($item['selesai']
                                        ? 'border-brand-600 bg-brand-600 text-white'
                                        : ($item['aktif']
                                            ? 'border-brand-600 bg-white text-brand-700 ring-4 ring-brand-100'
                                            : 'border-garis bg-white text-slate-600')) }}"
                                  aria-hidden="true">
                                @if ($item['selesai'] && ! ($item['bahaya'] ?? false))
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>

                            <div class="min-w-0 pt-0.5">
                                <p class="text-sm font-semibold {{ ($item['bahaya'] ?? false) ? 'text-rose-800' : 'text-tinta' }}">{{ $item['judul'] }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">{{ $item['keterangan'] }}</p>
                                @if ($item['waktu'])
                                    <p class="mt-1 text-xs text-slate-600">{{ $formatWaktu($item['waktu']) }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>

                {{-- Ringkasan --}}
                <dl class="space-y-4 border-t border-garis pt-5 text-sm lg:col-span-2 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                    <div>
                        <dt class="text-xs text-slate-600">Jenis laporan</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->label_jenis }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Lokasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->lokasi_ringkas }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Dikirim</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $formatWaktu($laporan->created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Terakhir diperbarui</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $formatWaktu($laporan->updated_at) }}</dd>
                    </div>
                </dl>
            </div>

            @if ($ditolak)
                <div class="border-t border-garis bg-rose-50 px-6 py-5 sm:px-8">
                    <p class="text-sm font-semibold text-rose-800">Alasan penolakan</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-rose-700">
                        {{ $laporan->deskripsi_aksi_selesai ?: 'Laporan tidak dapat ditindaklanjuti. Hubungi petugas untuk keterangan lebih lanjut.' }}
                    </p>
                </div>
            @elseif ($status === 'selesai' && filled($laporan->deskripsi_aksi_selesai))
                <div class="border-t border-garis bg-kertas px-6 py-5 sm:px-8">
                    <p class="text-sm font-semibold text-tinta">Tindak lanjut</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $laporan->deskripsi_aksi_selesai }}</p>
                </div>
            @endif

            @if ($laporan->jatuh_tempo_hapus)
                <div class="border-t border-garis bg-warning-50 px-6 py-4 text-sm text-warning-800 sm:px-8">
                    Laporan ini akan dihapus otomatis pada {{ $laporan->jatuh_tempo_hapus->translatedFormat('d F Y, H:i') }} WIB.
                </div>
            @endif
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <a href="{{ route('cek-laporan.index') }}" class="tombol-utama">Cek laporan lain</a>
            <a href="{{ route('lapor.create') }}" class="tombol-sekunder">Buat laporan baru</a>
        </div>

        <p class="mt-4 text-xs text-slate-600">
            Simpan kode laporan ini. Status diperbarui setiap kali petugas menindaklanjuti laporan Anda.
        </p>
    </section>
@endsection
