@extends('layouts.app')

@section('title', 'Master Wilayah')
@section('subtitle', 'Kelola data kecamatan dan kelurahan')

@section('content')
    @php $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20'; @endphp

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Jumlah Kecamatan</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $kecamatans->count() }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Jumlah Kelurahan</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalKelurahan }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Perumahan Terdata</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalPerumahan }}</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-3 lg:col-span-2">
            @forelse ($kecamatans as $kecamatan)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-800">
                                @if ($kecamatan->kode)
                                    <span class="mr-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600">{{ $kecamatan->kode }}</span>
                                @endif
                                Kec. {{ $kecamatan->nama }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $kecamatan->kabupaten_kota ?: 'Kabupaten/kota belum diisi' }} &middot;
                                {{ $kecamatan->kelurahans_count }} kelurahan
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <details class="relative">
                                <summary class="cursor-pointer list-none rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    Ubah
                                </summary>
                                <form method="POST" action="{{ route('kecamatan.update', $kecamatan) }}"
                                      class="absolute right-0 z-20 mt-2 w-72 max-w-[calc(100vw-2.5rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="kode" value="{{ $kecamatan->kode }}" placeholder="Kode kecamatan (opsional)" class="{{ $inputClass }}">
                                    <input type="text" name="nama" value="{{ $kecamatan->nama }}" required placeholder="Nama kecamatan" class="{{ $inputClass }}">
                                    <input type="text" name="kabupaten_kota" value="{{ $kecamatan->kabupaten_kota }}" placeholder="Kabupaten / Kota" class="{{ $inputClass }}">
                                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Simpan Perubahan
                                    </button>
                                </form>
                            </details>

                            <form method="POST" action="{{ route('kecamatan.destroy', $kecamatan) }}"
                                  data-confirm="Hapus Kec. {{ $kecamatan->nama }} beserta seluruh kelurahannya?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                            </form>
                        </div>
                    </div>

                    @if ($kecamatan->kelurahans->isEmpty())
                        <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Belum ada kelurahan pada kecamatan ini.</p>
                    @else
                        <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($kecamatan->kelurahans as $kelurahan)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <div class="text-sm">
                                        <span class="font-medium text-slate-700">{{ $kelurahan->nama }}</span>
                                        @if ($kelurahan->kode_pos)
                                            <span class="ml-1 text-xs text-slate-400">({{ $kelurahan->kode_pos }})</span>
                                        @endif
                                        <span class="ml-2 text-xs text-slate-500">{{ $kelurahan->perumahans_count ?? $kelurahan->perumahans()->count() }} perumahan</span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <details class="relative">
                                            <summary class="cursor-pointer list-none text-xs font-medium text-amber-600 hover:text-amber-700">Ubah</summary>
                                            <form method="POST" action="{{ route('kelurahan.update', $kelurahan) }}"
                                                  class="absolute right-0 z-20 mt-2 w-64 max-w-[calc(100vw-3.5rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                                @csrf
                                                @method('PUT')
                                                <select name="kecamatan_id" class="{{ $inputClass }}">
                                                    @foreach ($kecamatans as $item)
                                                        <option value="{{ $item->id }}" @selected($item->id === $kelurahan->kecamatan_id)>Kec. {{ $item->nama }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" name="kode" value="{{ $kelurahan->kode }}" placeholder="Kode kelurahan (opsional)" class="{{ $inputClass }}">
                                                <input type="text" name="nama" value="{{ $kelurahan->nama }}" required placeholder="Nama kelurahan" class="{{ $inputClass }}">
                                                <input type="text" name="kode_pos" value="{{ $kelurahan->kode_pos }}" placeholder="Kode pos" class="{{ $inputClass }}">
                                                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                    Simpan Perubahan
                                                </button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('kelurahan.destroy', $kelurahan) }}"
                                              data-confirm="Hapus Kel. {{ $kelurahan->nama }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">
                    Belum ada data kecamatan. Tambahkan melalui form di samping.
                </div>
            @endforelse
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Kecamatan</h2>
                <form method="POST" action="{{ route('kecamatan.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="kode" placeholder="Kode (opsional)" class="{{ $inputClass }}">
                    <input type="text" name="nama" required placeholder="Nama kecamatan" class="{{ $inputClass }}">
                    <input type="text" name="kabupaten_kota" placeholder="Kabupaten / Kota" class="{{ $inputClass }}">
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Simpan Kecamatan
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Kelurahan</h2>
                <form method="POST" action="{{ route('kelurahan.store') }}" class="space-y-3">
                    @csrf
                    <select name="kecamatan_id" required class="{{ $inputClass }}">
                        <option value="">— Pilih kecamatan —</option>
                        @foreach ($kecamatans as $kecamatan)
                            <option value="{{ $kecamatan->id }}">Kec. {{ $kecamatan->nama }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="kode" placeholder="Kode (opsional)" class="{{ $inputClass }}">
                    <input type="text" name="nama" required placeholder="Nama kelurahan" class="{{ $inputClass }}">
                    <input type="text" name="kode_pos" placeholder="Kode pos" class="{{ $inputClass }}">
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Simpan Kelurahan
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
