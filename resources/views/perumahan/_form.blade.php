@php
    $val = fn (string $field, $default = '') => old($field, $perumahan->{$field} ?? $default);
    $angka = fn ($value) => $value === null || $value === '' ? '' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

    $psuRows = old('psu', $perumahan->psu->map(fn ($row) => [
        'id' => $row->id,
        'jenis' => $row->jenis,
        'jumlah' => $angka($row->jumlah),
        'satuan' => $row->satuan,
        'kondisi' => $row->kondisi,
        'keterangan' => $row->keterangan,
    ])->all());

    $tipeRows = old('tipe_units', $perumahan->tipeUnits->map(fn ($row) => [
        'id' => $row->id,
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
        <x-card>
            <h2 class="mb-4 text-sm font-semibold text-slate-800">1. Identitas &amp; Lokasi Administratif</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="nama" label="Nama Perumahan" for="nama" required class="sm:col-span-2">
                    <input type="text" id="nama" name="nama" value="{{ $val('nama') }}" required
                           autocomplete="off" enterkeyhint="next" placeholder="Contoh: Perumahan Griya Asri"
                           class="input">
                </x-field>

                <x-field name="pengembang" label="Pengembang / Developer" for="pengembang" optional>
                    <input type="text" id="pengembang" name="pengembang" value="{{ $val('pengembang') }}"
                           autocomplete="organization" enterkeyhint="next" placeholder="Contoh: PT Karya Bangun"
                           class="input">
                </x-field>

                <x-field name="status" label="Status Perumahan" for="status" required>
                    <select id="status" name="status" class="input">
                        @foreach (\App\Models\Perumahan::STATUS as $value => $label)
                            <option value="{{ $value }}" @selected($val('status', 'komersil') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field name="kelurahan_id" label="Kelurahan" for="kelurahan_id" required class="sm:col-span-2">
                    <select id="kelurahan_id" name="kelurahan_id" required class="input">
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
                        <p class="mt-1 text-xs text-warning-700">
                            Belum ada data wilayah. Tambahkan dulu di menu
                            <a href="{{ route('wilayah.index') }}" class="underline">Master Wilayah</a>.
                        </p>
                    @endif
                </x-field>

                <x-field name="alamat" label="Alamat / Jalan" for="alamat" optional class="sm:col-span-2">
                    <input type="text" id="alamat" name="alamat" value="{{ $val('alamat') }}"
                           autocomplete="street-address" enterkeyhint="next" placeholder="Contoh: Jl. Melati No. 12"
                           class="input">
                </x-field>

                <x-field name="rt" label="RT" for="rt" optional>
                    <input type="text" id="rt" name="rt" value="{{ $val('rt') }}" inputmode="numeric"
                           enterkeyhint="next" placeholder="003" class="input">
                </x-field>

                <x-field name="rw" label="RW" for="rw" optional>
                    <input type="text" id="rw" name="rw" value="{{ $val('rw') }}" inputmode="numeric"
                           enterkeyhint="next" placeholder="005" class="input">
                </x-field>
            </div>
        </x-card>

        <x-card>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-800">2. Titik Lokasi (GIS)</h2>
                <button type="button" id="btn-lokasi-saya"
                        class="min-h-11 rounded-control border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50 md:min-h-10">
                    Gunakan lokasi saya
                </button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="latitude" label="Latitude" for="input-latitude" optional hint="Koordinat lintang, misal -6.9147440">
                    <input type="text" id="input-latitude" name="latitude" value="{{ $val('latitude') }}"
                           inputmode="decimal" autocomplete="off" enterkeyhint="next" placeholder="-6.9147440"
                           class="input">
                </x-field>

                <x-field name="longitude" label="Longitude" for="input-longitude" optional hint="Koordinat bujur, misal 107.6098100">
                    <input type="text" id="input-longitude" name="longitude" value="{{ $val('longitude') }}"
                           inputmode="decimal" autocomplete="off" enterkeyhint="next" placeholder="107.6098100"
                           class="input">
                </x-field>
            </div>
            <p class="mt-2 text-xs text-slate-500">
                Klik peta atau geser marker untuk mengisi koordinat secara otomatis.
                Peta default menampilkan wilayah <span class="font-medium text-slate-700">{{ config('peta.nama_wilayah') }}</span>.
            </p>

            <div id="map-picker" class="mt-3 h-64 w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 sm:h-80"></div>
        </x-card>

        <x-card>
            <h2 class="mb-4 text-sm font-semibold text-slate-800">3. Luasan, Unit, RTH &amp; Koefisien</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="luas_lahan" label="Luas Lahan (m²)" for="luas_lahan" required>
                    <input type="number" id="luas_lahan" step="0.01" min="0" inputmode="decimal" name="luas_lahan"
                           value="{{ $val('luas_lahan', 0) }}" required class="input">
                </x-field>

                <x-field name="jumlah_unit" label="Jumlah Unit" for="jumlah_unit" required hint="Harus sama dengan total rincian tipe di bawah.">
                    <input type="number" id="jumlah_unit" min="0" inputmode="numeric" name="jumlah_unit"
                           value="{{ $val('jumlah_unit', 0) }}" required class="input">
                    <p id="total-unit-tipe" class="mt-1 text-xs text-slate-500">
                        Total dari rincian tipe: <span class="font-semibold text-slate-700">0</span> unit
                    </p>
                </x-field>

                <x-field name="jumlah_rth" label="Jumlah RTH (titik)" for="jumlah_rth" optional>
                    <input type="number" id="jumlah_rth" min="0" inputmode="numeric" name="jumlah_rth"
                           value="{{ $val('jumlah_rth', 0) }}" class="input">
                </x-field>

                <x-field name="luas_rth" label="Luas RTH (m²)" for="luas_rth" optional>
                    <input type="number" id="luas_rth" step="0.01" min="0" inputmode="decimal" name="luas_rth"
                           value="{{ $val('luas_rth', 0) }}" class="input">
                </x-field>

                <x-field name="kdb" label="KDB (%)" for="kdb" optional>
                    <input type="number" id="kdb" step="0.01" min="0" max="100" inputmode="decimal" name="kdb"
                           value="{{ $val('kdb') }}" placeholder="60" class="input">
                </x-field>

                <x-field name="kdh" label="KDH (%)" for="kdh" optional>
                    <input type="number" id="kdh" step="0.01" min="0" max="100" inputmode="decimal" name="kdh"
                           value="{{ $val('kdh') }}" placeholder="30" class="input">
                </x-field>

                <x-field name="tahun_pembuatan" label="Tahun Pembuatan" for="tahun_pembuatan" optional>
                    <input type="number" id="tahun_pembuatan" min="1900" max="{{ date('Y') + 1 }}" inputmode="numeric"
                           name="tahun_pembuatan" value="{{ $val('tahun_pembuatan') }}" placeholder="2015" class="input">
                </x-field>

                <x-field name="tahun_selesai" label="Tahun Selesai Bangun" for="tahun_selesai" optional>
                    <input type="number" id="tahun_selesai" min="1900" max="{{ date('Y') + 10 }}" inputmode="numeric"
                           name="tahun_selesai" value="{{ $val('tahun_selesai') }}" placeholder="2018" class="input">
                </x-field>
            </div>
        </x-card>

        <x-card padded="false" data-dynamic-rows data-template="template-tipe">
            <div class="mb-4 border-b border-slate-100 p-4 sm:p-5 lg:p-6">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">4. Unit &amp; Tipe Rumah</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Rincikan tipe unit yang dibangun beserta jumlahnya.</p>
                    </div>
                    <button type="button" data-add-row
                            class="shrink-0 whitespace-nowrap rounded-control bg-slate-800 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">
                        + Tambah Tipe
                    </button>
                </div>
            </div>

            <div class="no-scrollbar overflow-x-auto px-4 pb-4 sm:px-5 lg:px-6">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="py-2 pr-2">Tipe</th>
                            <th class="py-2 pr-2">Jumlah</th>
                            <th class="py-2 pr-2">L. Tanah (m²)</th>
                            <th class="py-2 pr-2">L. Bangunan (m²)</th>
                            <th class="py-2 pr-2">Keterangan</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($tipeRows as $index => $row)
                            <tr>
                                <td class="py-2 pr-2">
                                    {{-- id baris lama agar sinkronisasi tidak mengganti id baris --}}
                                    <input type="hidden" name="tipe_units[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                    <x-field name="tipe_units.{{ $index }}.tipe" showErrors="true">
                                        <input type="text" name="tipe_units[{{ $index }}][tipe]" value="{{ $row['tipe'] ?? '' }}"
                                               placeholder="Tipe 36" class="input">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="tipe_units.{{ $index }}.jumlah_unit" showErrors="true">
                                        <input type="number" min="0" inputmode="numeric" name="tipe_units[{{ $index }}][jumlah_unit]"
                                               value="{{ $row['jumlah_unit'] ?? '' }}" class="input w-24">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="tipe_units.{{ $index }}.luas_tanah" showErrors="true">
                                        <input type="number" step="0.01" min="0" inputmode="decimal" name="tipe_units[{{ $index }}][luas_tanah]"
                                               value="{{ $row['luas_tanah'] ?? '' }}" class="input w-28">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="tipe_units.{{ $index }}.luas_bangunan" showErrors="true">
                                        <input type="number" step="0.01" min="0" inputmode="decimal" name="tipe_units[{{ $index }}][luas_bangunan]"
                                               value="{{ $row['luas_bangunan'] ?? '' }}" class="input w-28">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="tipe_units.{{ $index }}.keterangan" showErrors="true">
                                        <input type="text" name="tipe_units[{{ $index }}][keterangan]" value="{{ $row['keterangan'] ?? '' }}"
                                               class="input w-40">
                                    </x-field>
                                </td>
                                <td class="py-2 text-right">
                                    <button type="button" data-remove-row class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card padded="false" data-dynamic-rows data-template="template-psu">
            <div class="mb-4 border-b border-slate-100 p-4 sm:p-5 lg:p-6">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-800">5. Data PSU</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Prasarana, sarana, dan utilitas yang tersedia di perumahan.</p>
                    </div>
                    <button type="button" data-add-row
                            class="shrink-0 whitespace-nowrap rounded-control bg-slate-800 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">
                        + Tambah PSU
                    </button>
                </div>
            </div>

            <div class="no-scrollbar overflow-x-auto px-4 pb-4 sm:px-5 lg:px-6">
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
                                    {{-- id baris lama agar laporan yang menunjuk PSU ini tidak terputus saat save --}}
                                    <input type="hidden" name="psu[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                    <x-field name="psu.{{ $index }}.jenis" showErrors="true">
                                        <input type="text" list="jenis-psu" name="psu[{{ $index }}][jenis]" value="{{ $row['jenis'] ?? '' }}"
                                               placeholder="Jalan Lingkungan" class="input w-52">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="psu.{{ $index }}.jumlah" showErrors="true">
                                        <input type="number" step="0.01" min="0" inputmode="decimal" name="psu[{{ $index }}][jumlah]"
                                               value="{{ $row['jumlah'] ?? '' }}" class="input w-24">
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="psu.{{ $index }}.satuan" showErrors="true">
                                        <select name="psu[{{ $index }}][satuan]" class="input w-28">
                                            <option value="">-</option>
                                            @foreach (\App\Models\Perumahan::SATUAN_PSU as $satuan)
                                                <option value="{{ $satuan }}" @selected(($row['satuan'] ?? '') === $satuan)>{{ $satuan }}</option>
                                            @endforeach
                                        </select>
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="psu.{{ $index }}.kondisi" showErrors="true">
                                        <select name="psu[{{ $index }}][kondisi]" class="input w-36">
                                            <option value="">-</option>
                                            @foreach (\App\Models\Perumahan::KONDISI_PSU as $value => $label)
                                                <option value="{{ $value }}" @selected(($row['kondisi'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </x-field>
                                </td>
                                <td class="py-2 pr-2">
                                    <x-field name="psu.{{ $index }}.keterangan" showErrors="true">
                                        <input type="text" name="psu[{{ $index }}][keterangan]" value="{{ $row['keterangan'] ?? '' }}"
                                               class="input w-44">
                                    </x-field>
                                </td>
                                <td class="py-2 text-right">
                                    <button type="button" data-remove-row class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <datalist id="jenis-psu" class="mt-4 mx-4 mb-4 sm:mx-5 lg:mx-6">
                @foreach (\App\Models\Perumahan::JENIS_PSU as $jenis)
                    <option value="{{ $jenis }}"></option>
                @endforeach
            </datalist>
        </x-card>
    </div>

    {{-- Kolom kanan --}}
    <div class="space-y-4">
        <x-card>
            <h2 class="mb-4 text-sm font-semibold text-slate-800">6. Lahan Pemakaman</h2>

            <label class="flex min-h-11 items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" name="ada_lahan_pemakaman" id="ada-lahan-pemakaman" value="1"
                       @checked((bool) $val('ada_lahan_pemakaman'))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>Tersedia lahan pemakaman di dalam / sekitar perumahan</span>
            </label>

            <div id="detail-pemakaman" class="mt-4 space-y-3 {{ $val('ada_lahan_pemakaman') ? '' : 'hidden' }}">
                <x-field name="luas_lahan_pemakaman" label="Luas Lahan Pemakaman (m²)" for="luas_lahan_pemakaman" optional>
                    <input type="number" id="luas_lahan_pemakaman" step="0.01" min="0" inputmode="decimal"
                           name="luas_lahan_pemakaman" value="{{ $val('luas_lahan_pemakaman') }}" class="input">
                </x-field>

                <x-field name="lokasi_lahan_pemakaman" label="Lokasi Lahan Pemakaman" for="lokasi_lahan_pemakaman" optional>
                    <input type="text" id="lokasi_lahan_pemakaman" name="lokasi_lahan_pemakaman"
                           value="{{ $val('lokasi_lahan_pemakaman') }}" autocomplete="off" enterkeyhint="next"
                           placeholder="Blok D, sisi utara perumahan" class="input">
                </x-field>
            </div>
        </x-card>

        <x-card>
            <h2 class="mb-3 text-sm font-semibold text-slate-800">7. Keterangan Tambahan</h2>
            <x-field name="keterangan" optional hint="Maksimal 2000 karakter.">
                <textarea id="keterangan" name="keterangan" rows="6" maxlength="2000" class="input"
                          placeholder="Catatan kondisi perumahan, akses jalan, fasilitas lain, dsb.">{{ $val('keterangan') }}</textarea>
            </x-field>
        </x-card>
    </div>
</div>

{{-- --- Action bar lengket: Simpan / Batal --- --}}
<div class="sticky bottom-0 z-20 -mx-4 mt-6 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-pop backdrop-blur lg:-mx-8 lg:px-8">
    <div class="flex flex-wrap items-center justify-end gap-3">
        <x-button variant="secondary" href="{{ $cancelRoute ?? route('perumahan.index') }}">Batal</x-button>
        <x-button type="submit">{{ $submitLabel ?? 'Simpan Data' }}</x-button>
    </div>
</div>

<template id="template-tipe">
    <tr>
        <td class="py-2 pr-2">
            <input type="text" name="tipe_units[__INDEX__][tipe]" placeholder="Tipe 45" class="input">
        </td>
        <td class="py-2 pr-2">
            <input type="number" min="0" inputmode="numeric" name="tipe_units[__INDEX__][jumlah_unit]" class="input w-24">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" inputmode="decimal" name="tipe_units[__INDEX__][luas_tanah]" class="input w-28">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" inputmode="decimal" name="tipe_units[__INDEX__][luas_bangunan]" class="input w-28">
        </td>
        <td class="py-2 pr-2">
            <input type="text" name="tipe_units[__INDEX__][keterangan]" class="input w-40">
        </td>
        <td class="py-2 text-right">
            <button type="button" data-remove-row class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
        </td>
    </tr>
</template>

<template id="template-psu">
    <tr>
        <td class="py-2 pr-2">
            <input type="text" list="jenis-psu" name="psu[__INDEX__][jenis]" class="input w-52">
        </td>
        <td class="py-2 pr-2">
            <input type="number" step="0.01" min="0" inputmode="decimal" name="psu[__INDEX__][jumlah]" class="input w-24">
        </td>
        <td class="py-2 pr-2">
            <select name="psu[__INDEX__][satuan]" class="input w-28">
                <option value="">-</option>
                @foreach (\App\Models\Perumahan::SATUAN_PSU as $satuan)
                    <option value="{{ $satuan }}">{{ $satuan }}</option>
                @endforeach
            </select>
        </td>
        <td class="py-2 pr-2">
            <select name="psu[__INDEX__][kondisi]" class="input w-36">
                <option value="">-</option>
                @foreach (\App\Models\Perumahan::KONDISI_PSU as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td class="py-2 pr-2">
            <input type="text" name="psu[__INDEX__][keterangan]" class="input w-44">
        </td>
        <td class="py-2 text-right">
            <button type="button" data-remove-row class="rounded-md px-2 py-2 text-xs font-medium text-danger-600 transition hover:text-danger-700">Hapus</button>
        </td>
    </tr>
</template>
