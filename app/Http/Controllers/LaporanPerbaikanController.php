<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Support\FotoBersih;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Penanganan foto hasil perbaikan oleh petugas/admin pada modul Laporan Warga.
 *
 * Foto perbaikan dibedakan dari foto bukti warga lewat kolom `tipe` —
 * rute hapus sengaja menolak foto bertipe 'laporan' agar bukti pelapor
 * tidak dapat dihapus dari sini (mencegah IDOR / salah hapus).
 */
class LaporanPerbaikanController extends Controller
{
    /**
     * Batas total foto hasil perbaikan per laporan.
     */
    private const MAKS_FOTO = 10;

    public function store(Request $request, Laporan $laporan): RedirectResponse
    {
        if ($laporan->status === 'ditolak') {
            return back()->with('error', 'Laporan berstatus ditolak — tidak bisa menambah foto hasil perbaikan.');
        }

        $data = $request->validate([
            'foto_perbaikan' => ['required', 'array', 'min:1', 'max:5'],
            'foto_perbaikan.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'foto_perbaikan.required' => 'Pilih minimal 1 foto hasil perbaikan.',
            'foto_perbaikan.min' => 'Pilih minimal 1 foto hasil perbaikan.',
            'foto_perbaikan.max' => 'Unggahan maksimal 5 foto sekali proses.',
            'foto_perbaikan.*.image' => 'Lampiran harus berupa gambar.',
            'foto_perbaikan.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto_perbaikan.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
            'foto_perbaikan.*.dimensions' => 'Dimensi foto terlalu besar (maksimal 6000 × 6000 piksel).',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ]);

        $existing = $laporan->fotosPerbaikan()->count();
        if ($existing + count($data['foto_perbaikan']) > self::MAKS_FOTO) {
            return back()->with(
                'error',
                'Foto hasil perbaikan untuk laporan ini maksimal '.self::MAKS_FOTO.' — saat ini sudah '.$existing.' foto.'
            );
        }

        // Re-encode foto di luar transaksi (membuang EXIF & memperkecil),
        // baris DB hanya ditulis setelah seluruh file berhasil tersimpan;
        // bila ada yang gagal, file yatim ikut dibersihkan.
        $paths = [];

        try {
            foreach ($data['foto_perbaikan'] as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = FotoBersih::simpan($file, 'laporan/perbaikan');
                }
            }

            DB::transaction(function () use ($laporan, $data, $paths): void {
                foreach ($paths as $path) {
                    $laporan->fotosPerbaikan()->create([
                        'path' => $path,
                        'tipe' => LaporanFoto::TIPE_PERBAIKAN,
                        'keterangan' => $data['keterangan'] ?? null,
                        'diunggah_oleh' => auth()->id(),
                    ]);
                }
            });
        } catch (Throwable $e) {
            if ($paths !== []) {
                Storage::disk('public')->delete($paths);
            }

            throw $e;
        }

        return back()->with('success', count($paths).' foto hasil perbaikan berhasil ditambahkan ke laporan '.$laporan->kode.'.');
    }

    public function destroy(Laporan $laporan, LaporanFoto $foto): RedirectResponse
    {
        // IDOR guard: foto wajib milik laporan pada rute & bertipe 'perbaikan' —
        // foto bukti warga dilindungi dari penghapusan lewat rute ini.
        if ($foto->laporan_id !== $laporan->id || $foto->tipe !== LaporanFoto::TIPE_PERBAIKAN) {
            abort(404);
        }

        Storage::disk('public')->delete($foto->path);
        $foto->delete();

        return back()->with('success', 'Satu foto hasil perbaikan dihapus dari laporan '.$laporan->kode.'.');
    }
}
