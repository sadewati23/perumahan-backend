<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipeUnit extends Model
{
    protected $table = 'tipe_units';

    protected $fillable = [
        'perumahan_id', 'tipe', 'jumlah_unit', 'luas_tanah', 'luas_bangunan', 'harga', 'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'luas_tanah' => 'decimal:2',
            'luas_bangunan' => 'decimal:2',
            'harga' => 'decimal:2',
        ];
    }

    public function perumahan(): BelongsTo
    {
        return $this->belongsTo(Perumahan::class);
    }
}
