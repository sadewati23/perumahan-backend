<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LaporanFoto extends Model
{
    /** Foto bukti yang diunggah pelapor warga saat membuat laporan. */
    public const TIPE_LAPORAN = 'laporan';

    /** Foto hasil perbaikan yang diunggah petugas setelah penanganan. */
    public const TIPE_PERBAIKAN = 'perbaikan';

    protected $fillable = [
        'laporan_id', 'path', 'deskripsi_aksi_selesai',
        'tipe', 'keterangan', 'diunggah_oleh',
    ];

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(Laporan::class);
    }

    /** Petugas yang mengunggah foto (khusus foto hasil perbaikan). */
    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getLabelTipeAttribute(): string
    {
        return $this->tipe === self::TIPE_PERBAIKAN ? 'Hasil Perbaikan' : 'Kondisi Awal';
    }
}
