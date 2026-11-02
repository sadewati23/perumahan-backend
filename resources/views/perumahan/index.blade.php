@extends('layouts.app')

@section('title', 'Data Perumahan')
@section('subtitle', 'Daftar lengkap perumahan beserta luasan, unit, dan status')

@section('content')
    <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-100 p-4 shadow-sm ring-1 ring-slate-200/70 lg:p-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-sm">
            <span class="inline-flex items-baseline gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3.5 py-2 text-indigo-800">
                <strong class="text-lg font-bold tabular-nums">{{ $ringkasan['jumlah'] }}</strong>
                <span class="text-xs font-medium">perumahan</span>
            </span>
            <span class="inline-flex items-baseline gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2 text-sky-800">
                <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['unit'], 0, ',', '.') }}</strong>
                <span class="text-xs font-medium">unit</span>
            </span>
            <span class="inline-flex items-baseline gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2 text-amber-800">
                <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['luas_lahan'] / 10000, 2, ',', '.') }}</strong>
                <span class="text-xs font-medium">ha lahan</span>
            </span>
            <span class="inline-flex items-baseline gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-emerald-800">
                <strong class="text-lg font-bold tabular-nums">{{ number_format($ringkasan['luas_rth'] / 10000, 2, ',', '.') }}</strong>
                <span class="text-xs font-medium">ha RTH</span>
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('perumahan.peta') }}"
               class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-800 transition hover:bg-sky-100">
                Lihat Peta
            </a>
            <a href="{{ route('perumahan.create') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                + Tambah Perumahan
            </a>
        </div>
    </div>

    <details class="mb-4 rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50/70 via-white to-white p-4 shadow-sm" open>
        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-indigo-900 md:cursor-default">
            <span class="flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700" aria-hidden="true">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/>
                    </svg>
                </span>
                Filter perumahan
            </span>
            <span class="text-xs font-normal text-indigo-600 md:hidden">ketuk untuk buka/tutup</span>
        </summary>
        <form method="GET" class="mt-3">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-medium text-slate-600">Pencarian</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama perumahan, pengembang, kelurahan, kecamatan..."
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Kecamatan</label>
                <select name="kecamatan_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    <option value="">Semua kecamatan</option>
                    @foreach ($kecamatans as $kecamatan)
                        <option value="{{ $kecamatan->id }}" @selected(($filters['kecamatan_id'] ?? '') == $kecamatan->id)>{{ $kecamatan->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tahun selesai</label>
                <select name="tahun" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500">
                    <option value="">Semua</option>
                    @foreach ($tahunList as $tahun)
                        <option value="{{ $tahun }}" @selected(($filters['tahun'] ?? '') == $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Terapkan
            </button>
            <a href="{{ route('perumahan.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </div>
        </form>
    </details>

    @php
        $activePerumahanFilters = collect($filters)->filter(fn ($v) => filled($v));
    @endphp

    @if ($activePerumahanFilters->isNotEmpty())
        <div class="mb-4 flex flex-wrap items-center gap-2">
            @foreach ($activePerumahanFilters as $key => $value)
                @php
                    if ($key === 'kecamatan_id') {
                        $labelNilai = optional($kecamatans->firstWhere('id', $value))->nama ?? $value;
                    } elseif ($key === 'status') {
                        $labelNilai = \App\Models\Perumahan::STATUS[$value] ?? $value;
                    } elseif ($key === 'tahun') {
                        $labelNilai = $value;
                    } else {
                        $labelNilai = $value;
                    }
                @endphp
                <a href="{{ route('perumahan.index', $activePerumahanFilters->except($key)->all()) }}"
                   class="inline-flex items-center gap-1 rounded-pill bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200 transition hover:bg-brand-100">
                    {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $labelNilai }}
                    <span aria-hidden="true">&times;</span>
                </a>
            @endforeach
            <a href="{{ route('perumahan.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-800">Hapus semua filter</a>
        </div>
    @endif

    <div class="mb-4 text-sm text-slate-600">
        Menampilkan <span class="font-semibold text-slate-900">{{ $perumahans->firstItem() ?? 0 }}</span>–<span class="font-semibold text-slate-900">{{ $perumahans->lastItem() ?? 0 }}</span>
        dari <span class="font-semibold text-slate-900">{{ $perumahans->total() }}</span> data
    </div>

    {{-- Tampilan kartu untuk layar kecil (HP) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($perumahans as $perumahan)
            @php $tema = $perumahan->status_theme; @endphp
            <div class="masuk-naik {{ $loop->iteration <= 4 ? 'tunda-'.$loop->iteration : '' }} rounded-xl border border-slate-200 border-l-4 {{ $tema['rail_left'] }} bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('perumahan.show', $perumahan) }}" class="block truncate font-semibold text-slate-800 hover:text-indigo-600">
                            {{ $perumahan->nama }}
                        </a>
                        <p class="truncate text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang belum diisi' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-pill px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $perumahan->status_badge_class }}">
                        {{ $perumahan->status_label }}
                    </span>
                </div>

                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Luas lahan</dt>
                        <dd class="font-medium text-slate-700">{{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }} m&sup2;</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Jumlah unit</dt>
                        <dd class="font-medium text-slate-700">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">RTH</dt>
                        <dd class="font-medium text-slate-700">{{ $perumahan->jumlah_rth }} titik</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">KDB / KDH</dt>
                        <dd class="font-medium text-slate-700">
                            {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }} /
                            {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-slate-500">Lokasi</dt>
                        <dd class="text-slate-600">
                            Kel. {{ $perumahan->kelurahan->nama ?? '-' }}, Kec. {{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}
                            &middot; RT {{ $perumahan->rt ?: '-' }}/RW {{ $perumahan->rw ?: '-' }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs text-slate-500">Tahun</dt>
                        <dd class="text-slate-600">{{ $perumahan->tahun_pembuatan ?: '-' }} &rarr; {{ $perumahan->tahun_selesai ?: '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-3 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    @if ($perumahan->punya_koordinat)
                        <span title="Titik GIS tersedia" class="text-emerald-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                            </svg>
                        </span>
                    @else
                        <span title="Titik GIS belum ada" class="text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                            </svg>
                        </span>
                    @endif

                    <div class="flex items-center gap-3">
                        <a href="{{ route('perumahan.show', $perumahan) }}" class="text-xs font-medium text-slate-600 hover:text-indigo-600">Detail</a>
                        <a href="{{ route('perumahan.edit', $perumahan) }}" class="text-xs font-medium text-amber-600 hover:text-amber-700">Ubah</a>
                        @if (auth()->user()->isAdmin())
                            <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}"
                                  data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <x-empty-state
                title="{{ $activePerumahanFilters->isNotEmpty() ? 'Tidak ada perumahan yang cocok' : 'Belum ada data perumahan' }}"
                description="{{ $activePerumahanFilters->isNotEmpty() ? 'Ubah atau hapus filter untuk melihat data lain.' : 'Tambahkan perumahan pertama melalui tombol di atas.' }}" />
        @endforelse
    </div>

    {{-- Legenda warna status --}}
    <div class="mb-3 hidden flex-wrap items-center gap-3 text-xs text-slate-500 md:flex">
        <span class="font-medium text-slate-600">Keterangan status:</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>Subsidi</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-500" aria-hidden="true"></span>Komersil</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-violet-500" aria-hidden="true"></span>Campuran</span>
    </div>

    {{-- Tampilan tabel untuk layar sedang ke atas --}}
    <div class="hidden overflow-hidden rounded-xl border border-indigo-100 bg-white shadow-sm md:block">
        <div class="no-scrollbar overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="sticky top-0 z-10 bg-indigo-50/80 text-left text-xs font-semibold uppercase tracking-wide text-indigo-800">
                    <tr>
                        <th class="px-4 py-3">Perumahan</th>
                        <th class="px-4 py-3">Lokasi (Kel./Kec. &middot; RT/RW)</th>
                        <th class="px-4 py-3 text-right">Luas Lahan</th>
                        <th class="px-4 py-3 text-right">Unit</th>
                        <th class="px-4 py-3 text-center">RTH</th>
                        <th class="px-4 py-3 text-center">KDB / KDH</th>
                        <th class="px-4 py-3 text-center">Tahun</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($perumahans as $perumahan)
                        <tr class="masuk-naik {{ $loop->iteration <= 4 ? 'tunda-'.$loop->iteration : '' }} hover:bg-indigo-50/40">
                            <td class="px-4 py-3">
                                <div class="flex items-start gap-2">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $perumahan->status_theme['dot'] }}" aria-hidden="true"></span>
                                    <div class="min-w-0">
                                        <a href="{{ route('perumahan.show', $perumahan) }}" class="font-medium text-slate-800 hover:text-indigo-600">
                                            {{ $perumahan->nama }}
                                        </a>
                                        <p class="text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang belum diisi' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <p>Kel. {{ $perumahan->kelurahan->nama ?? '-' }}</p>
                                <p class="text-xs text-slate-500">
                                    Kec. {{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}
                                    &middot; RT {{ $perumahan->rt ?: '-' }}/RW {{ $perumahan->rw ?: '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }} m&sup2;
                                <p class="text-xs text-slate-500">{{ number_format($perumahan->luas_lahan_ha, 2, ',', '.') }} ha</p>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-700">
                                {{ number_format($perumahan->jumlah_unit, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $perumahan->jumlah_rth }} titik
                                <p class="text-xs text-slate-500">{{ number_format((float) $perumahan->luas_rth, 0, ',', '.') }} m&sup2;</p>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $perumahan->kdb !== null ? rtrim(rtrim(number_format((float) $perumahan->kdb, 2, ',', '.'), '0'), ',') . '%' : '-' }}
                                /
                                {{ $perumahan->kdh !== null ? rtrim(rtrim(number_format((float) $perumahan->kdh, 2, ',', '.'), '0'), ',') . '%' : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $perumahan->tahun_pembuatan ?: '-' }}
                                <p class="text-xs text-slate-500">&rarr; {{ $perumahan->tahun_selesai ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1 rounded-pill px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $perumahan->status_badge_class }}">
                                    {{ $perumahan->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($perumahan->punya_koordinat)
                                        <span title="Titik GIS tersedia" class="text-emerald-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                                            </svg>
                                        </span>
                                    @else
                                        <span title="Titik GIS belum ada" class="text-slate-300">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
                                            </svg>
                                        </span>
                                    @endif

                                    <a href="{{ route('perumahan.show', $perumahan) }}" class="text-xs font-medium text-slate-600 hover:text-indigo-600">Detail</a>
                                    <a href="{{ route('perumahan.edit', $perumahan) }}" class="text-xs font-medium text-amber-600 hover:text-amber-700">Ubah</a>
                                    @if (auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}"
                                              data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-4">
                                <x-empty-state
                                    title="{{ $activePerumahanFilters->isNotEmpty() ? 'Tidak ada perumahan yang cocok' : 'Belum ada data perumahan' }}"
                                    description="{{ $activePerumahanFilters->isNotEmpty() ? 'Ubah atau hapus filter untuk melihat data lain.' : 'Tambahkan perumahan pertama melalui tombol di atas.' }}" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $perumahans->links() }}
    </div>
    </div>
@endsection
