<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaporanRequest;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\PsuPerumahan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $data = $request->dataLaporan();

        $laporan = DB::transaction(function () use ($request, $data): Laporan {
            $laporan = Laporan::create($data + [
                'kode' => Laporan::buatKode($data['jenis']),
                'status' => 'baru',
            ]);

            foreach ((array) $request->file('foto', []) as $foto) {
                if ($foto && $foto->isValid()) {
                    $laporan->fotos()->create([
                        'path' => $foto->store('laporan', 'public'),
                    ]);
                }
            }

            return $laporan;
        });

        return redirect()
            ->route('lapor.create')
            ->with('laporan_kode', $laporan->kode)
            ->with('laporan_jenis', $laporan->label_jenis)
            ->with('success', 'Laporan berhasil dikirim. Simpan nomor tiket berikut untuk memantau penanganan.');
    }
}
