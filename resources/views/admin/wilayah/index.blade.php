@extends('layouts.admin')

@section('title', 'Master Wilayah')
@section('subtitle', 'Kelola data kecamatan dan kelurahan')

@section('content')
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-stat label="Jumlah Kecamatan" :value="$kecamatans->count()" satuan="kecamatan" icon="wilayah" tone="brand" />
        <x-stat label="Jumlah Kelurahan" :value="$totalKelurahan" satuan="kelurahan" icon="rumah" tone="info" />
        <x-stat label="Perumahan Terdata" :value="$totalPerumahan" satuan="lokasi" icon="perumahan" tone="success" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-3 lg:col-span-2">
            @forelse ($kecamatans as $kecamatan)
                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-semibold text-slate-800">
                                @if ($kecamatan->kode)
                                    <span class="rounded bg-kertas-tua px-1.5 py-0.5 font-mono text-xs font-medium text-slate-600">{{ $kecamatan->kode }}</span>
                                @endif
                                <span>Kec. {{ $kecamatan->nama }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-slate-600">
                                {{ $kecamatan->kabupaten_kota ?: 'Kabupaten/kota belum diisi' }} &middot;
                                {{ $kecamatan->kelurahans_count }} kelurahan
                            </p>
                        </div>

                        <div class="flex items-center gap-1">
                            <details class="relative">
                                <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1.5 rounded-control border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-kertas lg:min-h-0">
                                    <x-icon name="pensil" class="h-3.5 w-3.5"/>
                                    Ubah
                                </summary>
                                <form method="POST" action="{{ route('kecamatan.update', $kecamatan) }}"
                                      class="absolute right-0 z-20 mt-2 w-72 max-w-[calc(100vw-2.5rem)] space-y-2 rounded-card border border-garis bg-white p-3 shadow-pop">
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

                            @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('kecamatan.destroy', $kecamatan) }}"
                                      data-confirm="Hapus Kec. {{ $kecamatan->nama }} beserta seluruh kelurahannya?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-2.5 py-2 text-xs font-medium text-danger-700 transition hover:bg-danger-50 lg:min-h-0">
                                        <x-icon name="sampah" class="h-3.5 w-3.5"/>
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($kecamatan->kelurahans->isEmpty())
                        <p class="mt-3 rounded-lg bg-kertas px-3 py-2 text-xs text-slate-600">Belum ada kelurahan pada kecamatan ini.</p>
                    @else
                        <ul class="mt-3 divide-y divide-garis border-t border-garis">
                            @foreach ($kecamatan->kelurahans as $kelurahan)
                                <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                    <div class="min-w-0 text-sm">
                                        <span class="font-medium text-slate-700">{{ $kelurahan->nama }}</span>
                                        @if ($kelurahan->kode_pos)
                                            <span class="ml-1 text-xs text-slate-500">({{ $kelurahan->kode_pos }})</span>
                                        @endif
                                        <span class="ml-2 block text-xs text-slate-600 sm:inline">{{ $kelurahan->perumahans_count ?? $kelurahan->perumahans()->count() }} perumahan</span>
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <details class="relative">
                                            <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1.5 rounded-control px-2.5 py-2 text-xs font-medium text-brand-700 transition hover:bg-brand-50 lg:min-h-0">
                                                <x-icon name="pensil" class="h-3.5 w-3.5"/>
                                                Ubah
                                            </summary>
                                            <form method="POST" action="{{ route('kelurahan.update', $kelurahan) }}"
                                                  class="absolute right-0 z-20 mt-2 w-64 max-w-[calc(100vw-3.5rem)] space-y-2 rounded-card border border-garis bg-white p-3 shadow-pop">
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

                                        @if (auth()->user()->isAdmin())
                                            <form method="POST" action="{{ route('kelurahan.destroy', $kelurahan) }}"
                                                  data-confirm="Hapus Kel. {{ $kelurahan->nama }}?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-2.5 py-2 text-xs font-medium text-danger-700 transition hover:bg-danger-50 lg:min-h-0">
                                                    <x-icon name="sampah" class="h-3.5 w-3.5"/>
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            @empty
                <x-empty-state
                    title="Belum ada data kecamatan"
                    description="Tambahkan kecamatan pertama melalui form di samping untuk mulai mendata wilayah." />
            @endforelse
        </div>

        <div class="space-y-4">
            <x-card heading="Tambah Kecamatan" meta="Kecamatan baru untuk pengelompokan kelurahan">
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
                    <x-button type="submit" class="w-full">
                        <x-icon name="simpan" class="h-4 w-4"/>
                        Simpan Kecamatan
                    </x-button>
                </form>
            </x-card>

            <x-card heading="Tambah Kelurahan" meta="Kelurahan di bawah kecamatan terpilih">
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
                    <x-button type="submit" class="w-full">
                        <x-icon name="simpan" class="h-4 w-4"/>
                        Simpan Kelurahan
                    </x-button>
                </form>
            </x-card>
        </div>
    </div>
@endsection
