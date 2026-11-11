<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PerumahanImportTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function buatWilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
    }

    private function csv(string $isi, string $nama = 'perumahan.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, $isi);
    }

    private function csvValid(): string
    {
        return "Nama Perumahan,Pengembang,Kecamatan,Kelurahan,Alamat,RT,RW,Latitude,Longitude,Luas Lahan,Jumlah Unit,Status\n"
            ."Perumahan Amanah,PT A,Coblong,Dago,Jl. Uji 1,001,002,-6.7320,108.5523,12000,50,campuran\n"
            ."Perumahan Sentosa,PT B,Coblong,Dago,Jl. Uji 2,,,-6.7450,108.5600,8000,30,Subsidi\n";
    }

    public function test_tamu_tidak_bisa_membuka_halaman_impor(): void
    {
        $this->get(route('perumahan.import'))->assertRedirect(route('login'));
    }

    public function test_petugas_bisa_membuka_halaman_impor(): void
    {
        $this->actingAs($this->petugas())
            ->get(route('perumahan.import'))
            ->assertOk()
            ->assertSee('Unggah file data perumahan');
    }

    public function test_template_csv_bisa_diunduh(): void
    {
        $response = $this->actingAs($this->petugas())
            ->get(route('perumahan.import.template-csv'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // StreamedResponse: isi body dibaca lewat streamedContent(), bukan getContent().
        $this->assertStringContainsString('Nama Perumahan', $response->streamedContent());
    }

    public function test_template_xlsx_bisa_diunduh(): void
    {
        $response = $this->actingAs($this->petugas())
            ->get(route('perumahan.import.template-xlsx'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Simpan sementara lalu baca isinya via PhpSpreadsheet untuk memastikan sah.
        $isi = $response->streamedContent();
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($path, $isi);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $inline = $spreadsheet->getActiveSheet()->toArray()[0];

        @unlink($path);

        $this->assertSame('Nama Perumahan', $inline[0]);
    }

    public function test_impor_csv_berhasil_menyimpan_data(): void
    {
        $this->buatWilayah();

        $this->withoutExceptionHandling(); // DEBUG sementara

        $response = $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($this->csvValid())]);

        $response->assertRedirect(route('perumahan.index'));
        $this->assertSame(2, Perumahan::count());

        $pertama = Perumahan::orderBy('id')->first();
        $this->assertSame('Perumahan Amanah', $pertama->nama);
        $this->assertSame('campuran', $pertama->status);
        $this->assertSame(12000.0, (float) $pertama->luas_lahan);
        $this->assertSame(-6.7320, (float) $pertama->latitude);

        $kedua = Perumahan::orderBy('id')->skip(1)->first();
        $this->assertSame('subsidi', $kedua->status); // label "Subsidi" ke kode
        $this->assertSame(30, $kedua->jumlah_unit);
    }

    public function test_impor_csv_memakai_delimiter_titik_koma(): void
    {
        $this->buatWilayah();

        $isi = "Nama Perumahan;Kecamatan;Kelurahan;Luas Lahan;Jumlah Unit\n"
            ."Perumahan Titik Koma;Coblong;Dago;5000;12\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi, 'titik-koma.csv')]);

        $this->assertSame(1, Perumahan::count());
        $this->assertSame('Perumahan Titik Koma', Perumahan::first()->nama);
    }

    public function test_impor_xlsx_berhasil_menyimpan_data(): void
    {
        $this->buatWilayah();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([[
            'Nama Perumahan', 'Kecamatan', 'Kelurahan', 'Alamat', 'Luas Lahan', 'Jumlah Unit', 'Status',
        ]], null, 'A1');
        $sheet->fromArray([[
            'Perumahan Excelku', 'Coblong', 'Dago', 'Jl. Excel 9', '9500', '25', 'Komersil',
        ]], null, 'A2');
        $sheet->fromArray([[
            'Perumahan Excel Two', 'Coblong', 'Dago', 'Jl. Excel 10', '7000', '15', 'Campuran',
        ]], null, 'A3');

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $file = new UploadedFile($path, 'perumahan.xlsx', null, null, true);

        try {
            $this->actingAs($this->petugas())
                ->post(route('perumahan.import.simpan'), ['file' => $file]);

            $this->assertSame(2, Perumahan::count());
            $this->assertSame('Perumahan Excelku', Perumahan::first()->nama);
            $this->assertSame('Perumahan Excel Two', Perumahan::orderByDesc('id')->first()->nama);
        } finally {
            @unlink($path);
        }
    }

    public function test_baris_bermasalah_membatalkan_seluruh_impor_dan_menampilkan_pesan(): void
    {
        $this->buatWilayah();

        // Baris 2 valid, baris 3 tanpa kelurahan, baris 4 luas lahan kosong.
        $isi = "Nama Perumahan,Kecamatan,Kelurahan,Luas Lahan,Jumlah Unit\n"
            ."Perumahan Bagus,Coblong,Dago,5000,12\n"
            ."Perumahan Munurut,Coblong,,3000,5\n"
            ."Perumahan Tidak Lengkap,Coblong,Dago,,7\n";

        $response = $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $this->assertSame(0, Perumahan::count());

        $kesalahan = session('errors')->getBag('default')->get('file');
        $satuTeks = implode(' ', collect($kesalahan)->all());

        $this->assertStringContainsString('Baris 3', $satuTeks);
        $this->assertStringContainsString('Baris 4', $satuTeks);
        $this->assertStringContainsString('TIDAK disimpan', $satuTeks);
    }

    public function test_kelurahan_dgn_nama_ganda_wajib_mencantumkan_kecamatan(): void
    {
        $c1 = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);
        $c2 = Kecamatan::create(['nama' => 'Cidadap', 'kabupaten_kota' => 'Kota Bandung']);
        Kelurahan::create(['kecamatan_id' => $c1->id, 'nama' => 'Dago']);
        Kelurahan::create(['kecamatan_id' => $c2->id, 'nama' => 'Dago']);

        $isi = "Nama Perumahan,Kelurahan,Luas Lahan,Jumlah Unit\n"
            ."Perumahan Ambigu,Dago,5000,10\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $this->assertSame(0, Perumahan::count());

        $satuTeks = implode(' ', collect(session('errors')->getBag('default')->get('file'))->all());
        $this->assertStringContainsString('beberapa kecamatan', $satuTeks);
    }

    public function test_kelurahan_ganda_berhasil_dengan_kolom_kecamatan(): void
    {
        $c1 = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);
        $c2 = Kecamatan::create(['nama' => 'Cidadap', 'kabupaten_kota' => 'Kota Bandung']);
        Kelurahan::create(['kecamatan_id' => $c1->id, 'nama' => 'Dago']);
        $dagoCidadap = Kelurahan::create(['kecamatan_id' => $c2->id, 'nama' => 'Dago']);

        $isi = "Nama Perumahan,Kecamatan,Kelurahan,Luas Lahan,Jumlah Unit\n"
            ."Perumahan Jelas,Cidadap,Dago,5000,10\n";

        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), ['file' => $this->csv($isi)]);

        $this->assertSame(1, Perumahan::count());
        $this->assertSame($dagoCidadap->id, Perumahan::first()->kelurahan_id);
    }

    public function test_file_yang_tidak_bisa_dibaca_ditolak_dengan_pesan_jelas(): void
    {
        $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), [
                'file' => $this->csv("\x89PNG\r\n\x1A\nBUKAN_SPREADSHEET", 'perumahan.csv'),
            ]);

        $satuTeks = implode(' ', collect(session('errors')->getBag('default')->get('file'))->all());
        $this->assertStringContainsString('tidak dapat dibaca', $satuTeks);
        $this->assertSame(0, Perumahan::count());
    }

    public function test_file_salah_format_ditolak_validasi_request(): void
    {
        $response = $this->actingAs($this->petugas())
            ->post(route('perumahan.import.simpan'), [
                'file' => UploadedFile::fake()->create('perumahan.exe', 1),
            ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, Perumahan::count());
    }
}
