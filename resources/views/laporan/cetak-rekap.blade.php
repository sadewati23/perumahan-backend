<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Rekap Laporan Warga</title>
    <style>
        @page { size: A4; margin: 15mm }
        * { box-sizing: border-box }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0 auto;
            max-width: 210mm;
        }
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

        .ringkasan { display: flex; gap: 8px; margin-top: 12px; page-break-inside: avoid }
        .ringkasan .kartu { flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px; text-align: center }
        .ringkasan .label { font-size: 10px; color: #64748b }
        .ringkasan .angka { font-size: 16px; font-weight: 700 }

        table { width: 100%; border-collapse: collapse; margin-top: 12px }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; vertical-align: top }
        thead th { background: #f1f5f9; font-weight: 600; white-space: nowrap }
        tbody tr { page-break-inside: avoid }

        .kolom-teks { max-width: 140px }
        .kode { font-family: "Consolas", monospace; font-weight: 600 }

        .catatan { margin-top: 10px; padding: 6px 8px; border: 1px solid #f59e0b; background: #fef3c7; font-size: 10px; page-break-inside: avoid }

        footer.cetak { margin-top: 20px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px }

        @media print {
            .no-print { display: none }
        }
    </style>
</head>
<body>
    {{-- Toolbar layar — tidak ikut tercetak. --}}
    <div class="no-print">
        <button type="button" id="tombol-cetak">🖨️ Cetak Rekap</button>
        <button type="button" id="tombol-tutup">Tutup Tab</button>
    </div>

    <div class="kop">
        <h1>Sistem Data Perumahan — {{ config('peta.nama_wilayah') }}</h1>
        <p>Rekapitulasi Laporan Warga</p>
    </div>

    <h2 style="margin: 12px 0 4px">Rekap Laporan Warga</h2>
    <p style="margin: 0; color: #475569">
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

    <div class="ringkasan">
        <div class="kartu"><div class="label">Total</div><div class="angka">{{ $ringkasan['total'] }}</div></div>
        <div class="kartu"><div class="label">Baru</div><div class="angka">{{ $ringkasan['baru'] }}</div></div>
        <div class="kartu"><div class="label">Diproses</div><div class="angka">{{ $ringkasan['diproses'] }}</div></div>
        <div class="kartu"><div class="label">Selesai</div><div class="angka">{{ $ringkasan['selesai'] }}</div></div>
        <div class="kartu"><div class="label">Ditolak</div><div class="angka">{{ $ringkasan['ditolak'] }}</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Lokasi</th>
                <th>Judul</th>
                <th>Tingkat/Ukuran</th>
                <th>Status</th>
                <th>Tgl Selesai</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($laporans as $laporan)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="kode">{{ $laporan->kode }}</td>
                    <td>{{ $laporan->created_at->translatedFormat('d M Y') }}</td>
                    <td>{{ $laporan->label_jenis }}</td>
                    <td class="kolom-teks">{{ $laporan->lokasi_ringkas }}</td>
                    <td class="kolom-teks">{{ $laporan->judul }}</td>
                    <td>{{ $laporan->label_tingkat !== null ? $laporan->label_tingkat.' — '.$laporan->ukuran_ringkas : '-' }}</td>
                    <td>{{ $laporan->label_status }}</td>
                    <td>{{ $laporan->selesai_pada?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td>{{ $laporan->penangan->name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center; padding: 24px 0; color: #64748b">
                        Tidak ada laporan yang cocok dengan filter saat ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($terpotong)
        <div class="catatan">
            Perhatian: terdapat {{ $ringkasan['total'] }} laporan sesuai filter, tetapi cetakan ini hanya memuat
            {{ $batasBaris }} baris terbaru..tap Semua data tetap tersedia di modul Laporan Warga.
        </div>
    @endif

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
