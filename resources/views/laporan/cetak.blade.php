<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan {{ $laporan->kode }}</title>
    <style>
        @page { size: A4; margin: 15mm }
        * { box-sizing: border-box }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0 auto;
            max-width: 210mm;
        }
        /* Toolbar hanya tampil di layar, hilang saat dicetak. */
        .no-print { margin-bottom: 16px }
        .no-print button {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            cursor: pointer;
        }
        .no-print button:hover { background: #e2e8f0 }

        .kop { border-bottom: 3px double #334155; padding-bottom: 8px; text-align: center }
        .kop h1 { font-size: 15px; margin: 0; text-transform: uppercase; letter-spacing: .5px }
        .kop p { margin: 2px 0 0; font-size: 11px; color: #64748b }

        .kode { font-family: "Consolas", monospace; font-weight: 700 }

        table.info, table.rekap { width: 100%; border-collapse: collapse; margin-top: 12px }
        table.info th, table.info td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top }
        table.info th { background: #f1f5f9; font-weight: 600; white-space: nowrap; width: 170px }

        .judul-blok { border: 1px solid #cbd5e1; padding: 8px; margin-top: 12px }
        .judul-blok p.teks { margin: 6px 0 0; white-space: pre-line }

        .grid-foto { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 8px }
        .foto-item { page-break-inside: avoid; border: 1px solid #cbd5e1; padding: 4px }
        .foto-item img { width: 100%; height: 90px; object-fit: cover; display: block; border: 1px solid #e2e8f0 }
        .foto-item .ket { font-size: 10px; color: #475569; margin-top: 3px; word-break: break-word }

        .tanda-tangan { display: flex; justify-content: space-between; gap: 24px; margin-top: 28px; page-break-inside: avoid }
        .tanda-tangan .kolom { width: 42%; text-align: center }
        .tanda-tangan .ruang { height: 56px }
        .tanda-tangan .nama { border-top: 1px solid #334155; padding-top: 4px; font-weight: 600 }

        footer.cetak { margin-top: 24px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px }

        @media print {
            .no-print { display: none }
        }
    </style>
</head>
<body>
    {{-- Toolbar layar: tombol Cetak via addEventListener (bukan onclick inline). --}}
    <div class="no-print">
        <button type="button" id="tombol-cetak">🖨️ Cetak Laporan</button>
        <button type="button" id="tombol-tutup">Tutup Tab</button>
    </div>

    <div class="kop">
        <h1>Sistem Data Perumahan — {{ config('peta.nama_wilayah') }}</h1>
        <p>Cetak Laporan Penanganan Warga</p>
    </div>

    <h2 style="margin: 12px 0 4px">Laporan Penanganan {{ $laporan->label_jenis }}</h2>

    <table class="info">
        <tbody>
            <tr>
                <th>Kode Tiket</th>
                <td class="kode">{{ $laporan->kode }}</td>
            </tr>
            <tr>
                <th>Status Penanganan</th>
                <td>{{ $laporan->label_status }}</td>
            </tr>
            <tr>
                <th>Tanggal Lapor</th>
                <td>{{ $laporan->created_at->translatedFormat('d F Y, H:i') }}</td>
            </tr>
            @if ($laporan->selesai_pada)
                <tr>
                    <th>Tanggal Selesai</th>
                    <td>{{ $laporan->selesai_pada->translatedFormat('d F Y, H:i') }}</td>
                </tr>
            @endif
            <tr>
                <th>Lokasi</th>
                <td>{{ $laporan->lokasi_ringkas }}</td>
            </tr>
            @if ($laporan->psu)
                <tr>
                    <th>Sarana / Prasarana</th>
                    <td>{{ $laporan->psu->jenis }}</td>
                </tr>
            @endif
            @if ($laporan->jenis === 'kerusakan')
                <tr>
                    <th>Tingkat & Ukuran</th>
                    <td>{{ $laporan->label_tingkat }} — {{ $laporan->ukuran_ringkas }}</td>
                </tr>
            @endif
            <tr>
                <th>Nama Pelapor</th>
                <td>{{ $laporan->nama_pelapor }}</td>
            </tr>
        </tbody>
    </table>

    <div class="judul-blok">
        <strong>{{ $laporan->judul }}</strong>
        <p class="teks">{{ $laporan->deskripsi }}</p>
    </div>

    <table class="info">
        <tbody>
            <tr>
                <th>Ditangani Oleh</th>
                <td>{{ $laporan->penangan->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Catatan Petugas</th>
                <td>{{ $laporan->catatan_petugas ?? '-' }}</td>
            </tr>
            <tr>
                <th>Deskripsi Aksi Selesai</th>
                <td>{{ $laporan->deskripsi_aksi_selesai ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

    <h3 style="margin: 16px 0 4px">A. Foto Kondisi Awal (Bukti Pelapor)</h3>
    @if ($laporan->fotosLaporan->isEmpty())
        <p style="margin-top: 6px; color: #64748b">Tidak ada foto bukti terlampir.</p>
    @else
        <div class="grid-foto">
            @foreach ($laporan->fotosLaporan as $foto)
                <div class="foto-item">
                    <img src="{{ $foto->url }}" alt="Foto kondisi awal {{ $laporan->kode }} #{{ $loop->iteration }}">
                </div>
            @endforeach
        </div>
    @endif

    <h3 style="margin: 16px 0 4px">B. Foto Hasil Perbaikan</h3>
    @if ($laporan->fotosPerbaikan->isEmpty())
        <p style="margin-top: 6px; color: #64748b">Belum ada foto hasil perbaikan.</p>
    @else
        <div class="grid-foto">
            @foreach ($laporan->fotosPerbaikan as $foto)
                <div class="foto-item">
                    <img src="{{ $foto->url }}" alt="Foto hasil perbaikan {{ $laporan->kode }} #{{ $loop->iteration }}">
                    <p class="ket">{{ $foto->keterangan ?? '' }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="tanda-tangan">
        <div class="kolom">
            <p style="margin: 0">Petugas Penanganan,</p>
            <div class="ruang"></div>
            <p class="nama">{{ $laporan->penangan->name ?? '(..........................)' }}</p>
        </div>
        <div class="kolom">
            <p style="margin: 0">{{ config('peta.nama_wilayah') }}, {{ now()->translatedFormat('d F Y') }}</p>
            <p style="margin: 0">Mengetahui,</p>
            <div class="ruang"></div>
            <p class="nama">(..........................)</p>
        </div>
    </div>

    <footer class="cetak">
        Dicetak oleh {{ auth()->user()->name }} pada {{ now()->translatedFormat('d F Y, H:i') }}
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tombolCetak = document.getElementById('tombol-cetak');
            if (tombolCetak) {
                tombolCetak.addEventListener('click', () => window.print());
            }

            const tombolTutup = document.getElementById('tombol-tutup');
            if (tombolTutup) {
                tombolTutup.addEventListener('click', () => window.close());
            }
        });
    </script>
</body>
</html>
