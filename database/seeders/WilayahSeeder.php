<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    /**
     * Data wilayah Kota Cirebon: 5 kecamatan dan 22 kelurahan.
     * Sumber: kode wilayah Kemendagri (32.74.xx) dan kode pos Kota Cirebon.
     */
    public function run(): void
    {
        $data = [
            [
                'kode' => '32.74.01',
                'nama' => 'Kejaksan',
                'kelurahan' => [
                    ['nama' => 'Kesenden', 'kode_pos' => '45121'],
                    ['nama' => 'Sukapura', 'kode_pos' => '45122'],
                    ['nama' => 'Kejaksan', 'kode_pos' => '45123'],
                    ['nama' => 'Kebonbaru', 'kode_pos' => '45124'],
                ],
            ],
            [
                'kode' => '32.74.02',
                'nama' => 'Lemahwungkuk',
                'kelurahan' => [
                    ['nama' => 'Lemahwungkuk', 'kode_pos' => '45111'],
                    ['nama' => 'Panjunan', 'kode_pos' => '45112'],
                    ['nama' => 'Pegambiran', 'kode_pos' => '45113'],
                    ['nama' => 'Kasepuhan', 'kode_pos' => '45114'],
                ],
            ],
            [
                'kode' => '32.74.03',
                'nama' => 'Harjamukti',
                'kelurahan' => [
                    ['nama' => 'Larangan', 'kode_pos' => '45141'],
                    ['nama' => 'Kecapi', 'kode_pos' => '45142'],
                    ['nama' => 'Harjamukti', 'kode_pos' => '45143'],
                    ['nama' => 'Kalijaga', 'kode_pos' => '45144'],
                    ['nama' => 'Argasunya', 'kode_pos' => '45145'],
                ],
            ],
            [
                'kode' => '32.74.04',
                'nama' => 'Pekalipan',
                'kelurahan' => [
                    ['nama' => 'Jagasatru', 'kode_pos' => '45115'],
                    ['nama' => 'Pulasaren', 'kode_pos' => '45116'],
                    ['nama' => 'Pekalipan', 'kode_pos' => '45117'],
                    ['nama' => 'Pekalangan', 'kode_pos' => '45118'],
                ],
            ],
            [
                'kode' => '32.74.05',
                'nama' => 'Kesambi',
                'kelurahan' => [
                    ['nama' => 'Pekiringan', 'kode_pos' => '45131'],
                    ['nama' => 'Sunyaragi', 'kode_pos' => '45132'],
                    ['nama' => 'Drajat', 'kode_pos' => '45133'],
                    ['nama' => 'Kesambi', 'kode_pos' => '45134'],
                    ['nama' => 'Karyamulya', 'kode_pos' => '45135'],
                ],
            ],
        ];

        foreach ($data as $item) {
            $kecamatan = Kecamatan::updateOrCreate(
                ['nama' => $item['nama']],
                ['kode' => $item['kode'], 'kabupaten_kota' => 'Kota Cirebon'],
            );

            foreach ($item['kelurahan'] as $kelurahan) {
                Kelurahan::updateOrCreate(
                    ['kecamatan_id' => $kecamatan->id, 'nama' => $kelurahan['nama']],
                    ['kode_pos' => $kelurahan['kode_pos']],
                );
            }
        }
    }
}
