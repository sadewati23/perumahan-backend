@props([
    'name' => 'kotak',
    'size' => 'h-5 w-5',
    'strokeWidth' => 1.7,
])

@php
    /*
     * Satu peta ikon garis untuk seluruh area (admin & publik).
     * Semua ikon memakai kotak 24x24, stroke currentColor, tanpa fill.
     * Tambahkan nama baru di sini alih-alih menyalin SVG di tiap view.
     */
    $peta = [
        'dashboard' => [
            'M4 4h7v7H4z', 'M13 4h7v7h-7z', 'M4 13h7v7H4z', 'M13 13h7v7h-7z',
        ],
        'perumahan' => [
            'M4 21V8l8-5 8 5v13', 'M9 21v-6h6v6',
        ],
        'peta' => [
            'M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11z', 'M12 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        ],
        'wilayah' => [
            'M12 3l9 5-9 5-9-5 9-5z', 'M3 13l9 5 9-5',
        ],
        'pengguna' => [
            'M16 21v-2a4 4 0 00-8 0v2', 'M12 11a4 4 0 100-8 4 4 0 000 8z',
        ],
        'laporan' => [
            'M12 9v4m0 4h.01M10.3 3.9L2.5 17a1.7 1.7 0 001.5 2.5h16a1.7 1.7 0 001.5-2.5L13.7 3.9a1.7 1.7 0 00-3 0z',
        ],
        'plus' => [
            'M12 5v14', 'M5 12h14',
        ],
        'menu' => [
            'M4 6h16', 'M4 12h16', 'M4 18h16',
        ],
        'tutup' => [
            'M6 18L18 6', 'M6 6l12 12',
        ],
        'eksternal' => [
            'M14 5h5v5', 'M19 5l-7 7', 'M9 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-3',
        ],
        'keluar' => [
            'M15 17l5-5-5-5', 'M20 12H9', 'M12 3H6a2 2 0 00-2 2v14a2 2 0 002 2h6',
        ],
        'panah-kanan' => [
            'M9 5l7 7-7 7',
        ],
        'rumah' => [
            'M3 11l9-7 9 7', 'M5 10v10h14V10', 'M10 20v-5h4v5',
        ],
        'lahan' => [
            'M4 4h16v16H4z', 'M4 9.5h16', 'M9.5 4v16',
        ],
        'dedaunan' => [
            'M20 4C10 4 4 9 4 17c0 1.7 1.3 3 3 3 8 0 13-6 13-16z', 'M4 20C7 14 11 11 16 9',
        ],
        'jam' => [
            'M12 21a9 9 0 100-18 9 9 0 000 18z', 'M12 7v5l3 2',
        ],
        'kalender' => [
            'M7 3v4', 'M17 3v4', 'M4 7h16v13H4z', 'M4 11h16',
        ],
        'cek-lingkaran' => [
            'M12 21a9 9 0 100-18 9 9 0 000 18z', 'M8 12l2.5 2.5L16 9',
        ],
        'grafik' => [
            'M5 20V10', 'M12 20V4', 'M19 20v-6', 'M3 20h18',
        ],
        'kotak-masuk' => [
            'M4 13V7a2 2 0 012-2h12a2 2 0 012 2v6m0 0a2 2 0 012 2v3a2 2 0 01-2 2H4a2 2 0 01-2-2v-3a2 2 0 012-2m16 0H9a3 3 0 00-5 0H4',
        ],
        'cetak' => [
            'M6 9V3h12v6', 'M6 18H4a2 2 0 01-2-2v-4a2 2 0 012-2h16a2 2 0 012 2v4a2 2 0 01-2 2h-2', 'M6 14h12v7H6z',
        ],
        'panah-kiri' => [
            'M15 5l-7 7 7 7',
        ],
        'telepon' => [
            'M21 16.9v2a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 011.1 3.2 2 2 0 013.1 1h2a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L6.1 8.9a16 16 0 006 6l1.3-1.2a2 2 0 012.1-.5c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z',
        ],
        'filter' => [
            'M4 5h16', 'M7 12h10', 'M10 19h4',
        ],
        'unggah' => [
            'M12 16V4', 'M7 9l5-5 5 5', 'M4 20h16',
        ],
        'gps' => [
            'M12 3v3', 'M12 18v3', 'M3 12h3', 'M18 12h3', 'M12 18a6 6 0 100-12 6 6 0 000 12z', 'M12 14a2 2 0 100-4 2 2 0 000 4z',
        ],
        'pensil' => [
            'M12 20h9', 'M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z',
        ],
        'sampah' => [
            'M3 6h18', 'M8 6V4h8v2', 'M6 6l1 14h10l1-14', 'M10 11v6', 'M14 11v6',
        ],
        'cari' => [
            'M11 18a7 7 0 100-14 7 7 0 000 14z', 'M20 20l-4-4',
        ],
        'perisai' => [
            'M12 3l8 3v6c0 4.5-3.5 7.6-8 9-4.5-1.4-8-4.5-8-9V6z',
        ],
        'kunci' => [
            'M6 11h12v9H6z', 'M9 11V8a3 3 0 016 0v3',
        ],
        'masuk' => [
            'M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4', 'M10 17l5-5-5-5', 'M15 12H3',
        ],
        'simpan' => [
            'M5 3h11l3 3v15H5z', 'M8 3v6h8V3', 'M8 14h8v7H8z',
        ],
        'berkas' => [
            'M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9z', 'M14 3v6h6',
        ],
        'kotak' => [
            'M4 4h16v16H4z',
        ],
    ];

    $jalur = $peta[$name] ?? $peta['kotak'];
    $kelas = $attributes->get('class') ?? $size;
@endphp

<svg {{ $attributes->except('class')->merge([
    'class' => $kelas,
    'fill' => 'none',
    'stroke' => 'currentColor',
    'stroke-width' => $strokeWidth,
    'viewBox' => '0 0 24 24',
    'aria-hidden' => 'true',
]) }}>
    @foreach ($jalur as $d)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}"/>
    @endforeach
</svg>
