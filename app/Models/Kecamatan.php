<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Kecamatan extends Model
{
    protected $fillable = ['kode', 'nama', 'kabupaten_kota'];

    public function kelurahans(): HasMany
    {
        return $this->hasMany(Kelurahan::class);
    }

    public function perumahans(): HasManyThrough
    {
        return $this->hasManyThrough(Perumahan::class, Kelurahan::class, 'kecamatan_id', 'kelurahan_id');
    }
}
