<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePerumahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sudah dilindungi middleware 'auth' pada route.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ada_lahan_pemakaman' => $this->boolean('ada_lahan_pemakaman'),
        ]);
    }

    public function rules(): array
    {
        $tahunSekarang = (int) date('Y');

        return [
            'nama' => ['required', 'string', 'max:150'],
            'pengembang' => ['nullable', 'string', 'max:150'],
            'kelurahan_id' => ['required', 'exists:kelurahans,id'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'luas_lahan' => ['required', 'numeric', 'min:0'],
            'jumlah_unit' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:subsidi,komersil'],
            'tahun_pembuatan' => ['nullable', 'integer', 'min:1900', 'max:'.($tahunSekarang + 1)],
            'tahun_selesai' => ['nullable', 'integer', 'min:1900', 'max:'.($tahunSekarang + 10)],
            'jumlah_rth' => ['nullable', 'integer', 'min:0'],
            'luas_rth' => ['nullable', 'numeric', 'min:0'],
            'kdb' => ['nullable', 'numeric', 'between:0,100'],
            'kdh' => ['nullable', 'numeric', 'between:0,100'],
            'ada_lahan_pemakaman' => ['boolean'],
            'luas_lahan_pemakaman' => ['nullable', 'numeric', 'min:0'],
            'lokasi_lahan_pemakaman' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'psu' => ['nullable', 'array'],
            'psu.*.id' => ['nullable', 'integer'],
            'psu.*.jenis' => ['nullable', 'string', 'max:100'],
            'psu.*.jumlah' => ['nullable', 'numeric', 'min:0'],
            'psu.*.satuan' => ['nullable', 'string', 'max:30'],
            'psu.*.kondisi' => ['nullable', 'in:baik,rusak_ringan,rusak_berat'],
            'psu.*.keterangan' => ['nullable', 'string', 'max:255'],
            'tipe_units' => ['nullable', 'array'],
            'tipe_units.*.id' => ['nullable', 'integer'],
            'tipe_units.*.tipe' => ['nullable', 'string', 'max:50'],
            'tipe_units.*.jumlah_unit' => ['nullable', 'integer', 'min:0'],
            'tipe_units.*.luas_tanah' => ['nullable', 'numeric', 'min:0'],
            'tipe_units.*.luas_bangunan' => ['nullable', 'numeric', 'min:0'],
            'tipe_units.*.keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama perumahan wajib diisi.',
            'kelurahan_id.required' => 'Kelurahan wajib dipilih.',
            'luas_lahan.required' => 'Luas lahan wajib diisi.',
            'jumlah_unit.required' => 'Jumlah unit wajib diisi.',
            'status.required' => 'Status perumahan wajib dipilih.',
        ];
    }

    /**
     * Validasi bisnis antar-field yang tidak bisa dinyatakan sebagai aturan tunggal.
     */
    public function after(): array
    {
        return [
            $this->validasiTahun(...),
            $this->validasiLuasRth(...),
            $this->validasiTotalTipeUnit(...),
            $this->validasiKoordinat(...),
        ];
    }

    private function validasiTahun(Validator $validator): void
    {
        $tahunPembuatan = $this->input('tahun_pembuatan');
        $tahunSelesai = $this->input('tahun_selesai');

        if (filled($tahunPembuatan) && filled($tahunSelesai) && (int) $tahunSelesai < (int) $tahunPembuatan) {
            $validator->errors()->add(
                'tahun_selesai',
                'Tahun selesai tidak boleh lebih awal dari tahun pembuatan.'
            );
        }
    }

    private function validasiLuasRth(Validator $validator): void
    {
        $luasRth = $this->input('luas_rth');

        if (blank($luasRth)) {
            return;
        }

        if ((float) $luasRth > (float) $this->input('luas_lahan')) {
            $validator->errors()->add(
                'luas_rth',
                'Luas RTH tidak boleh lebih besar dari luas lahan.'
            );
        }
    }

    private function validasiTotalTipeUnit(Validator $validator): void
    {
        $tipeUnits = (array) $this->input('tipe_units', []);

        $barisTipe = array_filter($tipeUnits, fn ($row) => filled($row['tipe'] ?? null));

        if ($barisTipe === []) {
            return;
        }

        $totalTipe = (int) array_sum(array_map(
            fn ($row) => (int) ($row['jumlah_unit'] ?? 0),
            $barisTipe
        ));

        $jumlahUnit = (int) $this->input('jumlah_unit', 0);

        if ($totalTipe !== $jumlahUnit) {
            $validator->errors()->add(
                'tipe_units',
                "Total unit pada tipe unit ({$totalTipe}) tidak sesuai dengan jumlah unit perumahan ({$jumlahUnit})."
            );
        }
    }

    /**
     * Koordinat harus berada dalam batas wilayah kerja (config/peta.php 'batas').
     */
    private function validasiKoordinat(Validator $validator): void
    {
        $latitude = $this->input('latitude');
        $longitude = $this->input('longitude');

        if (blank($latitude) || blank($longitude)) {
            return;
        }

        $batas = config('peta.batas');

        foreach (['latitude' => $latitude, 'longitude' => $longitude] as $field => $nilai) {
            $min = (float) $batas["{$field}_min"];
            $max = (float) $batas["{$field}_max"];

            if ((float) $nilai < $min || (float) $nilai > $max) {
                $validator->errors()->add($field, sprintf(
                    'Koordinat %s (%s) di luar batas wilayah %s (%s s.d. %s).',
                    $field === 'latitude' ? 'lintang' : 'bujur',
                    $nilai,
                    config('peta.nama_wilayah'),
                    $min,
                    $max
                ));
            }
        }
    }

    /**
     * Data tervalidasi siap simpan: kosongkan detail pemakaman bila tidak ada lahan pemakaman.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if (is_array($data) && ! $data['ada_lahan_pemakaman']) {
            $data['luas_lahan_pemakaman'] = null;
            $data['lokasi_lahan_pemakaman'] = null;
        }

        return $data;
    }
}
