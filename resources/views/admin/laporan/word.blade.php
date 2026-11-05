<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ $laporan->kode }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        /* Microsoft Word mengabaikan @page CSS biasa pada dokumen HTML-.doc;
           ukuran kertas A4 (595.3pt x 841.9pt, margin 18mm ~ 51.05pt) diterapkan
           lewat section Word via div.WordSection1 di bawah. */
        @page WordSection1 {
            size: 595.3pt 841.9pt;
            margin: 51.05pt 51.05pt 51.05pt 51.05pt;
            mso-page-orientation: portrait;
        }
        div.WordSection1 { page: WordSection1; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }
        h1 { font-size: 16pt; text-align: center; margin: 0; }
        h2 { font-size: 14pt; margin: 18pt 0 6pt; }
        h3 { font-size: 12pt; margin: 14pt 0 6pt; }
        p { margin: 6pt 0; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8pt; }
        .kop img { height: 48px; margin-bottom: 4pt; }
        .kop p { margin: 3pt 0 0; }
        table { width: 100%; border-collapse: collapse; margin: 10pt 0; }
        th, td { border: 1px solid #000; padding: 5pt; text-align: left; vertical-align: top; }
        th { width: 27%; font-weight: bold; }
        .judul { border: 1px solid #000; padding: 7pt; }
        .foto-table td { width: 33%; text-align: center; vertical-align: top; }
        .foto-table img { max-width: 150px; max-height: 110px; }
        .foto-keterangan { margin-top: 4pt; font-size: 10pt; }
        .tanda-tangan td { width: 50%; border: 0; text-align: center; padding-top: 20pt; }
        .ruang-tanda-tangan { height: 60pt; }
        .footer { border-top: 1px solid #000; margin-top: 20pt; padding-top: 5pt; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="WordSection1">
    <div class="kop">
        <img src="{{ asset('images/logo.png') }}" alt="">
        <h1>Sistem Data Perumahan — {{ config('peta.nama_wilayah') }}</h1>
        <p>Cetak Laporan Penanganan Warga</p>
    </div>

    <h2>Laporan Penanganan {{ $laporan->label_jenis }}</h2>
    <table>
        <tr><th>Kode Tiket</th><td>{{ $laporan->kode }}</td></tr>
        <tr><th>Status Penanganan</th><td>{{ $laporan->label_status }}</td></tr>
        <tr><th>Tanggal Lapor</th><td>{{ $laporan->created_at->translatedFormat('d F Y, H:i') }}</td></tr>
        @if ($laporan->selesai_pada)<tr><th>Tanggal Selesai</th><td>{{ $laporan->selesai_pada->translatedFormat('d F Y, H:i') }}</td></tr>@endif
        <tr><th>Lokasi</th><td>{{ $laporan->lokasi_ringkas }}</td></tr>
        @if ($laporan->psu)<tr><th>Sarana / Prasarana</th><td>{{ $laporan->psu->jenis }}</td></tr>@endif
        @if ($laporan->jenis === 'kerusakan')<tr><th>Tingkat & Ukuran</th><td>{{ $laporan->label_tingkat }} — {{ $laporan->ukuran_ringkas }}</td></tr>@endif
        <tr><th>Nama Pelapor</th><td>{{ $laporan->nama_pelapor }}</td></tr>
    </table>

    <div class="judul"><strong>{{ $laporan->judul }}</strong><p>{{ $laporan->deskripsi }}</p></div>

    <table>
        <tr><th>Ditangani Oleh</th><td>{{ $laporan->penangan->name ?? '-' }}</td></tr>
        <tr><th>Catatan Petugas</th><td>{{ $laporan->catatan_petugas ?? '-' }}</td></tr>
        <tr><th>Deskripsi Aksi Selesai</th><td>{{ $laporan->deskripsi_aksi_selesai ?? '-' }}</td></tr>
    </table>

    <h3>A. Foto Kondisi Awal (Bukti Pelapor)</h3>
    @if ($fotosLaporan->isEmpty())
        <p>Tidak ada foto bukti terlampir.</p>
    @else
        <table class="foto-table"><tr>
            @foreach ($fotosLaporan as $item)
                <td>@if ($item['src'])<img src="{{ $item['src'] }}" alt="Foto kondisi awal">@else Foto tidak tersedia @endif</td>
                @if ($loop->iteration % 3 === 0 && ! $loop->last)</tr><tr>@endif
            @endforeach
        </tr></table>
    @endif

    <h3>B. Foto Hasil Perbaikan</h3>
    @if ($fotosPerbaikan->isEmpty())
        <p>Belum ada foto hasil perbaikan.</p>
    @else
        <table class="foto-table"><tr>
            @foreach ($fotosPerbaikan as $item)
                <td>
                    @if ($item['src'])<img src="{{ $item['src'] }}" alt="Foto hasil perbaikan">@else Foto tidak tersedia @endif
                    @if ($item['foto']->keterangan)<div class="foto-keterangan">{{ $item['foto']->keterangan }}</div>@endif
                </td>
                @if ($loop->iteration % 3 === 0 && ! $loop->last)</tr><tr>@endif
            @endforeach
        </tr></table>
    @endif

    <table class="tanda-tangan">
        <tr>
            <td>Petugas Penanganan,<div class="ruang-tanda-tangan"></div><strong>{{ $laporan->penangan->name ?? '(..........................)' }}</strong></td>
            <td>{{ config('peta.nama_wilayah') }}, {{ now()->translatedFormat('d F Y') }}<br>Mengetahui,<div class="ruang-tanda-tangan"></div><strong>(..........................)</strong></td>
        </tr>
    </table>
    <div class="footer">Dicetak oleh {{ auth()->user()->name }} pada {{ now()->translatedFormat('d F Y, H:i') }}</div>
    </div>
</body>
</html>
