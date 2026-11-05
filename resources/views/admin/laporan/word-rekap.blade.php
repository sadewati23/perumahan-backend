<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Laporan Warga</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        /* Microsoft Word mengabaikan @page CSS biasa pada dokumen HTML-.doc;
           ukuran kertas A4 landscape (1190.6pt x 841.9pt, margin 12mm ~ 34.05pt)
           diterapkan lewat section Word via div.WordSection1 di bawah. */
        @page WordSection1 {
            size: 1190.6pt 841.9pt;
            margin: 34.05pt 34.05pt 34.05pt 34.05pt;
            mso-page-orientation: landscape;
        }
        div.WordSection1 { page: WordSection1; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #000; }
        h1 { font-size: 16pt; text-align: center; margin: 0; }
        h2 { font-size: 14pt; margin: 18pt 0 6pt; }
        .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 8pt; }
        .kop img { height: 48px; margin-bottom: 4pt; }
        .kop p { margin: 3pt 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10pt; }
        th, td { border: 1px solid #000; padding: 4pt; text-align: left; vertical-align: top; }
        th { font-weight: bold; }
        .footer { border-top: 1px solid #000; margin-top: 20pt; padding-top: 5pt; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="WordSection1">
    <div class="kop">
        <img src="{{ asset('images/logo.png') }}" alt="">
        <h1>Sistem Data Perumahan — {{ config('peta.nama_wilayah') }}</h1>
        <p>Rekapitulasi Laporan Warga</p>
    </div>
    <h2>Rekap Laporan Warga</h2>
    <p>
        @if ($filters['q'] ?? $filters['jenis'] ?? $filters['status'] ?? $filters['tingkat'] ?? $filters['perumahan_id'] ?? $filters['dari'] ?? $filters['sampai'] ?? null)
            Dengan filter:
            @if (($filters['q'] ?? '') !== '') “{{ $filters['q'] }}” @endif
            @if ($filters['jenis'] ?? null) · jenis {{ \App\Models\Laporan::JENIS[$filters['jenis']] }} @endif
            @if ($filters['status'] ?? null) · status {{ \App\Models\Laporan::STATUS[$filters['status']] }} @endif
            @if ($filters['tingkat'] ?? null) · tingkat {{ \App\Models\Laporan::TINGKAT[$filters['tingkat']] }} @endif
            @if ($filters['dari'] ?? null) · dari {{ $filters['dari'] }} @endif
            @if ($filters['sampai'] ?? null) · sampai {{ $filters['sampai'] }} @endif
        @else
            Tanpa filter — seluruh laporan terdata.
        @endif
    </p>
    <table>
        <tr><th>Total</th><th>Baru</th><th>Diproses</th><th>Selesai</th><th>Ditolak</th></tr>
        <tr><td>{{ $ringkasan['total'] }}</td><td>{{ $ringkasan['baru'] }}</td><td>{{ $ringkasan['diproses'] }}</td><td>{{ $ringkasan['selesai'] }}</td><td>{{ $ringkasan['ditolak'] }}</td></tr>
    </table>
    <table>
        <tr><th>No</th><th>Kode</th><th>Tanggal</th><th>Jenis</th><th>Lokasi</th><th>Judul</th><th>Tingkat/Ukuran</th><th>Status</th><th>Tgl Selesai</th><th>Petugas</th></tr>
        @forelse ($laporans as $laporan)
            <tr>
                <td>{{ $loop->iteration }}</td><td>{{ $laporan->kode }}</td><td>{{ $laporan->created_at->translatedFormat('d M Y') }}</td><td>{{ $laporan->label_jenis }}</td><td>{{ $laporan->lokasi_ringkas }}</td><td>{{ $laporan->judul }}</td><td>{{ $laporan->label_tingkat !== null ? $laporan->label_tingkat.' — '.$laporan->ukuran_ringkas : '-' }}</td><td>{{ $laporan->label_status }}</td><td>{{ $laporan->selesai_pada?->translatedFormat('d M Y') ?? '-' }}</td><td>{{ $laporan->penangan->name ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="10">Tidak ada laporan yang cocok dengan filter saat ini.</td></tr>
        @endforelse
    </table>
    @if ($terpotong)<p>Perhatian: terdapat {{ $ringkasan['total'] }} laporan sesuai filter, tetapi file ini hanya memuat {{ $batasBaris }} baris terbaru.</p>@endif
    <div class="footer">Dicetak oleh {{ auth()->user()->name }} pada {{ now()->translatedFormat('d F Y, H:i') }}</div>
    </div>
</body>
</html>
