<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Perumahan;
use App\Services\DashboardStatistikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LaporanAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'jenis', 'status', 'tingkat', 'perumahan_id']);

        $query = Laporan::with(['perumahan.kelurahan.kecamatan', 'psu', 'kelurahan.kecamatan', 'fotosLaporan'])->filter($filters);

        $ringkasan = [
            'total' => (clone $query)->count(),
            'baru' => (clone $query)->where('status', 'baru')->count(),
            'diproses' => (clone $query)->where('status', 'diproses')->count(),
            'selesai' => (clone $query)->where('status', 'selesai')->count(),
            'ditolak' => (clone $query)->where('status', 'ditolak')->count(),
        ];

        $laporans = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

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
        $data = $request->validate([
            'status' => ['required', 'in:baru,diproses,selesai,ditolak'],
            'catatan_petugas' => ['nullable', 'string', 'max:2000'],
            'deskripsi_aksi_selesai' => ['nullable', 'string', 'max:2000'],
        ], [
            'status.required' => 'Status penanganan wajib dipilih.',
        ]);

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

        return redirect()->route('laporan.show', $laporan)
            ->with('success', 'Penanganan laporan '.$laporan->kode.' berhasil diperbarui.');
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

    public function destroy(Laporan $laporan): RedirectResponse
    {
        // Hapus semua foto bukti dari disk storage dulu, baru baris database.
        foreach ($laporan->fotos as $foto) {
            if ($foto->path && Storage::disk('public')->exists($foto->path)) {
                Storage::disk('public')->delete($foto->path);
            }
        }

        $kode = $laporan->kode;
        $laporan->delete();

        // Statistik dashboard & badge sidebar tergantung data laporan → reset cache.
        Cache::forget(Laporan::CACHE_BAIK_BARU);
        Cache::forget(DashboardStatistikService::CACHE_KEY);

        return redirect()->route('laporan.index')
            ->with('success', 'Laporan '.$kode.' beserta seluruh foto buktinya dan data penanganannya telah dihapus permanen.');
    }
}
