<?php

namespace App\Http\Requests;

use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\PsuPerumahan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Validator;

class StoreLaporanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Laporan dikirim oleh publik tanpa login.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Jenis default: kerusakan saat tidak dikirim. Jenis invalid dibiarkan
        // apa adanya agar rule 'in:' yang menolaknya (test menyandarkan error ke 'jenis').
        $jenis = $this->input('jenis');

        if (blank($jenis)) {
            $this->merge(['jenis' => Laporan::JENIS_KERUSAKAN]);
        }

        // Bangunan liar yang memilih perumahan: kelurahan mengikuti kelurahan
        // milik perumahan tersebut (bukan pilihan bebas pelapor).
        if ($jenis === Laporan::JENIS_BANGUNAN_LIAR && $this->filled('perumahan_id')) {
            $kelurahanPerumahan = Perumahan::query()
                ->find($this->input('perumahan_id'))
                ?->kelurahan_id;

            if ($kelurahanPerumahan) {
                $this->merge(['kelurahan_id' => $kelurahanPerumahan]);
            }
        }
    }

    public function rules(): array
    {
        // Kontak HP/WA tidak lagi diminta (dihapus dari form) — kolom tetap ada di DB untuk data lama.
        $aturan = [
            'jenis' => ['nullable', 'in:'.implode(',', array_keys(Laporan::JENIS))],
            'nama_pelapor' => ['required', 'string', 'max:100'],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:2000'],
            'foto' => ['nullable', 'array', 'max:3'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            // Titik GPS opsional dari pemilih peta: dua-duanya harus terisi bersamaan
            // (dicek di after()) dan berada dalam rentang nilai koordinat dunia.
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        // Proteksi Cloudflare Turnstile aktif hanya bila secret dikonfigurasi;
        // tanpa secret form tetap dapat dipakai (hanya dijaga honeypot + rate limit).
        $aturan['cf-turnstile-response'] = $this->aturanTurnstile();

        // Bangunan liar wajib minimal 1 foto; jenis lain foto opsional.
        if ($this->jenis === Laporan::JENIS_BANGUNAN_LIAR) {
            $aturan['foto'] = ['required', 'array', 'min:1', 'max:3'];
        }

        if ($this->jenis === Laporan::JENIS_BANGUNAN_LIAR) {
            // Bangunan liar: perumahan opsional, kelurahan wajib, plus lokasi teks & tanggal kejadian.
            $aturan += [
                'perumahan_id' => ['nullable', 'exists:perumahans,id'],
                'kelurahan_id' => ['required', 'exists:kelurahans,id'],
                'lokasi_teks' => ['required', 'string', 'max:255'],
                'tanggal_kejadian' => ['nullable', 'date', 'before_or_equal:today'],
            ];
        } elseif ($this->jenis === Laporan::JENIS_PENGADUAN) {
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
        }

        return $aturan;
    }

    public function messages(): array
    {
        return [
            'jenis.in' => 'Jenis laporan tidak valid.',
            'nama_pelapor.required' => 'Nama pelapor wajib diisi.',
            'judul.required' => 'Judul laporan wajib diisi.',
            'deskripsi.required' => 'Uraian wajib diisi.',
            'foto.max' => 'Foto bukti maksimal 3 file.',
            'foto.required' => 'Laporan bangunan liar wajib melampirkan minimal 1 foto.',
            'foto.min' => 'Laporan bangunan liar wajib melampirkan minimal 1 foto.',
            'foto.*.image' => 'Lampiran harus berupa gambar.',
            'foto.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
            'foto.*.dimensions' => 'Dimensi foto terlalu besar (maksimal 6000 × 6000 piksel).',
            'cf-turnstile-response.required' => 'Selesaikan verifikasi keamanan terlebih dahulu.',
            'kelurahan_id.required' => 'Kelurahan/kecamatan lokasi wajib dipilih.',
            'lokasi_teks.required' => 'Lokasi kejadian (nama jalan/gang/titik) wajib diisi.',
            'tanggal_kejadian.before_or_equal' => 'Tanggal kejadian tidak boleh di masa depan.',
            'perumahan_id.required' => 'Perumahan wajib dipilih.',
            'tingkat.required' => 'Tingkat kerusakan wajib dipilih.',
            'persentase_kerusakan.min' => 'Persentase kerusakan tidak boleh negatif.',
            'persentase_kerusakan.max' => 'Persentase kerusakan maksimal 100.',
            'panjang_kerusakan.min' => 'Panjang kerusakan tidak boleh negatif.',
        ];
    }

    /**
     * Validasi bisnis antar-field yang tidak bisa dinyatakan sebagai aturan tunggal.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->perluCekPsu()) {
                    return;
                }

                // Pastikan PSU yang dipilih benar-benar milik perumahan terpilih.
                $psuValid = PsuPerumahan::query()
                    ->whereKey($this->input('psu_perumahan_id'))
                    ->where('perumahan_id', $this->input('perumahan_id'))
                    ->exists();

                if (! $psuValid) {
                    $validator->errors()->add(
                        'psu_perumahan_id',
                        'Sarana yang dipilih tidak sesuai dengan perumahan yang dilaporkan.'
                    );
                }
            },
            function (Validator $validator): void {
                // Titik GPS buatan pasangan: hanya satu yang terisi = data tak lengkap.
                if (filled($this->input('latitude')) !== filled($this->input('longitude'))) {
                    $validator->errors()->add(
                        'latitude',
                        'Titik lokasi tidak lengkap — silakan pilih ulang titiknya di peta.'
                    );
                }
            },
        ];
    }

    /**
     * Cek kepemilikan PSU hanya relevan untuk laporan kerusakan dengan PSU & perumahan terisi.
     */
    private function perluCekPsu(): bool
    {
        return $this->input('jenis') === Laporan::JENIS_KERUSAKAN
            && filled($this->input('psu_perumahan_id'))
            && filled($this->input('perumahan_id'));
    }

    /**
     * Aturan Cloudflare Turnstile: [] bila secret kosong (fitur nonaktif),
     * jika tidak wajib diisi + verifikasi token ke endpoint siteverify.
     */
    private function aturanTurnstile(): array
    {
        $secret = config('services.turnstile.secret');

        if (blank($secret)) {
            return [];
        }

        return [
            'required',
            function (string $attribute, mixed $value, callable $fail): void {
                try {
                    $verifikasi = Http::asForm()->timeout(5)
                        ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                            'secret' => config('services.turnstile.secret'),
                            'response' => (string) $value,
                            'remoteip' => $this->ip(),
                        ])->json();
                } catch (ConnectionException) {
                    $fail('Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba lagi.');

                    return;
                }

                if (! is_array($verifikasi) || ($verifikasi['success'] ?? false) !== true) {
                    $fail('Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba lagi.');
                }
            },
        ];
    }

    /**
     * Data siap simpan: normalisasi field yang tidak relevan per jenis.
     * Field internal antifraud (token Turnstile) tidak boleh tersimpan.
     */
    public function dataLaporan(): array
    {
        $data = Arr::except($this->validated(), ['cf-turnstile-response']);

        return match ($this->validated('jenis')) {
            Laporan::JENIS_PENGADUAN, Laporan::JENIS_BANGUNAN_LIAR => $data + [
                'tingkat' => null,
                'psu_perumahan_id' => null,
            ],
            default => $data,
        };
    }
}
