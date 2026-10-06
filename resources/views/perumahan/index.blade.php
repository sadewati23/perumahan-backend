@extends('layouts.app')

@section('title', 'Data Perumahan')
@section('subtitle', 'Daftar lengkap perumahan beserta luasan, unit, dan status')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
            Menampilkan <span class="font-semibold">{{ $ringkasan['jumlah'] }}</span> perumahan &middot;
            <span class="font-semibold">{{ number_format($ringkasan['unit'], 0, ',', '.') }}</span> unit &middot;
            <span class="font-semibold">{{ number_format($ringkasan['luas_lahan'] / 10000, 2, ',', '.') }}</span> ha lahan
        </p>
        <div class="flex gap-2">
            <a href="{{ route('perumahan.peta') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                Lihat Peta
            </a>
            <a href="{{ route('perumahan.create') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                + Tambah Perumahan
            </a>
        </div>
    </div>

    <form method="GET" class="mb-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
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
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900">
                Terapkan
            </button>
            <a href="{{ route('perumahan.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </div>
    </form>

    {{-- Tampilan kartu untuk layar kecil (HP) --}}
    <div class="space-y-3 md:hidden">
        @forelse ($perumahans as $perumahan)
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <a href="{{ route('perumahan.show', $perumahan) }}" class="block truncate font-semibold text-slate-800 hover:text-indigo-600">
                            {{ $perumahan->nama }}
                        </a>
                        <p class="truncate text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang belum diisi' }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->badge_status }}">
                        {{ $perumahan->label_status }}
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
                        <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}"
                              data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                Belum ada data perumahan yang cocok dengan pencarian.
            </div>
        @endforelse
    </div>

    {{-- Tampilan tabel untuk layar sedang ke atas --}}
    <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
        <div class="no-scrollbar overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
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
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <a href="{{ route('perumahan.show', $perumahan) }}" class="font-medium text-slate-800 hover:text-indigo-600">
                                    {{ $perumahan->nama }}
                                </a>
                                <p class="text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang belum diisi' }}</p>
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
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->badge_status }}">
                                    {{ $perumahan->label_status }}
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
                                    <form method="POST" action="{{ route('perumahan.destroy', $perumahan) }}"
                                          data-confirm="Hapus PERMANEN data perumahan {{ $perumahan->nama }}? Data tidak bisa dikembalikan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                Belum ada data perumahan yang cocok dengan pencarian.
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
@endsection
