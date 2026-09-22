<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelurahan extends Model
{
    protected $fillable = ['kecamatan_id', 'kode', 'nama', 'kode_pos'];

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function perumahans(): HasMany
    {
        return $this->hasMany(Perumahan::class);
    }

    public function getNamaLengkapAttribute(): string
    {
        return 'Kel. '.$this->nama.', Kec. '.($this->kecamatan->nama ?? '-');
    }
}
