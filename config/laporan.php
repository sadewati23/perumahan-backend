<?php

$h = (int) env('LAPORAN_HAPUS_OTOMATIS_HARI', 3);

return [
    'hapus_otomatis' => (bool) env('LAPORAN_HAPUS_OTOMATIS', true),
    'hapus_otomatis_hari' => $h >= 1 ? $h : 3,
    // Laporan yang diselesaikan sebelum tanggal ini tidak pernah dihapus otomatis (YYYY-MM-DD, WIB).
    'hapus_otomatis_sejak' => env('LAPORAN_HAPUS_OTOMATIS_SEJAK') ?: null,
];
