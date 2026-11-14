<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan perubahan status laporan (audit trail).
 *
 * Baris riwayat bersifat hanya-tambah: tidak ada fitur ubah atau hapus
 * di aplikasi. Baris ikut terhapus bersama laporan lewat foreign key.
 */
class LaporanRiwayat extends Model
{
    /** Riwayat tidak memiliki kolom updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'laporan_id', 'user_id', 'status_lama', 'status_baru', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /** Petugas yang melakukan perubahan status. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLabelStatusLamaAttribute(): string
    {
        return Laporan::STATUS[$this->status_lama] ?? $this->status_lama;
    }

    public function getLabelStatusBaruAttribute(): string
    {
        return Laporan::STATUS[$this->status_baru] ?? $this->status_baru;
    }
}
