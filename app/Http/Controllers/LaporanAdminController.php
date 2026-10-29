<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use App\Support\FilterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LaporanAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = FilterQuery::ambil($request, [
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(Laporan::JENIS))],
            'status' => ['nullable', 'in:'.implode(',', array_keys(Laporan::STATUS))],
            'tingkat' => ['nullable', 'in:'.implode(',', array_keys(Laporan::TINGKAT))],
            'perumahan_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Laporan::with(['perumahan.kelurahan.kecamatan', 'psu', 'kelurahan.kecamatan', 'fotosLaporan'])->filter($filters);

        $ringkasan = [
            'total' => (clone $query)->count(),
            'baru' => (clone $query)->where('status', 'baru')->count(),
            'diproses' => (clone $query)->where('status', 'diproses')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];

        $laporans = $query->orderByDesc('created_at')->paginate(15)->appends($filters);

        $perumahans = Perumahan::orderBy('nama')->get(['id', 'nama']);

        return view('laporan.index', compact('laporans', 'perumahans', 'filters', 'ringkasan'));
    }

    public function show(Laporan $laporan): View
    {
        $laporan->load([
            'perumahan.kelurahan.kecamatan',
            'kelurahan.kecamatan',
            'psu',
            'fotosLaporan',
            'fotosPerbaikan.pengunggah',
            'penangan',
        ]);

        return view('laporan.show', compact('laporan'));
    }

    public function update(Request $request, Laporan $laporan): RedirectResponse
    {
        $statusSebelumnya = $laporan->status;
        $data = $request->validate([
            'status' => ['required', 'in:baru,diproses,selesai,ditolak'],
            'catatan_petugas' => ['nullable', 'string', 'max:2000'],
            'deskripsi_aksi_selesai' => ['nullable', 'string', 'max:2000'],
            'jangan_hapus_otomatis' => ['nullable', 'boolean'],
        ], [
            'status.required' => 'Status penanganan wajib dipilih.',
        ]);

        $data['jangan_hapus_otomatis'] = $request->boolean('jangan_hapus_otomatis');
        $laporan->update($data + [
            'ditangani_oleh' => auth()->id(),
            'ditangani_pada' => now(),
            // Timestamp selesai diunci pada pertama kali ditandai selesai;
            // kembali ke status lain mengosongkannya kembali.
            'selesai_pada' => $data['status'] === 'selesai'
                ? ($laporan->selesai_pada ?? now())
                : null,
        ]);

        // Statistik dashboard & badge sidebar tergantung status laporan → reset cache.
        Cache::forget(Laporan::CACHE_BAIK_BARU);
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        $pesan = 'Penanganan laporan '.$laporan->kode.' berhasil diperbarui.';
        if ($laporan->jatuh_tempo_hapus && $statusSebelumnya !== 'selesai') {
            $pesan .= ' Laporan akan dihapus otomatis pada '.$laporan->jatuh_tempo_hapus->translatedFormat('d F Y, H:i').' WIB ('.config('laporan.hapus_otomatis_hari').' hari setelah ditandai selesai).';
        }
        return redirect()->route('laporan.show', $laporan)->with('success', $pesan);
    }

    /**
     * Halaman cetak satu laporan (standalone, CSS print inline).
     */
    public function cetak(Laporan $laporan): View
    {
        $laporan->load([
            'perumahan.kelurahan.kecamatan',
            'kelurahan.kecamatan',
            'psu',
            'fotosLaporan',
            'fotosPerbaikan.pengunggah',
            'penangan',
        ]);

        return view('laporan.cetak', compact('laporan'));
    }

    /** Unduh satu laporan dalam format HTML yang dapat dibuka Microsoft Word. */
    public function word(Laporan $laporan): Response
    {
        $laporan->load([
            'perumahan.kelurahan.kecamatan',
            'kelurahan.kecamatan',
            'psu',
            'fotosLaporan',
            'fotosPerbaikan.pengunggah',
            'penangan',
        ]);

        $fotosLaporan = $laporan->fotosLaporan->map(fn ($foto) => [
            'foto' => $foto,
            'src' => $this->gambarUntukWord($foto->path),
        ]);
        $fotosPerbaikan = $laporan->fotosPerbaikan->map(fn ($foto) => [
            'foto' => $foto,
            'src' => $this->gambarUntukWord($foto->path),
        ]);

        return response()->view('laporan.word', compact('laporan', 'fotosLaporan', 'fotosPerbaikan'))
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="laporan-'.$laporan->kode.'.doc"');
    }

    /**
     * Halaman cetak rekap daftar laporan mengikuti filter daftar utama.
     */
    public function cetakRekap(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(Laporan::JENIS))],
            'status' => ['nullable', 'in:'.implode(',', array_keys(Laporan::STATUS))],
            'tingkat' => ['nullable', 'in:'.implode(',', array_keys(Laporan::TINGKAT))],
            'perumahan_id' => ['nullable', 'exists:perumahans,id'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ], [
            'sampai.after_or_equal' => 'Tanggal sampai tidak boleh lebih awal dari tanggal dari.',
        ]);

        $batasBaris = 500;
        $query = Laporan::query()
            ->with(['perumahan.kelurahan.kecamatan', 'kelurahan.kecamatan', 'psu', 'penangan'])
            ->filter($filters)
            ->when($filters['dari'] ?? null, fn ($q, $dari) => $q->whereDate('created_at', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn ($q, $sampai) => $q->whereDate('created_at', '<=', $sampai));

        $ringkasan = [
            'total' => (clone $query)->count(),
            'baru' => (clone $query)->where('status', 'baru')->count(),
            'diproses' => (clone $query)->where('status', 'diproses')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];

        $terpotong = $ringkasan['total'] > $batasBaris;
        $laporans = $query->orderByDesc('created_at')->limit($batasBaris)->get();

        return view('laporan.cetak-rekap', compact('laporans', 'filters', 'ringkasan', 'terpotong', 'batasBaris'));
    }

    /** Unduh rekap dalam format HTML yang dapat dibuka Microsoft Word. */
    public function wordRekap(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(Laporan::JENIS))],
            'status' => ['nullable', 'in:'.implode(',', array_keys(Laporan::STATUS))],
            'tingkat' => ['nullable', 'in:'.implode(',', array_keys(Laporan::TINGKAT))],
            'perumahan_id' => ['nullable', 'exists:perumahans,id'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ], [
            'sampai.after_or_equal' => 'Tanggal sampai tidak boleh lebih awal dari tanggal dari.',
        ]);

        $batasBaris = 500;
        $query = Laporan::query()
            ->with(['perumahan.kelurahan.kecamatan', 'kelurahan.kecamatan', 'psu', 'penangan'])
            ->filter($filters)
            ->when($filters['dari'] ?? null, fn ($q, $dari) => $q->whereDate('created_at', '>=', $dari))
            ->when($filters['sampai'] ?? null, fn ($q, $sampai) => $q->whereDate('created_at', '<=', $sampai));

        $ringkasan = [
            'total' => (clone $query)->count(),
            'baru' => (clone $query)->where('status', 'baru')->count(),
            'diproses' => (clone $query)->where('status', 'diproses')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];

        $terpotong = $ringkasan['total'] > $batasBaris;
        $laporans = $query->orderByDesc('created_at')->limit($batasBaris)->get();

        return response()->view('laporan.word-rekap', compact('laporans', 'filters', 'ringkasan', 'terpotong', 'batasBaris'))
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="rekap-laporan.doc"');
    }

    private function gambarUntukWord(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }

    public function destroy(Laporan $laporan): RedirectResponse
    {
        $kode = $laporan->kode;
        $laporan->hapusPermanen();

        // Statistik dashboard & badge sidebar tergantung data laporan → reset cache.
        Cache::forget(Laporan::CACHE_BAIK_BARU);
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('laporan.index')
            ->with('success', 'Laporan '.$kode.' beserta seluruh foto buktinya dan data penanganannya telah dihapus permanen.');
    }
}
