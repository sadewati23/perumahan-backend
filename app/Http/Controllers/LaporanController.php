<?php

namespace App\Http\Controllers;

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
     * Simpan laporan baru sesuai jenis (validasi kondisional per jenis)
     * beserta foto bukti (maks. 3 foto).
     */
    public function store(Request $request): RedirectResponse
    {
        $jenis = $request->input('jenis', Laporan::JENIS_KERUSAKAN);

        if (! array_key_exists($jenis, Laporan::JENIS)) {
            $jenis = Laporan::JENIS_KERUSAKAN;
        }

        // Aturan dasar yang berlaku untuk semua jenis laporan.
        $aturan = [
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(Laporan::JENIS))],
            'nama_pelapor' => ['required', 'string', 'max:100'],
            'kontak_pelapor' => ['required', 'string', 'min:6', 'max:100'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'foto' => ['nullable', 'array', 'max:3'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        // Bangunan liar wajib minimal 1 foto; jenis lain foto opsional.
        if ($jenis === Laporan::JENIS_BANGUNAN_LIAR) {
            $aturan['foto'] = ['required', 'array', 'min:1', 'max:3'];
        }

        $pesan = [
            'jenis.in' => 'Jenis laporan tidak valid.',
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'kontak_pelapor.required' => 'Kontak (HP/WA) wajib diisi agar petugas dapat menghubungi Anda.',
            'kontak_pelapor.min' => 'Kontak terlalu pendek, isi nomor HP/WA atau email yang valid.',
            'judul.required' => 'Judul laporan wajib diisi.',
            'deskripsi.required' => 'Uraian wajib diisi.',
            'foto.max' => 'Foto bukti maksimal 3 file.',
            'foto.required' => 'Laporan bangunan liar wajib melampirkan minimal 1 foto.',
            'foto.min' => 'Laporan bangunan liar wajib melampirkan minimal 1 foto.',
            'foto.*.image' => 'Lampiran harus berupa gambar.',
            'foto.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
        ];

        if ($jenis === Laporan::JENIS_BANGUNAN_LIAR) {
            // Bangunan liar: perumahan opsional, kelurahan wajib, plus lokasi teks & tanggal kejadian.
            $aturan += [
                'perumahan_id' => ['nullable', 'exists:perumahans,id'],
                'kelurahan_id' => ['required', 'exists:kelurahans,id'],
                'lokasi_teks' => ['required', 'string', 'max:255'],
                'tanggal_kejadian' => ['nullable', 'date', 'before_or_equal:today'],
            ];

            $pesan += [
                'kelurahan_id.required' => 'Kelurahan/kecamatan lokasi wajib dipilih.',
                'lokasi_teks.required' => 'Lokasi kejadian (nama jalan/gang/titik) wajib diisi.',
                'tanggal_kejadian.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
            ];
        } elseif ($jenis === Laporan::JENIS_PENGADUAN) {
            // Pengaduan: teks saja, foto opsional, tanpa perlu lokasi spesifik.
            $aturan += [
                'perumahan_id' => ['nullable', 'exists:perumahans,id'],
                'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
                'lokasi_teks' => ['nullable', 'string', 'max:255'],
            ];
        } else {
            // Kerusakan: perumahan wajib + rincian tingkat & ukuran kerusakan.
            $aturan += [
                'perumahan_id' => ['required', 'exists:perumahans,id'],
                'psu_perumahan_id' => ['nullable', 'exists:psu_perumahans,id'],
                'kelurahan_id' => ['nullable', 'exists:kelurahans,id'],
                'lokasi_teks' => ['nullable', 'string', 'max:255'],
                'tingkat' => ['required', 'in:rusak_ringan,rusak_berat'],
                'persentase_kerusakan' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'panjang_kerusakan' => ['nullable', 'numeric', 'min:0'],
            ];

            $pesan += [
                'perumahan_id.required' => 'Perumahan wajib dipilih.',
                'tingkat.required' => 'Tingkat kerusakan wajib dipilih.',
                'persentase_kerusakan.min' => 'Persentase kerusakan tidak boleh negatif.',
                'persentase_kerusakan.max' => 'Persentase kerusakan maksimal 100.',
                'panjang_kerusakan.min' => 'Panjang kerusakan tidak boleh negatif.',
            ];
        }

        $data = $request->validate($aturan, $pesan);

        // Pastikan PSU yang dipilih benar-benar milik perumahan terpilih.
        if (! empty($data['psu_perumahan_id'])) {
            $psuValid = PsuPerumahan::whereKey($data['psu_perumahan_id'])
                ->where('perumahan_id', $data['perumahan_id'])
                ->exists();

            if (! $psuValid) {
                return back()->withInput()->withErrors([
                    'psu_perumahan_id' => 'Sarana yang dipilih tidak sesuai dengan perumahan yang dilaporkan.',
                ]);
            }
        }

        // Bangunan liar yang memilih perumahan: pakai kelurahan milik perumahan tersebut.
        if ($jenis === Laporan::JENIS_BANGUNAN_LIAR && ! empty($data['perumahan_id'])) {
            $kelurahanPerumahan = Perumahan::query()->find($data['perumahan_id'])?->kelurahan_id;

            if ($kelurahanPerumahan) {
                $data['kelurahan_id'] = $kelurahanPerumahan;
            }
        }

        // Normalisasi field yang tidak relevan per jenis sebelum disimpan.
        $data = match ($jenis) {
            Laporan::JENIS_PENGADUAN => $data + [
                'tingkat' => null,
                'psu_perumahan_id' => null,
            ],
            Laporan::JENIS_BANGUNAN_LIAR => $data + ['tingkat' => null, 'psu_perumahan_id' => null],
            default => $data,
        };

        $laporan = DB::transaction(function () use ($request, $data, $jenis): Laporan {
            $laporan = Laporan::create($data + [
                'kode' => Laporan::buatKode($jenis),
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
