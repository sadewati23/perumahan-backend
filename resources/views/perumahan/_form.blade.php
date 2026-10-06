@php
    $val = fn (string $field, $default = '') => old($field, $perumahan->{$field} ?? $default);
    $angka = fn ($value) => $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20';
    $labelClass = 'mb-1.5 block text-sm font-medium text-slate-700';

    $psuRows = old('psu', $perumahan->psu->map(fn ($row) => [
        'jenis' => $row->jenis,
        'jumlah' => $angka($row->jumlah),
        'satuan' => $row->satuan,
        'kondisi' => $row->kondisi,
        'keterangan' => $row->keterangan,
    ])->all());

    $tipeRows = old('tipe_units', $perumahan->tipeUnits->map(fn ($row) => [
        'tipe' => $row->tipe,
        'jumlah_unit' => $row->jumlah_unit,
        'luas_tanah' => $angka($row->luas_tanah),
        'luas_bangunan' => $angka($row->luas_bangunan),
        'keterangan' => $row->keterangan,
    ])->all());

    if (empty($psuRows)) {
        $psuRows = [['jenis' => '', 'jumlah' => '', 'satuan' => '', 'kondisi' => '', 'keterangan' => '']];
    }
    if (empty($tipeRows)) {
        $tipeRows = [['tipe' => '', 'jumlah_unit' => '', 'luas_tanah' => '', 'luas_bangunan' => '', 'keterangan' => '']];
    }
@endphp

