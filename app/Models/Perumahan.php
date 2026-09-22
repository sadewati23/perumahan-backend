<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Perumahan extends Model
{
    use SoftDeletes;

    public const STATUS = [
        'subsidi' => 'Subsidi',
        'komersil' => 'Komersil',
    ];

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
        'nama', 'pengembang', 'kelurahan_id', 'alamat', 'rt', 'rw',
        'latitude', 'longitude', 'batas_polygon', 'luas_lahan', 'jumlah_unit', 'status',
        'tahun_pembuatan', 'tahun_selesai', 'jumlah_rth', 'luas_rth',
        'kdb', 'kdh', 'ada_lahan_pemakaman', 'luas_lahan_pemakaman',
        'lokasi_lahan_pemakaman', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'batas_polygon' => 'array',
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
                $q->where(function (Builder $sub) use ($keyword) {
                    $sub->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('pengembang', 'like', "%{$keyword}%")
                        ->orWhere('alamat', 'like', "%{$keyword}%")
                        ->orWhereHas('kelurahan', fn (Builder $k) => $k->where('nama', 'like', "%{$keyword}%"))
                        ->orWhereHas('kelurahan.kecamatan', fn (Builder $k) => $k->where('nama', 'like', "%{$keyword}%"));
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

    public function getBadgeStatusAttribute(): string
    {
        return $this->status === 'subsidi'
            ? 'bg-emerald-100 text-emerald-700 ring-emerald-600/20'
            : 'bg-indigo-100 text-indigo-700 ring-indigo-600/20';
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

    /**
     * Batas polygon dalam bentuk array pasangan [lat, lng] yang siap dipakai Leaflet.
     */
    public function getPolygonKoordinatAttribute(): array
    {
        $polygon = $this->batas_polygon;

        if (! is_array($polygon)) {
            return [];
        }

        $koordinat = [];
        foreach ($polygon as $titik) {
            if (! is_array($titik) || count($titik) < 2) {
                continue;
            }

            $lat = is_numeric($titik[0]) ? (float) $titik[0] : null;
            $lng = is_numeric($titik[1]) ? (float) $titik[1] : null;

            if ($lat === null || $lng === null) {
                continue;
            }

            $koordinat[] = [$lat, $lng];
        }

        return count($koordinat) >= 3 ? $koordinat : [];
    }

    public function getPunyaPolygonAttribute(): bool
    {
        return $this->polygon_koordinat !== [];
    }

    /**
     * Luas polygon batas dalam m² memakai rumus Chamberlain–Duquette
     * (spherical excess — sama seperti turf.js), akurat untuk area
     * sekecil apapun pada permukaan bumi.
     */
    public function getLuasPolygonAttribute(): ?float
    {
        $koordinat = $this->polygon_koordinat;

        if (count($koordinat) < 3) {
            return null;
        }

        $jariBumi = 6378137.0; // meter (WGS84)
        $jumlah = count($koordinat);
        $total = 0.0;

        for ($i = 0; $i < $jumlah; $i++) {
            [$latBawah, $lngBawah] = $koordinat[$i];
            $latTengah = $koordinat[($i + 1) % $jumlah][0];
            $lngAtas = $koordinat[($i + 2) % $jumlah][1];

            $total += deg2rad($lngAtas - $lngBawah) * sin(deg2rad($latTengah));
        }

        return (float) abs(round($total * $jariBumi ** 2 / 2, 2));
    }

    /**
     * Luas polygon batas dalam hektar.
     */
    public function getLuasPolygonHaAttribute(): ?float
    {
        return $this->luas_polygon === null ? null : round($this->luas_polygon / 10000, 4);
    }

    /**
     * Selisih luas polygon terhadap luas lahan inputan (m²),
     * positif berarti polygon lebih luas.
     */
    public function getSelisihLuasAttribute(): ?float
    {
        if ($this->luas_polygon === null || (float) $this->luas_lahan <= 0) {
            return null;
        }

        return round($this->luas_polygon - (float) $this->luas_lahan, 2);
    }

    /**
     * Persentase deviasi luas polygon terhadap luas lahan inputan.
     */
    public function getDeviasiLuasPersenAttribute(): ?float
    {
        if ($this->luas_polygon === null || (float) $this->luas_lahan <= 0) {
            return null;
        }

        return round(($this->luas_polygon - (float) $this->luas_lahan) / (float) $this->luas_lahan * 100, 2);
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
            'polygon' => $this->polygon_koordinat,
            'luas_polygon' => $this->luas_polygon,
        ];
    }

    /**
     * Seluruh titik perumahan dalam format GeoJSON FeatureCollection.
     */
    public static function featureCollection(): array
    {
        $features = self::with('kelurahan.kecamatan')
            ->where(function (Builder $q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude')
                    ->orWhereNotNull('batas_polygon');
            })
            ->get()
            ->map(function (self $p) {
                $properties = $p->titikPeta();

                // Polygon disimpan terpisah sebagai Feature geometry Polygon.
                if ($p->punya_polygon) {
                    $cincin = array_map(
                        fn (array $titik) => [$titik[1], $titik[0]],
                        $p->polygon_koordinat,
                    );

                    // Tutup cincin polygon bila titik awal dan akhir berbeda.
                    if ($cincin[0] !== $cincin[count($cincin) - 1]) {
                        $cincin[] = $cincin[0];
                    }

                    $properties['geometry_polygon'] = [
                        'type' => 'Polygon',
                        'coordinates' => [$cincin],
                    ];
                }

                // Feature titik wajib punya koordinat; polygon saja masuk sebagai null-coordinates.
                if (! $p->punya_koordinat) {
                    return [
                        'type' => 'Feature',
                        'geometry' => null,
                        'properties' => $properties,
                    ];
                }

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $p->longitude, (float) $p->latitude],
                    ],
                    'properties' => $properties,
                ];
            });

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }
}
