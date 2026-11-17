@extends('layouts.admin')

@section('title', 'Laporan Warga')
@section('subtitle', 'Kerusakan sarana-prasarana, pengaduan, dan bangunan liar dari warga')

@section('content')
    @php
        $chipStatus = [
            '' => ['label' => 'Semua', 'titik' => 'bg-tinta'],
            'baru' => ['label' => 'Baru', 'titik' => 'bg-amber-500'],
            'diproses' => ['label' => 'Diproses', 'titik' => 'bg-sky-500'],
            'selesai' => ['label' => 'Selesai', 'titik' => 'bg-emerald-500'],
            'ditolak' => ['label' => 'Ditolak', 'titik' => 'bg-rose-500'],
        ];

        $activeFilters = collect($filters)->filter(fn ($v) => filled($v));
        $hasFilter = $activeFilters->isNotEmpty();
        $resultCount = $laporans->total();

        $filterLabels = [
            'q' => 'Pencarian',
            'jenis' => 'Jenis',
            'status' => 'Status',
            'tingkat' => 'Tingkat',
            'perumahan_id' => 'Perumahan',
        ];
    @endphp

    {{-- Ringkasan per status --}}
    <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
        @foreach ($chipStatus as $status => $chip)
            @php $aktif = ($filters['status'] ?? '') === $status; @endphp
            <a href="{{ route('laporan.index', array_filter(['q' => $filters['q'] ?? null, 'jenis' => $filters['jenis'] ?? null, 'tingkat' => $filters['tingkat'] ?? null, 'perumahan_id' => $filters['perumahan_id'] ?? null, 'status' => $status])) }}"
               @if ($aktif) aria-current="true" @endif
               class="rounded-card border bg-white p-4 shadow-card transition hover:border-brand-300
                      {{ $aktif ? 'border-brand-400 ring-2 ring-brand-500/20' : 'border-garis' }}">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-600">
                    <span class="h-1.5 w-1.5 rounded-full {{ $chip['titik'] }}" aria-hidden="true"></span>
                    {{ $chip['label'] }}
                </p>
                <p class="mt-1 font-judul text-2xl font-semibold tabular-nums tracking-tight text-tinta">
                    {{ number_format($status === '' ? $ringkasan['total'] : $ringkasan[$status], 0, ',', '.') }}
                </p>
            </a>
        @endforeach
    </div>

    {{-- Filter --}}
    <details class="mt-4 rounded-card border border-garis bg-white shadow-card" open>
        <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-2 px-4 py-3 text-sm font-semibold text-tinta sm:px-5 md:cursor-default">
            <span class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-kertas-tua text-slate-600 ring-1 ring-garis" aria-hidden="true">
                    <x-icon name="filter" class="h-4 w-4"/>
                </span>
                Filter laporan
            </span>
            <span class="text-xs font-normal text-slate-600 md:hidden">Buka / tutup</span>
        </summary>

        <form method="GET" action="{{ route('laporan.index') }}" class="flex flex-wrap items-end gap-3 border-t border-garis px-4 py-4 sm:px-5">
            @if ($filters['status'] ?? '')
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="min-w-48 flex-1">
                <label for="q" class="label mb-1.5">Cari</label>
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Kode tiket, judul, pelapor, atau perumahan…"
                       class="input">
            </div>

            <div class="min-w-40">
                <label for="jenis" class="label mb-1.5">Jenis</label>
                <select id="jenis" name="jenis" class="input">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::JENIS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['jenis'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label for="perumahan_id" class="label mb-1.5">Perumahan</label>
                <select id="perumahan_id" name="perumahan_id" class="input">
                    <option value="">Semua</option>
                    @foreach ($perumahans as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['perumahan_id'] ?? '') === (string) $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-36">
                <label for="tingkat" class="label mb-1.5">Tingkat</label>
                <select id="tingkat" name="tingkat" class="input">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::TINGKAT as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['tingkat'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button type="submit">
                    <x-icon name="cari" class="h-4 w-4"/>
                    Terapkan
                </x-button>
                <x-button href="{{ route('laporan.index') }}" variant="secondary">Reset</x-button>
            </div>
        </form>
    </details>

    @if ($hasFilter)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            @foreach ($activeFilters as $key => $value)
                @php
                    if ($key === 'perumahan_id') {
                        $labelNilai = optional($perumahans->firstWhere('id', $value))->nama ?? $value;
                    } elseif ($key === 'jenis') {
                        $labelNilai = \App\Models\Laporan::JENIS[$value] ?? $value;
                    } elseif ($key === 'status') {
                        $labelNilai = \App\Models\Laporan::STATUS[$value] ?? $value;
                    } elseif ($key === 'tingkat') {
                        $labelNilai = \App\Models\Laporan::TINGKAT[$value] ?? $value;
                    } else {
                        $labelNilai = $value;
                    }
                @endphp
                <a href="{{ route('laporan.index', $activeFilters->except($key)->all()) }}"
                   class="inline-flex min-h-11 items-center gap-1 rounded-pill bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100 lg:min-h-0">
                    {{ $filterLabels[$key] ?? $key }}: {{ $labelNilai }}
                    <span aria-hidden="true">&times;</span>
                </a>
            @endforeach
            <a href="{{ route('laporan.index') }}" class="text-xs font-medium text-slate-600 hover:text-slate-800">Hapus semua filter</a>
        </div>
    @endif

    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
        <p>
            Menampilkan <span class="font-semibold text-tinta">{{ $laporans->firstItem() ?? 0 }}</span>–<span class="font-semibold text-tinta">{{ $laporans->lastItem() ?? 0 }}</span>
            dari <span class="font-semibold text-tinta">{{ $resultCount }}</span> data
        </p>

        {{-- Cetak rekap membawa seluruh filter aktif agar hasil cetak sama dengan daftar di layar. --}}
        <x-button href="{{ route('laporan.cetak-rekap', request()->query()) }}" variant="secondary" size="sm">
            <x-icon name="cetak" class="h-4 w-4"/>
            Cetak Rekap
        </x-button>
    </div>

    {{-- Daftar kartu untuk layar kecil --}}
    <div class="mt-3 space-y-3 md:hidden">
        @forelse ($laporans as $laporan)
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('laporan.show', $laporan) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $laporan->kode }}</a>
                        <p class="mt-1 font-medium text-slate-800">{{ $laporan->judul }}</p>
                        <p class="mt-0.5 text-xs text-slate-600">{{ $laporan->lokasi_ringkas }}</p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <x-badge :tone="match ($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }" size="sm">
                            {{ $laporan->label_status }}
                        </x-badge>
                        @if ($laporan->status === 'selesai' && $laporan->jatuh_tempo_hapus)
                            @php $sisa = now()->diffInDays($laporan->jatuh_tempo_hapus, false); @endphp
                            <x-badge tone="warning" size="sm">{{ $sisa < 0 ? 'Menunggu penghapusan' : ($sisa === 0 ? 'Dihapus hari ini' : 'Dihapus '.$sisa.' hari lagi') }}</x-badge>
                        @endif
                    </div>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-600">Jenis</dt>
                        <dd class="mt-0.5">
                            <x-badge :tone="match ($laporan->jenis) { 'pengaduan' => 'info', 'bangunan_liar' => 'danger', default => 'warning' }" size="sm">
                                {{ $laporan->label_jenis }}
                            </x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Tingkat</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $laporan->label_tingkat ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Pelapor</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $laporan->nama_pelapor }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-600">Tanggal</dt>
                        <dd class="mt-0.5 text-xs text-slate-600">{{ $laporan->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                </dl>

                @if ($laporan->deskripsi)
                    <p class="mt-3 line-clamp-3 text-xs text-slate-600">{{ $laporan->deskripsi }}</p>
                @endif

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-garis pt-2">
                    <div class="flex flex-wrap items-center gap-1">
                        <a href="{{ route('laporan.show', $laporan) }}" class="inline-flex min-h-11 items-center rounded-md px-2 text-xs font-medium text-slate-600 hover:bg-kertas hover:text-brand-700">Detail</a>

                        @if ($laporan->fotosLaporan->isNotEmpty())
                            <a href="{{ route('laporan.show', $laporan) }}" class="inline-flex min-h-11 items-center rounded-md px-2 text-xs font-medium text-brand-700 hover:bg-brand-50">
                                {{ $laporan->fotosLaporan->count() }} foto bukti
                            </a>
                        @endif
                    </div>

                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('laporan.destroy', $laporan) }}" class="inline" data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex min-h-11 items-center rounded-md px-2 text-xs font-medium text-danger-700 hover:bg-danger-50">
                                Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </x-card>
        @empty
            <x-empty-state
                title="{{ $hasFilter ? 'Tidak ada laporan yang cocok' : 'Belum ada laporan' }}"
                description="{{ $hasFilter ? 'Ubah atau hapus filter untuk melihat laporan lain.' : 'Laporan warga yang masuk akan tampil di sini.' }}"
                iconPath="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        @endforelse
    </div>

    {{-- Tabel untuk layar sedang ke atas --}}
    <div class="mt-4 hidden overflow-hidden rounded-card border border-garis bg-white shadow-card md:block">
        <div class="no-scrollbar overflow-x-auto">
            <table class="min-w-full divide-y divide-garis text-sm">
                <thead class="sticky top-0 z-10 bg-kertas-tua text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">Kode</th>
                        <th class="whitespace-nowrap px-4 py-3">Laporan</th>
                        <th class="whitespace-nowrap px-4 py-3">Jenis</th>
                        <th class="whitespace-nowrap px-4 py-3">Pelapor</th>
                        <th class="whitespace-nowrap px-4 py-3">Tingkat</th>
                        <th class="whitespace-nowrap px-4 py-3">Ukuran</th>
                        <th class="whitespace-nowrap px-4 py-3">Detail</th>
                        <th class="whitespace-nowrap px-4 py-3">Status</th>
                        <th class="whitespace-nowrap px-4 py-3">Tanggal</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis">
                    @forelse ($laporans as $laporan)
                        <tr class="transition hover:bg-kertas">
                            <td class="whitespace-nowrap px-4 py-3">
                                <a href="{{ route('laporan.show', $laporan) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $laporan->kode }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('laporan.show', $laporan) }}" class="font-medium text-slate-800 hover:text-brand-700">{{ $laporan->judul }}</a>
                                <p class="mt-0.5 text-xs text-slate-600">{{ $laporan->lokasi_ringkas }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-badge :tone="match ($laporan->jenis) { 'pengaduan' => 'info', 'bangunan_liar' => 'danger', default => 'warning' }" size="sm">
                                    {{ $laporan->label_jenis }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $laporan->nama_pelapor }}</p>
                                <p class="mt-0.5 text-xs text-slate-600">{{ $laporan->kontak_pelapor ?? '-' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($laporan->label_tingkat)
                                    <x-badge :tone="match ($laporan->tingkat) { 'rusak_berat' => 'danger', 'rusak_ringan' => 'warning', default => 'neutral' }" size="sm">{{ $laporan->label_tingkat }}</x-badge>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ $laporan->ukuran_ringkas }}</td>
                            <td class="max-w-xs px-4 py-3 text-xs text-slate-600">
                                <p class="line-clamp-2">{{ $laporan->deskripsi }}</p>

                                @if ($laporan->tanggal_kejadian)
                                    <p class="mt-1 text-slate-600">Kejadian: {{ $laporan->label_tanggal_kejadian }}</p>
                                @endif

                                @if ($laporan->fotosLaporan->isNotEmpty())
                                    <a href="{{ route('laporan.show', $laporan) }}"
                                       class="mt-1 inline-flex items-center gap-1 font-medium text-brand-700 hover:underline">
                                        {{ $laporan->fotosLaporan->count() }} foto bukti
                                    </a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-badge :tone="match ($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }" size="sm">
                                    {{ $laporan->label_status }}
                                </x-badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">
                                {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if (auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('laporan.destroy', $laporan) }}" class="inline" data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-control border border-danger-200 px-2.5 py-1.5 text-xs font-semibold text-danger-700 transition hover:bg-danger-50">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-4">
                                <x-empty-state
                                    title="{{ $hasFilter ? 'Tidak ada laporan yang cocok' : 'Belum ada laporan' }}"
                                    description="{{ $hasFilter ? 'Ubah atau hapus filter untuk melihat laporan lain.' : 'Laporan warga yang masuk akan tampil di sini.' }}"
                                    iconPath="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $laporans->links('vendor.pagination.admin') }}
    </div>
@endsection