<div class="grid gap-4 xl:grid-cols-3">
    {{-- Kolom kiri --}}
    <div class="space-y-4 xl:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-slate-800">1. Identitas &amp; Lokasi Administratif</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Nama Perumahan <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" value="{{ $val('nama') }}" required placeholder="Contoh: Perumahan Griya Asri"
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">Pengembang / Developer</label>
                    <input type="text" name="pengembang" value="{{ $val('pengembang') }}" placeholder="Contoh: PT Karya Bangun"
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">Status Perumahan <span class="text-rose-500">*</span></label>
                    <select name="status" class="{{ $inputClass }}">
                        @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                            <option value="{{ $value }}" @selected($val('status', 'komersil') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Kelurahan <span class="text-rose-500">*</span></label>
                    <select name="kelurahan_id" required class="{{ $inputClass }}">
                        <option value="">— Pilih kelurahan —</option>
                        @foreach ($kecamatans as $kecamatan)
                            <optgroup label="Kec. {{ $kecamatan->nama }}">
                                @foreach ($kecamatan->kelurahans as $kelurahan)
                                    <option value="{{ $kelurahan->id }}" @selected((string) $val('kelurahan_id') === (string) $kelurahan->id)>
                                        Kel. {{ $kelurahan->nama }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @if ($kecamatans->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">
                            Belum ada data wilayah. Tambahkan dulu di menu
                            <a href="{{ route('wilayah.index') }}" class="underline">Master Wilayah</a>.
                        </p>
                    @endif
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Alamat / Jalan</label>
                    <input type="text" name="alamat" value="{{ $val('alamat') }}" placeholder="Contoh: Jl. Melati No. 12"
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">RT</label>
                    <input type="text" name="rt" value="{{ $val('rt') }}" placeholder="003" class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">RW</label>
                    <input type="text" name="rw" value="{{ $val('rw') }}" placeholder="005" class="{{ $inputClass }}">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">2. Titik Lokasi (GIS)</h2>
                <button type="button" id="btn-lokasi-saya"
                        class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50">
                    Gunakan lokasi saya
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Latitude</label>
                    <input type="text" id="input-latitude" name="latitude" value="{{ $val('latitude') }}" placeholder="-6.9147440"
                           class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Longitude</label>
                    <input type="text" id="input-longitude" name="longitude" value="{{ $val('longitude') }}" placeholder="107.6098100"
                           class="{{ $inputClass }}">
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-500">
                Klik peta atau geser marker untuk mengisi koordinat secara otomatis.
                Peta default menampilkan wilayah <span class="font-medium text-slate-700">{{ config('peta.nama_wilayah') }}</span>.
            </p>

            <div id="map-picker" class="mt-3 h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 sm:h-80"></div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-slate-800">3. Luasan, Unit, RTH &amp; Koefisien</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="{{ $labelClass }}">Luas Lahan (m&sup2;) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="luas_lahan" value="{{ $val('luas_lahan', 0) }}" required
                           class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Jumlah Unit <span class="text-rose-500">*</span></label>
                    <input type="number" min="0" name="jumlah_unit" value="{{ $val('jumlah_unit', 0) }}" required
                           class="{{ $inputClass }}">
                    <p id="total-unit-tipe" class="mt-1 text-xs text-slate-500">
                        Total dari rincian tipe: <span class="font-semibold text-slate-700">0</span> unit
                    </p>
                </div>
                <div>
                    <label class="{{ $labelClass }}">Jumlah RTH (titik)</label>
                    <input type="number" min="0" name="jumlah_rth" value="{{ $val('jumlah_rth', 0) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Luas RTH (m&sup2;)</label>
                    <input type="number" step="0.01" min="0" name="luas_rth" value="{{ $val('luas_rth', 0) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">KDB (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="kdb" value="{{ $val('kdb') }}" placeholder="60"
                           class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">KDH (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="kdh" value="{{ $val('kdh') }}" placeholder="30"
                           class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Tahun Pembuatan</label>
                    <input type="number" min="1900" max="{{ date('Y') + 1 }}" name="tahun_pembuatan"
                           value="{{ $val('tahun_pembuatan') }}" placeholder="2015" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Tahun Selesai Bangun</label>
                    <input type="number" min="1900" max="{{ date('Y') + 10 }}" name="tahun_selesai"
                           value="{{ $val('tahun_selesai') }}" placeholder="2018" class="{{ $inputClass }}">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-dynamic-rows data-template="template-tipe">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">4. Unit &amp; Tipe Rumah</h2>
                    <p class="text-xs text-slate-500">Rincikan tipe unit yang dibangun beserta jumlahnya.</p>
                </div>
                <button type="button" data-add-row
                        class="shrink-0 whitespace-nowrap rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-slate-900">
                    + Tambah Tipe
                </button>
            </div>

            <div class="no-scrollbar overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-2">Tipe</th>
                            <th class="py-2 pr-2">Jumlah</th>
                            <th class="py-2 pr-2">L. Tanah (m&sup2;)</th>
                            <th class="py-2 pr-2">L. Bangunan (m&sup2;)</th>
                            <th class="py-2 pr-2">Keterangan</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tipeRows as $index => $row)
                            <tr>
                                <td class="py-2 pr-2">
                                    <input type="text" name="tipe_units[{{ $index }}][tipe]" value="{{ $row['tipe'] ?? '' }}"
                                           placeholder="Tipe 36" class="{{ $inputClass }}">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" min="0" name="tipe_units[{{ $index }}][jumlah_unit]"
                                           value="{{ $row['jumlah_unit'] ?? '' }}" class="{{ $inputClass }} w-24">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" step="0.01" min="0" name="tipe_units[{{ $index }}][luas_tanah]"
                                           value="{{ $row['luas_tanah'] ?? '' }}" class="{{ $inputClass }} w-28">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" step="0.01" min="0" name="tipe_units[{{ $index }}][luas_bangunan]"
                                           value="{{ $row['luas_bangunan'] ?? '' }}" class="{{ $inputClass }} w-28">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="text" name="tipe_units[{{ $index }}][keterangan]" value="{{ $row['keterangan'] ?? '' }}"
                                           class="{{ $inputClass }} w-40">
                                </td>
                                <td class="py-2 text-right">
                                    <button type="button" data-remove-row class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-dynamic-rows data-template="template-psu">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">5. Data PSU</h2>
                    <p class="text-xs text-slate-500">Prasarana, sarana, dan utilitas yang tersedia di perumahan.</p>
                </div>
                <button type="button" data-add-row
                        class="shrink-0 whitespace-nowrap rounded-md bg-slate-800 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-slate-900">
                    + Tambah PSU
                </button>
            </div>

            <div class="no-scrollbar overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-2">Jenis PSU</th>
                            <th class="py-2 pr-2">Jumlah</th>
                            <th class="py-2 pr-2">Satuan</th>
                            <th class="py-2 pr-2">Kondisi</th>
                            <th class="py-2 pr-2">Keterangan</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($psuRows as $index => $row)
                            <tr>
                                <td class="py-2 pr-2">
                                    <input type="text" list="jenis-psu" name="psu[{{ $index }}][jenis]" value="{{ $row['jenis'] ?? '' }}"
                                           placeholder="Jalan Lingkungan" class="{{ $inputClass }} w-52">
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="number" step="0.01" min="0" name="psu[{{ $index }}][jumlah]"
                                           value="{{ $row['jumlah'] ?? '' }}" class="{{ $inputClass }} w-24">
                                </td>
                                <td class="py-2 pr-2">
                                    <select name="psu[{{ $index }}][satuan]" class="{{ $inputClass }} w-28">
                                        <option value="">-</option>
                                        @foreach (\App\Models\Perumahan::SATUAN_PSU as $satuan)
                                            <option value="{{ $satuan }}" @selected(($row['satuan'] ?? '') === $satuan)>{{ $satuan }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-2">
                                    <select name="psu[{{ $index }}][kondisi]" class="{{ $inputClass }} w-36">
                                        <option value="">-</option>
                                        @foreach (\App\Models\Perumahan::KONDISI_PSU as $value => $label)
                                            <option value="{{ $value }}" @selected(($row['kondisi'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-2 pr-2">
                                    <input type="text" name="psu[{{ $index }}][keterangan]" value="{{ $row['keterangan'] ?? '' }}"
                                           class="{{ $inputClass }} w-44">
                                </td>
                                <td class="py-2 text-right">
                                    <button type="button" data-remove-row class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <datalist id="jenis-psu">
                @foreach (\App\Models\Perumahan::JENIS_PSU as $jenis)
                    <option value="{{ $jenis }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>

    {{-- Kolom kanan --}}
    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-slate-800">6. Lahan Pemakaman</h2>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="ada_lahan_pemakaman" id="ada-lahan-pemakaman" value="1"
                       @checked((bool) $val('ada_lahan_pemakaman'))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span>Tersedia lahan pemakaman di dalam / sekitar perumahan</span>
            </label>

            <div id="detail-pemakaman" class="mt-4 space-y-3 {{ $val('ada_lahan_pemakaman') ? '' : 'hidden' }}">
                <div>
                    <label class="{{ $labelClass }}">Luas Lahan Pemakaman (m&sup2;)</label>
                    <input type="number" step="0.01" min="0" name="luas_lahan_pemakaman"
                           value="{{ $val('luas_lahan_pemakaman') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Lokasi Lahan Pemakaman</label>
                    <input type="text" name="lokasi_lahan_pemakaman" value="{{ $val('lokasi_lahan_pemakaman') }}"
                           placeholder="Blok D, sisi utara perumahan" class="{{ $inputClass }}">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-800">7. Keterangan Tambahan</h2>
            <textarea name="keterangan" rows="6" class="{{ $inputClass }}"
                      placeholder="Catatan kondisi perumahan, akses jalan, fasilitas lain, dsb.">{{ $val('keterangan') }}</textarea>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <button type="submit"
                    class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                {{ $submitLabel ?? 'Simpan Data' }}
            </button>
            <a href="{{ $cancelRoute ?? route('perumahan.index') }}"
               class="mt-2 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Batal
            </a>
        </div>
    </div>
</div>

<template id="template-tipe">
    <tr>
        <td class="py-2 pr-2">
            <input type="text" name="tipe_units[__INDEX__][tipe]" placeholder="Tipe 45" class="{{ $inputClass }}">
        </td>
        <td class="py-2 pr-2">
            <input type="number" min="0" name="tipe_units[__INDEX__][jumlah_unit]" class="{{ $inputClass }} w-24">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" name="tipe_units[__INDEX__][luas_tanah]" class="{{ $inputClass }} w-28">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" name="tipe_units[__INDEX__][luas_bangunan]" class="{{ $inputClass }} w-28">
        </td>
        <td class="py-2 pr-2">
            <input type="text" name="tipe_units[__INDEX__][keterangan]" class="{{ $inputClass }} w-40">
        </td>
        <td class="py-2 text-right">
            <button type="button" data-remove-row class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
        </td>
    </tr>
</template>

<template id="template-psu">
    <tr>
        <td class="py-2 pr-2">
            <input type="text" list="jenis-psu" name="psu[__INDEX__][jenis]" class="{{ $inputClass }} w-52">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" name="psu[__INDEX__][jumlah]" class="{{ $inputClass }} w-24">
        </td>
        <td class="py-2 pr-2">
            <select name="psu[__INDEX__][satuan]" class="{{ $inputClass }} w-28">
                <option value="">-</option>
                @foreach (\App\Models\Perumahan::SATUAN_PSU as $satuan)
                    <option value="{{ $satuan }}">{{ $satuan }}</option>
                @endforeach
            </select>
        </td>
        <td class="py-2 pr-2">
            <select name="psu[__INDEX__][kondisi]" class="{{ $inputClass }} w-36">
                <option value="">-</option>
                @foreach (\App\Models\Perumahan::KONDISI_PSU as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td class="py-2 pr-2">
            <input type="text" name="psu[__INDEX__][keterangan]" class="{{ $inputClass }} w-44">
        </td>
        <td class="py-2 text-right">
            <button type="button" data-remove-row class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
        </td>
    </tr>
</template>
