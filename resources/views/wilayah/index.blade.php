@extends('layouts.app')

@section('title', 'Master Wilayah')
@section('subtitle', 'Kelola data kecamatan dan kelurahan')

@section('content')
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
                                <summary class="cursor-pointer list-none rounded-control border border-slate-300 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                                    Ubah
                                </summary>
                                <form method="POST" action="{{ route('kecamatan.update', $kecamatan) }}"
                                      class="absolute right-0 z-20 mt-2 w-72 max-w-[calc(100vw-2.5rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    @csrf
                                    @method('PUT')
                                    <x-field name="kode" for="kecamatan-{{ $kecamatan->id }}-kode" label="Kode kecamatan" optional :show-errors="false">
                                        <input id="kecamatan-{{ $kecamatan->id }}-kode" type="text" name="kode" value="{{ $kecamatan->kode }}"
                                               inputmode="numeric" autocomplete="off" class="input" placeholder="Kode kecamatan (opsional)">
                                    </x-field>
                                    <x-field name="nama" for="kecamatan-{{ $kecamatan->id }}-nama" label="Nama kecamatan" :show-errors="false">
                                        <input id="kecamatan-{{ $kecamatan->id }}-nama" type="text" name="nama" value="{{ $kecamatan->nama }}"
                                               required autocomplete="off" class="input">
                                    </x-field>
                                    <x-field name="kabupaten_kota" for="kecamatan-{{ $kecamatan->id }}-kabupaten" label="Kabupaten / Kota" optional :show-errors="false">
                                        <input id="kecamatan-{{ $kecamatan->id }}-kabupaten" type="text" name="kabupaten_kota" value="{{ $kecamatan->kabupaten_kota }}"
                                               autocomplete="address-level2" enterkeyhint="done" class="input">
                                    </x-field>
                                    <x-button type="submit" class="w-full">Simpan Perubahan</x-button>
                                </form>
                            </details>

                            <form method="POST" action="{{ route('kecamatan.destroy', $kecamatan) }}"
                                  data-confirm="Hapus Kec. {{ $kecamatan->nama }} beserta seluruh kelurahannya?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
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
                                            <summary class="cursor-pointer list-none rounded-md px-2 py-2 text-xs font-medium text-warning-700 transition hover:text-warning-800">Ubah</summary>
                                            <form method="POST" action="{{ route('kelurahan.update', $kelurahan) }}"
                                                  class="absolute right-0 z-20 mt-2 w-64 max-w-[calc(100vw-3.5rem)] space-y-2 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                                @csrf
                                                @method('PUT')
                                                <x-field name="kecamatan_id" for="kelurahan-{{ $kelurahan->id }}-kecamatan" label="Kecamatan" :show-errors="false">
                                                    <select id="kelurahan-{{ $kelurahan->id }}-kecamatan" name="kecamatan_id" class="input">
                                                        @foreach ($kecamatans as $item)
                                                            <option value="{{ $item->id }}" @selected($item->id === $kelurahan->kecamatan_id)>Kec. {{ $item->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                </x-field>
                                                <x-field name="kode" for="kelurahan-{{ $kelurahan->id }}-kode" label="Kode kelurahan" optional :show-errors="false">
                                                    <input id="kelurahan-{{ $kelurahan->id }}-kode" type="text" name="kode" value="{{ $kelurahan->kode }}"
                                                           inputmode="numeric" autocomplete="off" class="input">
                                                </x-field>
                                                <x-field name="nama" for="kelurahan-{{ $kelurahan->id }}-nama" label="Nama kelurahan" :show-errors="false">
                                                    <input id="kelurahan-{{ $kelurahan->id }}-nama" type="text" name="nama" value="{{ $kelurahan->nama }}"
                                                           required autocomplete="off" class="input">
                                                </x-field>
                                                <x-field name="kode_pos" for="kelurahan-{{ $kelurahan->id }}-kode-pos" label="Kode pos" optional :show-errors="false">
                                                    <input id="kelurahan-{{ $kelurahan->id }}-kode-pos" type="text" name="kode_pos" value="{{ $kelurahan->kode_pos }}"
                                                           inputmode="numeric" autocomplete="postal-code" enterkeyhint="done" class="input">
                                                </x-field>
                                                <x-button type="submit" class="w-full">Simpan Perubahan</x-button>
                                            </form>
                                        </details>

                                        <form method="POST" action="{{ route('kelurahan.destroy', $kelurahan) }}"
                                              data-confirm="Hapus Kel. {{ $kelurahan->nama }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <x-empty-state
                    title="Belum ada data kecamatan"
                    description="Tambahkan kecamatan pertama melalui form di samping untuk mulai mendata wilayah.">
                    <x-slot:actions></x-slot:actions>
                </x-empty-state>
            @endforelse
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Kecamatan</h2>
                <form method="POST" action="{{ route('kecamatan.store') }}" class="space-y-3" data-unsaved-guard="Data kecamatan belum disimpan.">
                    @csrf
                    <x-field name="kode" label="Kode" optional>
                        <input id="tambah-kecamatan-kode" type="text" name="kode" value="{{ old('kode') }}"
                               inputmode="numeric" autocomplete="off" enterkeyhint="next" placeholder="Kode (opsional)" class="input">
                    </x-field>
                    <x-field name="nama" label="Nama kecamatan" required>
                        <input id="tambah-kecamatan-nama" type="text" name="nama" value="{{ old('nama') }}"
                               required autocomplete="off" enterkeyhint="next" placeholder="Nama kecamatan" class="input">
                    </x-field>
                    <x-field name="kabupaten_kota" label="Kabupaten / Kota" optional>
                        <input id="tambah-kecamatan-kabupaten" type="text" name="kabupaten_kota" value="{{ old('kabupaten_kota') }}"
                               autocomplete="address-level2" enterkeyhint="done" placeholder="Kabupaten / Kota" class="input">
                    </x-field>
                    <x-button type="submit" class="w-full">Simpan Kecamatan</x-button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Tambah Kelurahan</h2>
                <form method="POST" action="{{ route('kelurahan.store') }}" class="space-y-3" data-unsaved-guard="Data kelurahan belum disimpan.">
                    @csrf
                    <x-field name="kecamatan_id" label="Kecamatan" required>
                        <select id="tambah-kelurahan-kecamatan" name="kecamatan_id" required class="input">
                            <option value="">— Pilih kecamatan —</option>
                            @foreach ($kecamatans as $kecamatan)
                                <option value="{{ $kecamatan->id }}" @selected(old('kecamatan_id') == $kecamatan->id)>Kec. {{ $kecamatan->nama }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field name="kode" label="Kode" optional>
                        <input id="tambah-kelurahan-kode" type="text" name="kode" value="{{ old('kode') }}"
                               inputmode="numeric" autocomplete="off" enterkeyhint="next" placeholder="Kode (opsional)" class="input">
                    </x-field>
                    <x-field name="nama" label="Nama kelurahan" required>
                        <input id="tambah-kelurahan-nama" type="text" name="nama" value="{{ old('nama') }}"
                               required autocomplete="off" enterkeyhint="next" placeholder="Nama kelurahan" class="input">
                    </x-field>
                    <x-field name="kode_pos" label="Kode pos" optional>
                        <input id="tambah-kelurahan-kode-pos" type="text" name="kode_pos" value="{{ old('kode_pos') }}"
                               inputmode="numeric" autocomplete="postal-code" enterkeyhint="done" placeholder="Kode pos" class="input">
                    </x-field>
                    <x-button type="submit" class="w-full">Simpan Kelurahan</x-button>
                </form>
            </div>
        </div>
    </div>
@endsection
