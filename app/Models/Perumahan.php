<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Support\FilterQuery;

class Perumahan extends Model
{

    public const STATUS = [
        'subsidi' => 'Subsidi',
        'komersil' => 'Komersil',
        'campuran' => 'Campuran',
    ];

    public const WARNA_HEX = [
        'subsidi' => '#10b981',
        'komersil' => '#0284c7',
        'campuran' => '#7c3aed',
    ];

    public static function warnaPerLabel(): array
    {
        return [
            'Subsidi' => self::WARNA_HEX['subsidi'],
            'Komersil' => self::WARNA_HEX['komersil'],
            'Campuran' => self::WARNA_HEX['campuran'],
        ];
    }

    public const JENIS_PSU = [
        'Jalan Lingkungan',
        'Saluran Drainase',
        'Jaringan Air Bersih',
        'Jaringan Listrik',
        'Jaringan Telepon',
        'Penerangan Jalan Umum',
        'Tempat Persampahan',
        'Pos Keamanan / Pos Jaga',
        'Pemadam Kebakaran / Hydrant',
        'Sarana Ibadah',
        'Balai Pertemuan Masyarakat',
        'Sarana Pendidikan',
        'Sarana Kesehatan',
        'Sarana Olahraga',
    ];

    public const SATUAN_PSU = ['unit', 'meter', 'm2', 'set', 'titik', 'buah'];

    public const KONDISI_PSU = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
    ];

    protected $fillable = [
        'nama', 'foto_path', 'pengembang', 'kelurahan_id', 'alamat', 'rt', 'rw',
        'latitude', 'longitude', 'luas_lahan', 'jumlah_unit', 'status',
        'tahun_pembuatan', 'tahun_selesai', 'jumlah_rth', 'luas_rth',
        'kdb', 'kdh', 'ada_lahan_pemakaman', 'luas_lahan_pemakaman',
        'lokasi_lahan_pemakaman', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'luas_lahan' => 'decimal:2',
            'luas_rth' => 'decimal:2',
            'luas_lahan_pemakaman' => 'decimal:2',
            'kdb' => 'decimal:2',
            'kdh' => 'decimal:2',
            'ada_lahan_pemakaman' => 'boolean',
            'tahun_pembuatan' => 'integer',
            'tahun_selesai' => 'integer',
        ];
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function psu(): HasMany
    {
        return $this->hasMany(PsuPerumahan::class);
    }

    public function tipeUnits(): HasMany
    {
        return $this->hasMany(TipeUnit::class);
    }

    /**
     * Laporan warga yang terkait. Dipakai command perumahan:cek-duplikat
     * untuk menghitung dampak sebelum data duplikat dirapikan.
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function (Builder $q, $keyword) {
                $keyword = FilterQuery::likeAman($keyword);
                $q->where(function (Builder $sub) use ($keyword) {
                    $like = "%{$keyword}%";
                    $sub->where('nama', 'like', $like)
                        ->orWhere('pengembang', 'like', $like)
                        ->orWhere('alamat', 'like', $like)
                        ->orWhereHas('kelurahan', fn (Builder $k) => $k->where('nama', 'like', $like))
                        ->orWhereHas('kelurahan.kecamatan', fn (Builder $k) => $k->where('nama', 'like', $like));
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['kecamatan_id'] ?? null, fn (Builder $q, $id) => $q
                ->whereHas('kelurahan', fn (Builder $k) => $k->where('kecamatan_id', $id)))
            ->when($filters['tahun'] ?? null, fn (Builder $q, $tahun) => $q->where('tahun_selesai', $tahun));
    }

    public function getLabelStatusAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->label_status;
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }

    /**
     * Palet warna per status: hijau (subsidi), biru (komersil), ungu (campuran).
     * Dipakai bersama oleh badge, titik penanda, dan aksen kartu agar konsisten.
     */
    public function getStatusThemeAttribute(): array
    {
        return match ($this->status) {
            'subsidi' => [
                'badge' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
                'dot' => 'bg-emerald-500',
                'soft' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
                'text' => 'text-emerald-700',
                'rail' => 'border-emerald-500',
                'rail_left' => 'border-l-emerald-500',
                'rail_top' => 'border-t-emerald-500',
                'gradient' => 'from-emerald-50 via-white to-white',
            ],
            'komersil' => [
                'badge' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
                'dot' => 'bg-sky-500',
                'soft' => 'bg-sky-50 text-sky-800 ring-sky-200',
                'text' => 'text-sky-700',
                'rail' => 'border-sky-500',
                'rail_left' => 'border-l-sky-500',
                'rail_top' => 'border-t-sky-500',
                'gradient' => 'from-sky-50 via-white to-white',
            ],
            'campuran' => [
                'badge' => 'bg-violet-100 text-violet-800 ring-violet-600/20',
                'dot' => 'bg-violet-500',
                'soft' => 'bg-violet-50 text-violet-800 ring-violet-200',
                'text' => 'text-violet-700',
                'rail' => 'border-violet-500',
                'rail_left' => 'border-l-violet-500',
                'rail_top' => 'border-t-violet-500',
                'gradient' => 'from-violet-50 via-white to-white',
            ],
            default => [
                'badge' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
                'dot' => 'bg-slate-500',
                'soft' => 'bg-slate-50 text-slate-700 ring-slate-200',
                'text' => 'text-slate-600',
                'rail' => 'border-slate-400',
                'rail_left' => 'border-l-slate-400',
                'rail_top' => 'border-t-slate-400',
                'gradient' => 'from-slate-50 via-white to-white',
            ],
        };
    }

    public function getStatusDotClassAttribute(): string
    {
        return $this->status_theme['dot'];
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status_theme['badge'];
    }

    public function getBadgeStatusAttribute(): string
    {
        return $this->status_badge_class;
    }

    public function getLuasLahanHaAttribute(): float
    {
        return round((float) $this->luas_lahan / 10000, 4);
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->rt || $this->rw ? 'RT '.($this->rt ?: '-').' / RW '.($this->rw ?: '-') : null,
            $this->kelurahan ? 'Kel. '.$this->kelurahan->nama : null,
            $this->kelurahan?->kecamatan ? 'Kec. '.$this->kelurahan->kecamatan->nama : null,
        ]);

        return implode(', ', $parts);
    }

    public function getPunyaKoordinatAttribute(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function getTotalUnitTipeAttribute(): int
    {
        return (int) $this->tipeUnits->sum('jumlah_unit');
    }

    /**
     * Data ringkas untuk marker peta (dipakai sisi admin maupun publik).
     */
    public function titikPeta(): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
            'status' => $this->label_status,
            'status_kode' => $this->status,
            'alamat' => $this->alamat_lengkap,
            'kelurahan' => $this->kelurahan->nama ?? null,
            'kecamatan' => $this->kelurahan->kecamatan->nama ?? null,
            'unit' => $this->jumlah_unit,
            'luas_lahan' => (float) $this->luas_lahan,
            'rth' => (float) $this->luas_rth,
            'kdb' => $this->kdb !== null ? (float) $this->kdb : null,
            'kdh' => $this->kdh !== null ? (float) $this->kdh : null,
            'tahun_selesai' => $this->tahun_selesai,
        ];
    }

    /**
     * Seluruh titik perumahan dalam format GeoJSON FeatureCollection.
     */
    public static function featureCollection(): array
    {
        $features = self::with('kelurahan.kecamatan')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(fn (self $p) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $p->longitude, (float) $p->latitude],
                ],
                'properties' => $p->titikPeta(),
            ]);

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}
