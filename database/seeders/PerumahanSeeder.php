<?php

namespace Database\Seeders;

use App\Models\Kelurahan;
use App\Models\Perumahan;
use Illuminate\Database\Seeder;

class PerumahanSeeder extends Seeder
{
    /**
     * Contoh data perumahan di Kota Cirebon sebagai gambaran format pengisian.
     * Ganti/hapus dengan data hasil pendataan lapangan.
     */
    public function run(): void
    {
        $contoh = [
            [
                'nama' => 'Perumahan Griya Kalijaga Asri',
                'pengembang' => 'PT Cirebon Karya Bangun',
                'kelurahan' => 'Kalijaga',
                'alamat' => 'Jl. Kalijaga No. 45',
                'rt' => '003', 'rw' => '007',
                'latitude' => -6.7465000, 'longitude' => 108.5420000,
                'luas_lahan' => 32000, 'jumlah_unit' => 240, 'status' => 'komersil',
                'tahun_pembuatan' => 2018, 'tahun_selesai' => 2021,
                'jumlah_rth' => 3, 'luas_rth' => 3200, 'kdb' => 55, 'kdh' => 35,
                'ada_lahan_pemakaman' => false, 'luas_lahan_pemakaman' => null, 'lokasi_lahan_pemakaman' => null,
                'keterangan' => 'Akses jalan lingkungan 8 meter, dekat dengan akses Jalan Raya Kalijaga.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 2400, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 2600, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Jaringan Air Bersih', 'jumlah' => 240, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Penerangan Jalan Umum', 'jumlah' => 48, 'satuan' => 'titik', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 120, 'luas_tanah' => 72, 'luas_bangunan' => 36],
                    ['tipe' => 'Tipe 45', 'jumlah_unit' => 80, 'luas_tanah' => 90, 'luas_bangunan' => 45],
                    ['tipe' => 'Tipe 60', 'jumlah_unit' => 40, 'luas_tanah' => 120, 'luas_bangunan' => 60],
                ],
            ],
            [
                'nama' => 'Perumahan Sunyaragi Residence',
                'pengembang' => 'PT Sunyaragi Properti',
                'kelurahan' => 'Sunyaragi',
                'alamat' => 'Jl. Sunyaragi Raya No. 12',
                'rt' => '005', 'rw' => '003',
                'latitude' => -6.7360000, 'longitude' => 108.5665000,
                'luas_lahan' => 12000, 'jumlah_unit' => 60, 'status' => 'komersil',
                'tahun_pembuatan' => 2021, 'tahun_selesai' => 2023,
                'jumlah_rth' => 4, 'luas_rth' => 1800, 'kdb' => 45, 'kdh' => 40,
                'ada_lahan_pemakaman' => false, 'luas_lahan_pemakaman' => null, 'lokasi_lahan_pemakaman' => null,
                'keterangan' => 'Cluster dengan satu akses gerbang, dekat kawasan wisata Gua Sunyaragi.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 900, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Pos Keamanan / Pos Jaga', 'jumlah' => 2, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Jaringan Listrik', 'jumlah' => 60, 'satuan' => 'unit', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 60', 'jumlah_unit' => 40, 'luas_tanah' => 120, 'luas_bangunan' => 60],
                    ['tipe' => 'Tipe 90', 'jumlah_unit' => 20, 'luas_tanah' => 150, 'luas_bangunan' => 90],
                ],
            ],
            [
                'nama' => 'Perumahan Bumi Pekiringan Permai',
                'pengembang' => 'Koperasi Warga Pekiringan',
                'kelurahan' => 'Pekiringan',
                'alamat' => 'Jl. Pekiringan No. 88',
                'rt' => '002', 'rw' => '011',
                'latitude' => -6.7145000, 'longitude' => 108.5630000,
                'luas_lahan' => 18500, 'jumlah_unit' => 200, 'status' => 'subsidi',
                'tahun_pembuatan' => 2019, 'tahun_selesai' => 2022,
                'jumlah_rth' => 2, 'luas_rth' => 1850, 'kdb' => 60, 'kdh' => 30,
                'ada_lahan_pemakaman' => true, 'luas_lahan_pemakaman' => 1200, 'lokasi_lahan_pemakaman' => 'Blok D, sisi utara perumahan',
                'keterangan' => 'Perumahan subsidi dengan skema KPR FLPP, jarak 1 km dari Alun-Alun Kejaksan.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 1800, 'satuan' => 'meter', 'kondisi' => 'rusak_ringan'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 1900, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Tempat Persampahan', 'jumlah' => 6, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Sarana Ibadah', 'jumlah' => 1, 'satuan' => 'unit', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 140, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 60, 'luas_tanah' => 72, 'luas_bangunan' => 36],
                ],
            ],
            [
                'nama' => 'Perumahan Kasepuhan Harmoni',
                'pengembang' => 'PT Harmoni Cirebon Sejahtera',
                'kelurahan' => 'Kasepuhan',
                'alamat' => 'Jl. Kasepuhan No. 20',
                'rt' => '004', 'rw' => '005',
                'latitude' => -6.7130000, 'longitude' => 108.5700000,
                'luas_lahan' => 15000, 'jumlah_unit' => 150, 'status' => 'subsidi',
                'tahun_pembuatan' => 2017, 'tahun_selesai' => 2020,
                'jumlah_rth' => 2, 'luas_rth' => 1400, 'kdb' => 58, 'kdh' => 32,
                'ada_lahan_pemakaman' => true, 'luas_lahan_pemakaman' => 900, 'lokasi_lahan_pemakaman' => 'Blok B, sisi timur perumahan',
                'keterangan' => 'Berada di kawasan bersejarah Kasepuhan, dekat Keraton Kasepuhan Cirebon.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 1500, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 1600, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Jaringan Air Bersih', 'jumlah' => 150, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Sarana Ibadah', 'jumlah' => 1, 'satuan' => 'unit', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 90, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 60, 'luas_tanah' => 72, 'luas_bangunan' => 36],
                ],
            ],
            [
                'nama' => 'Perumahan Pulasaren Indah',
                'pengembang' => 'CV Pulasaren Griya',
                'kelurahan' => 'Pulasaren',
                'alamat' => 'Jl. Pulasaren No. 7',
                'rt' => '001', 'rw' => '009',
                'latitude' => -6.7158000, 'longitude' => 108.5668000,
                'luas_lahan' => 8000, 'jumlah_unit' => 45, 'status' => 'komersil',
                'tahun_pembuatan' => 2020, 'tahun_selesai' => 2022,
                'jumlah_rth' => 2, 'luas_rth' => 800, 'kdb' => 50, 'kdh' => 35,
                'ada_lahan_pemakaman' => false, 'luas_lahan_pemakaman' => null, 'lokasi_lahan_pemakaman' => null,
                'keterangan' => 'Perumahan skala kecil dengan akses dekat Jalan Siliwangi.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 600, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 650, 'satuan' => 'meter', 'kondisi' => 'rusak_ringan'],
                    ['jenis' => 'Penerangan Jalan Umum', 'jumlah' => 12, 'satuan' => 'titik', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 45', 'jumlah_unit' => 30, 'luas_tanah' => 90, 'luas_bangunan' => 45],
                    ['tipe' => 'Tipe 70', 'jumlah_unit' => 15, 'luas_tanah' => 130, 'luas_bangunan' => 70],
                ],
            ],
            [
                'nama' => 'Perumahan Kesenden Permai',
                'pengembang' => 'PT Kesenden Jaya Mandiri',
                'kelurahan' => 'Kesenden',
                'alamat' => 'Jl. Kesenden No. 14',
                'rt' => '003', 'rw' => '006',
                'latitude' => -6.7085000, 'longitude' => 108.5575000,
                'luas_lahan' => 14000, 'jumlah_unit' => 120, 'status' => 'subsidi',
                'tahun_pembuatan' => 2020, 'tahun_selesai' => 2022,
                'jumlah_rth' => 3, 'luas_rth' => 1500, 'kdb' => 58, 'kdh' => 31,
                'ada_lahan_pemakaman' => false, 'luas_lahan_pemakaman' => null, 'lokasi_lahan_pemakaman' => null,
                'keterangan' => 'Dekat dengan kawasan pelabuhan dan Jalan Yos Sudarso.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 1400, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 1500, 'satuan' => 'meter', 'kondisi' => 'rusak_ringan'],
                    ['jenis' => 'Jaringan Air Bersih', 'jumlah' => 120, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Sarana Pendidikan', 'jumlah' => 1, 'satuan' => 'unit', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 80, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 40, 'luas_tanah' => 72, 'luas_bangunan' => 36],
                ],
            ],
            [
                'nama' => 'Perumahan Argasunya Green Park',
                'pengembang' => 'PT Argasunya Hijau Lestari',
                'kelurahan' => 'Argasunya',
                'alamat' => 'Jl. Argasunya Raya No. 33',
                'rt' => '006', 'rw' => '002',
                'latitude' => -6.7650000, 'longitude' => 108.5330000,
                'luas_lahan' => 26000, 'jumlah_unit' => 180, 'status' => 'subsidi',
                'tahun_pembuatan' => 2022, 'tahun_selesai' => 2024,
                'jumlah_rth' => 5, 'luas_rth' => 5200, 'kdb' => 52, 'kdh' => 42,
                'ada_lahan_pemakaman' => true, 'luas_lahan_pemakaman' => 2000, 'lokasi_lahan_pemakaman' => 'Blok F, sisi barat perumahan',
                'keterangan' => 'Konsep green park dengan RTH luas dan penanaman pohon pelindung.',
                'psu' => [
                    ['jenis' => 'Jalan Lingkungan', 'jumlah' => 2800, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Saluran Drainase', 'jumlah' => 3000, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Sarana Olahraga', 'jumlah' => 1, 'satuan' => 'unit', 'kondisi' => 'baik'],
                    ['jenis' => 'Tempat Persampahan', 'jumlah' => 8, 'satuan' => 'unit', 'kondisi' => 'baik'],
                ],
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 100, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 60, 'luas_tanah' => 72, 'luas_bangunan' => 36],
                    ['tipe' => 'Tipe 45', 'jumlah_unit' => 20, 'luas_tanah' => 90, 'luas_bangunan' => 45],
                ],
            ],
        ];

        foreach ($contoh as $data) {
            $kelurahan = Kelurahan::where('nama', $data['kelurahan'])->first();
            if (! $kelurahan) {
                continue;
            }

            $psu = $data['psu'];
            $tipe = $data['tipe_units'];
            unset($data['psu'], $data['tipe_units'], $data['kelurahan']);

            $perumahan = Perumahan::updateOrCreate(
                ['nama' => $data['nama']],
                $data + ['kelurahan_id' => $kelurahan->id],
            );

            $perumahan->psu()->delete();
            foreach ($psu as $row) {
                $perumahan->psu()->create($row + ['keterangan' => null]);
            }

            $perumahan->tipeUnits()->delete();
            foreach ($tipe as $row) {
                $perumahan->tipeUnits()->create($row + ['keterangan' => null]);
            }
        }
    }
}
