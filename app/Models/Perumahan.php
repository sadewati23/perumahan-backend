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
        'subsidi' => '#f59e0b',
        'komersil' => '#262626',
        'campuran' => '#9f1239',
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

    public function getStatusDotClassAttribute(): string
    {
        return match ($this->status) {
            'subsidi' => 'bg-amber-500',
            'komersil' => 'bg-neutral-800',
            'campuran' => 'bg-rose-800',
            default => 'bg-slate-500',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'subsidi' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
            'komersil' => 'bg-neutral-200 text-neutral-800 ring-neutral-600/20',
            'campuran' => 'bg-rose-100 text-rose-800 ring-rose-700/20',
            default => 'bg-slate-100 text-slate-700 ring-slate-500/20',
        };
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
