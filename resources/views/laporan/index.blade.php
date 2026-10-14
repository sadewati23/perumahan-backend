@extends('layouts.app')

@section('title', 'Laporan Warga')
@section('subtitle', 'Kerusakan sarana-prasarana, pengaduan, dan bangunan liar dari warga')

@section('content')
    @php
        $chipStatus = [
            '' => ['label' => 'Semua', 'warna' => 'bg-slate-900 text-white'],
            'baru' => ['label' => 'Baru', 'warna' => 'bg-amber-100 text-amber-800 ring-amber-600/20'],
            'diproses' => ['label' => 'Diproses', 'warna' => 'bg-sky-100 text-sky-800 ring-sky-600/20'],
            'selesai' => ['label' => 'Selesai', 'warna' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20'],
            'ditolak' => ['label' => 'Ditolak', 'warna' => 'bg-rose-100 text-rose-800 ring-rose-600/20'],
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

    <div class="flex flex-wrap gap-3">
        @foreach ($chipStatus as $status => $chip)
            <a href="{{ route('laporan.index', array_filter(['q' => $filters['q'] ?? null, 'jenis' => $filters['jenis'] ?? null, 'tingkat' => $filters['tingkat'] ?? null, 'perumahan_id' => $filters['perumahan_id'] ?? null, 'status' => $status])) }}"
               class="flex-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300
                      {{ ($filters['status'] ?? '') === $status ? 'ring-2 ring-indigo-500/40' : '' }}">
                <p class="text-xs font-medium text-slate-500">{{ $chip['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ $status === '' ? $ringkasan['total'] : $ringkasan[$status] }}
                </p>
            </a>
        @endforeach
    </div>

    <details class="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" open>
        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-700 md:cursor-default md:after:hidden">
            Filter laporan
            <span class="text-xs font-normal text-slate-500 md:hidden">ketuk untuk buka/tutup</span>
        </summary>

        <form method="GET" action="{{ route('laporan.index') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @if ($filters['status'] ?? '')
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="min-w-48 flex-1">
                <label for="q" class="mb-1.5 block text-sm font-medium text-slate-700">Cari</label>
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Kode tiket, judul, pelapor, atau perumahan…"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="min-w-40">
                <label for="jenis" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</label>
                <select id="jenis" name="jenis"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::JENIS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['jenis'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label for="perumahan_id" class="mb-1.5 block text-sm font-medium text-slate-700">Perumahan</label>
                <select id="perumahan_id" name="perumahan_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach ($perumahans as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['perumahan_id'] ?? '') === (string) $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-36">
                <label for="tingkat" class="mb-1.5 block text-sm font-medium text-slate-700">Tingkat</label>
                <select id="tingkat" name="tingkat"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::TINGKAT as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['tingkat'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Terapkan
                </button>
                <a href="{{ route('laporan.index') }}"
                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                    Reset
                </a>
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
                   class="inline-flex items-center gap-1 rounded-pill bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">
                    {{ $filterLabels[$key] ?? $key }}: {{ $labelNilai }}
                    <span aria-hidden="true">&times;</span>
                </a>
            @endforeach
            <a href="{{ route('laporan.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">Hapus semua filter</a>
        </div>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
        <p>
            Menampilkan <span class="font-semibold text-slate-900">{{ $laporans->firstItem() ?? 0 }}</span>–<span class="font-semibold text-slate-900">{{ $laporans->lastItem() ?? 0 }}</span>
            dari <span class="font-semibold text-slate-900">{{ $resultCount }}</span> data
        </p>
    </div>

    {{-- Cetak rekap membawa seluruh filter aktif agar hasil cetak sama dengan daftar di layar. --}}
    <div class="mt-2 flex justify-end">
        <x-button href="{{ route('laporan.cetak-rekap', request()->query()) }}" variant="secondary" size="sm">🖨 Cetak Rekap</x-button>
    </div>

    {{-- Daftar kartu untuk layar kecil --}}
    <div class="mt-4 space-y-3 md:hidden">
        @forelse ($laporans as $laporan)
            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <a href="{{ route('laporan.show', $laporan) }}" class="font-mono text-xs font-semibold text-slate-700 hover:text-brand-700">{{ $laporan->kode }}</a>
                        <p class="mt-1 font-medium text-slate-800">{{ $laporan->judul }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $laporan->lokasi_ringkas }}</p>
                    </div>
                    <x-badge :tone="match ($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }" size="sm">
                        {{ $laporan->label_status }}
                    </x-badge>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Jenis</dt>
                        <dd class="mt-0.5">
                            <x-badge :tone="match ($laporan->jenis) { 'pengaduan' => 'info', 'bangunan_liar' => 'danger', default => 'warning' }" size="sm">
                                {{ $laporan->label_jenis }}
                            </x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Tingkat</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $laporan->label_tingkat ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Pelapor</dt>
                        <dd class="mt-0.5 text-slate-700">{{ $laporan->nama_pelapor }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Tanggal</dt>
                        <dd class="mt-0.5 text-xs text-slate-500">{{ $laporan->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    </div>
                </dl>

                @if ($laporan->deskripsi)
                    <p class="mt-3 text-xs text-slate-600">{{ $laporan->deskripsi }}</p>
                @endif

                @if ($laporan->fotosLaporan->isNotEmpty())
                    <a href="{{ route('laporan.show', $laporan) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:underline">
                        {{ $laporan->fotosLaporan->count() }} foto bukti
                    </a>
                @endif

                <div class="mt-3 border-t border-slate-100 pt-3 text-right">
                    <button type="button"
                            data-confirm="Hapus laporan {{ $laporan->kode }} ini?&#10;Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan."
                            data-action="{{ route('laporan.destroy', $laporan) }}"
                            class="text-xs font-medium text-danger-600 hover:text-danger-700">
                        Hapus
                    </button>
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
    <div class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="no-scrollbar overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
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
                <tbody class="divide-y divide-slate-100">
                    @forelse ($laporans as $laporan)
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-700">{{ $laporan->kode }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $laporan->lokasi_ringkas }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-badge :tone="match ($laporan->jenis) { 'pengaduan' => 'info', 'bangunan_liar' => 'danger', default => 'warning' }" size="sm">
                                    {{ $laporan->label_jenis }}
                                </x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $laporan->nama_pelapor }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $laporan->kontak_pelapor ?? '-' }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($laporan->label_tingkat)
                                    <x-badge tone="warning" size="sm">{{ $laporan->label_tingkat }}</x-badge>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ $laporan->ukuran_ringkas }}</td>
                            <td class="max-w-xs px-4 py-3 text-xs text-slate-600">
                                <p>{{ $laporan->deskripsi }}</p>

                                @if ($laporan->tanggal_kejadian)
                                    <p class="mt-1 text-slate-500">Kejadian: {{ $laporan->label_tanggal_kejadian }}</p>
                                @endif

                                @if ($laporan->fotosLaporan->isNotEmpty())
                                    <a href="{{ route('laporan.show', $laporan) }}"
                                       class="mt-1 inline-flex items-center gap-1 font-medium text-indigo-600 hover:underline">
                                        {{ $laporan->fotosLaporan->count() }} foto bukti
                                    </a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <x-badge :tone="match ($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }" size="sm">
                                    {{ $laporan->label_status }}
                                </x-badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                                {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button"
                                        data-confirm="Hapus laporan {{ $laporan->kode }} ini? Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan."
                                        data-action="{{ route('laporan.destroy', $laporan) }}"
                                        class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                    Hapus
                                </button>
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
        {{ $laporans->links() }}
    </div>

    {{-- inline script remove; confirm dialog handled globally --}}
@endsection