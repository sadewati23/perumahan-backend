<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Wilayah Default Peta
    |--------------------------------------------------------------------------
    |
    | Nilai ini dipakai sebagai pusat tampilan peta (beranda, peta sebaran, dan
    | pemilih titik pada form) selama perumahan belum memiliki titik koordinat.
    | Ubah melalui .env bila sistem dipakai untuk wilayah lain.
    |
    */
    'nama_wilayah' => env('PETA_WILAYAH', 'Kota Cirebon'),

    'latitude' => (float) env('PETA_LATITUDE', -6.7320),

    'longitude' => (float) env('PETA_LONGITUDE', 108.5523),

    'zoom' => (int) env('PETA_ZOOM', 13),

    'zoom_detail' => (int) env('PETA_ZOOM_DETAIL', 17),

    'batas' => [
        'latitude_min' => (float) env('PETA_LAT_MIN', -6.7900),
        'latitude_max' => (float) env('PETA_LAT_MAX', -6.6900),
        'longitude_min' => (float) env('PETA_LNG_MIN', 108.5200),
        'longitude_max' => (float) env('PETA_LNG_MAX', 108.6000),
    ],

    'tile_url' => env('PETA_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),

    'tile_attribution' => '&copy; OpenStreetMap contributors',
];
