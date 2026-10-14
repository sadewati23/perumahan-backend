<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaporanRequest;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\PsuPerumahan;
use App\Support\FotoBersih;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Form lapor publik (tanpa login) untuk tiga jenis laporan:
     * kerusakan sarana-prasarana, pengaduan, dan bangunan liar.
     */
    public function create(Request $request): View
    {
        $perumahans = Perumahan::with(['kelurahan.kecamatan', 'psu'])
            ->orderBy('nama')
            ->get()
            ->map(fn (Perumahan $p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'wilayah' => 'Kel. '.($p->kelurahan->nama ?? '-').', Kec. '.($p->kelurahan->kecamatan->nama ?? '-'),
                'psu' => $p->psu->map(fn (PsuPerumahan $s) => [
                    'id' => $s->id,
                    'jenis' => $s->jenis,
                ])->values(),
            ]);

        // Wilayah untuk laporan bangunan liar di luar area perumahan.
        $kelurahans = Kelurahan::with('kecamatan')
            ->orderBy('nama')
            ->get()
            ->map(fn (Kelurahan $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'kecamatan' => $k->kecamatan->nama ?? '-',
            ]);

        $perumahanTerpilih = (int) $request->query('perumahan', 0) ?: null;
        $jenisTerpilih = $request->query('jenis');

        if (! array_key_exists($jenisTerpilih, Laporan::JENIS)) {
            $jenisTerpilih = null;
        }

        return view('lapor.form', compact('perumahans', 'kelurahans', 'perumahanTerpilih', 'jenisTerpilih'));
    }

    /**
     * Simpan laporan baru sesuai jenis (validasi kondisional per jenis dipindah
     * ke StoreLaporanRequest) beserta foto bukti (maks. 3 foto).
     */
    public function store(StoreLaporanRequest $request): RedirectResponse
    {
        // Honeypot: field palsu di luar layar hanya diisi bot. Balas layanan
        // "sukses" tanpa menyimpan apa pun agar bot tidak tahu bahwa ia lolos.
        if ($request->filled('jawaban_cadangan')) {
            return redirect()->route('lapor.create')->with('success', 'Laporan berhasil dikirim.');
        }

        $data = $request->dataLaporan();

        // Re-encode foto di LUAR transaksi: operasi berat (decode/encode GD)
        // tidak dipanjangkan di dalam transaksi DB. Path sudah tersimpan di
        // disk sejak awal, sehingga bila transaksi gagal tinggal dibersihkan.
        $paths = [];

        try {
            foreach ((array) $request->file('foto', []) as $foto) {
                if ($foto && $foto->isValid()) {
                    $paths[] = FotoBersih::simpan($foto);
                }
            }

            $laporan = DB::transaction(function () use ($data, $paths): Laporan {
                $laporan = Laporan::create($data + [
                    'kode' => Laporan::buatKode($data['jenis']),
                    'status' => 'baru',
                ]);

                foreach ($paths as $path) {
                    $laporan->fotos()->create(['path' => $path]);
                }

                return $laporan;
            });
        } catch (\Throwable $e) {
            // Jangan tinggalkan file yatim di disk bila ada langkah yang gagal.
            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }

            throw $e;
        }

        return redirect()
            ->route('lapor.create')
            ->with('laporan_kode', $laporan->kode)
            ->with('laporan_jenis', $laporan->label_jenis)
            ->with('success', 'Laporan berhasil dikirim. Simpan nomor tiket berikut untuk memantau penanganan.');
    }
}
