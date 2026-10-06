<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laporan extends Model
{
    public const JENIS_KERUSAKAN = 'kerusakan';
    public const JENIS_PENGADUAN = 'pengaduan';
    public const JENIS_BANGUNAN_LIAR = 'bangunan_liar';

    public const JENIS = [
        self::JENIS_KERUSAKAN => 'Kerusakan Sarana-Prasarana',
        self::JENIS_PENGADUAN => 'Pengaduan',
        self::JENIS_BANGUNAN_LIAR => 'Bangunan Liar',
    ];

    /**
     * Prefix nomor tiket per jenis laporan: LPR-, PGN-, BLR-.
     */
    public const PREFIX_KODE = [
        self::JENIS_KERUSAKAN => 'LPR',
        self::JENIS_PENGADUAN => 'PGN',
        self::JENIS_BANGUNAN_LIAR => 'BLR',
    ];

    public const STATUS = [
        'baru' => 'Baru',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    public const TINGKAT = [
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
    ];

    protected $fillable = [
        'kode', 'jenis', 'perumahan_id', 'kelurahan_id', 'psu_perumahan_id',
        'lokasi_teks',
        'nama_pelapor', 'kontak_pelapor',
        'judul', 'deskripsi', 'tingkat',
        'tanggal_kejadian',
        'persentase_kerusakan', 'panjang_kerusakan',
        'status', 'catatan_petugas', 'ditangani_oleh', 'ditangani_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kejadian' => 'date',
            'persentase_kerusakan' => 'decimal:2',
            'panjang_kerusakan' => 'decimal:2',
            'ditangani_pada' => 'datetime',
        ];
    }

    public function perumahan(): BelongsTo
    {
        return $this->belongsTo(Perumahan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function psu(): BelongsTo
    {
        return $this->belongsTo(PsuPerumahan::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(LaporanFoto::class);
    }

    public function penangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, $keyword) {
                $q->where(function (Builder $sub) use ($keyword) {
                    $sub->where('kode', 'like', "%{$keyword}%")
                        ->orWhere('judul', 'like', "%{$keyword}%")
                        ->orWhere('nama_pelapor', 'like', "%{$keyword}%")
                        ->orWhere('deskripsi', 'like', "%{$keyword}%")
                        ->orWhere('lokasi_teks', 'like', "%{$keyword}%")
                        ->orWhereHas('perumahan', fn (Builder $p) => $p->where('nama', 'like', "%{$keyword}%"));
                });
            })
            ->when($filters['jenis'] ?? null, fn (Builder $q, $jenis) => $q->where('jenis', $jenis))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['tingkat'] ?? null, fn (Builder $q, $tingkat) => $q->where('tingkat', $tingkat))
            ->when($filters['perumahan_id'] ?? null, fn (Builder $q, $id) => $q->where('perumahan_id', $id));
    }

    /**
     * Nomor tiket unik per jenis, mis. LPR-20261005-7K3M / PGN-20261006-A1B2 / BLR-20261006-C3D4.
     */
    public static function buatKode(string $jenis = self::JENIS_KERUSAKAN): string
    {
        do {
            $kode = sprintf(
                '%s-%s-%s',
                self::PREFIX_KODE[$jenis] ?? 'LPR',
                now()->format('Ymd'),
                strtoupper(substr(bin2hex(random_bytes(4)), 0, 4)),
            );
        } while (self::where('kode', $kode)->exists());

        return $kode;
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getBadgeStatusAttribute(): string
    {
        return match ($this->status) {
            'baru' => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            'diproses' => 'bg-sky-100 text-sky-700 ring-sky-600/20',
            'selesai' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
            'ditolak' => 'bg-rose-100 text-rose-700 ring-rose-600/20',
            default => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        };
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function getBadgeJenisAttribute(): string
    {
        return match ($this->jenis) {
            self::JENIS_KERUSAKAN => 'bg-indigo-100 text-indigo-700 ring-indigo-600/20',
            self::JENIS_PENGADUAN => 'bg-violet-100 text-violet-700 ring-violet-600/20',
            self::JENIS_BANGUNAN_LIAR => 'bg-orange-100 text-orange-700 ring-orange-600/20',
            default => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        };
    }

    public function getLabelTingkatAttribute(): ?string
    {
        return $this->tingkat !== null ? (self::TINGKAT[$this->tingkat] ?? $this->tingkat) : null;
    }

    public function getBadgeTingkatAttribute(): string
    {
        return match ($this->tingkat) {
            'rusak_berat' => 'bg-rose-100 text-rose-700 ring-rose-600/20',
            'rusak_ringan' => 'bg-amber-100 text-amber-700 ring-amber-600/20',
            default => 'bg-slate-100 text-slate-700 ring-slate-600/20',
        };
    }

    public function getLabelTanggalKejadianAttribute(): string
    {
        return $this->tanggal_kejadian?->translatedFormat('d F Y') ?? '-';
    }

    /**
     * Lokasi ringkas: nama perumahan, atau kelurahan-kecamatan untuk laporan
     * di luar perumahan (bangunan liar), dengan teks lokasi bila diisi.
     */
    public function getLokasiRingkasAttribute(): string
    {
        if ($this->perumahan_id && $this->perumahan) {
            return $this->lokasi_teks
                ? $this->perumahan->nama.' — '.$this->lokasi_teks
                : $this->perumahan->nama;
        }

        if ($this->kelurahan_id && $this->kelurahan) {
            $wilayah = 'Kel. '.$this->kelurahan->nama.
                ', Kec. '.($this->kelurahan->kecamatan->nama ?? '-');

            return $this->lokasi_teks ? $wilayah.' — '.$this->lokasi_teks : $wilayah;
        }

        return $this->lokasi_teks ?: '-';
    }

    /**
     * Formatter desimal ringkas: "45,50" -> "45,5" dan "45,00" -> "45".
     */
    private function formatDesimal(mixed $nilai): string
    {
        return rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',');
    }

    public function getLabelPersentaseKerusakanAttribute(): string
    {
        return $this->persentase_kerusakan !== null
            ? $this->formatDesimal($this->persentase_kerusakan).' %'
            : '-';
    }

    public function getLabelPanjangKerusakanAttribute(): string
    {
        return $this->panjang_kerusakan !== null
            ? $this->formatDesimal($this->panjang_kerusakan).' m'
            : '-';
    }

    /**
     * Ukuran kerusakan ringkas untuk kolom tabel, mis. "45,5 % · 12,5 m".
     */
    public function getUkuranRingkasAttribute(): string
    {
        $parts = array_filter([
            $this->persentase_kerusakan !== null ? $this->formatDesimal($this->persentase_kerusakan).' %' : null,
            $this->panjang_kerusakan !== null ? $this->formatDesimal($this->panjang_kerusakan).' m' : null,
        ]);

        return $parts ? implode(' · ', $parts) : '-';
    }

    /**
     * Tautan WhatsApp bila kontak pelapor berupa nomor telepon.
     * Normalisasi: 08xxx / 8xxx -> 628xxx agar valid untuk wa.me.
     */
    public function getKontakWaAttribute(): ?string
    {
        if (preg_match('/^[\d\s+\-().]+$/', $this->kontak_pelapor) !== 1) {
            return null;
        }

        $digit = preg_replace('/\D/', '', $this->kontak_pelapor);

        if (strlen($digit) < 8) {
            return null;
        }

        return match (true) {
            str_starts_with($digit, '62') => $digit,
            str_starts_with($digit, '0') => '62'.substr($digit, 1),
            str_starts_with($digit, '8') => '62'.$digit,
            default => $digit,
        };
    }
}
