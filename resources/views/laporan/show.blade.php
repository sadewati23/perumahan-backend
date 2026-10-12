@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('subtitle', $laporan->kode)

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-lg bg-slate-900 px-3 py-1.5 font-mono text-sm font-bold text-white">{{ $laporan->kode }}</span>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_jenis }}">{{ $laporan->label_jenis }}</span>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_status }}">{{ $laporan->label_status }}</span>
            @if ($laporan->label_tingkat)
                <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_tingkat }}">{{ $laporan->label_tingkat }}</span>
            @endif
        </div>

        <a href="{{ route('laporan.index') }}"
           class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            &larr; Kembali ke Daftar
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Isi Laporan</h2>

                <p class="mt-3 text-lg font-semibold text-slate-900">{{ $laporan->judul }}</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $laporan->deskripsi }}</p>

                <dl class="mt-5 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-slate-500">Lokasi</dt>
                        <dd class="text-sm font-medium text-slate-800">
                            @if ($laporan->perumahan)
                                <a href="{{ route('perumahan.show', $laporan->perumahan) }}" class="text-indigo-600 hover:underline">
                                    {{ $laporan->perumahan->nama }}
                                </a>
                            @elseif ($laporan->kelurahan)
                                Kel. {{ $laporan->kelurahan->nama }}, Kec. {{ $laporan->kelurahan->kecamatan->nama ?? '-' }}
                                <span class="text-xs text-slate-500">(di luar area perumahan)</span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if ($laporan->lokasi_teks)
                        <div>
                            <dt class="text-xs text-slate-500">Titik / alamat kejadian</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $laporan->lokasi_teks }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs text-slate-500">Sarana / prasarana</dt>
                        <dd class="text-sm font-medium text-slate-800">
                            @if ($laporan->psu)
                                {{ $laporan->psu->jenis }}
                                <span class="text-xs text-slate-500">(kondisi terdata: {{ $laporan->psu->label_kondisi }})</span>
                            @elseif ($laporan->jenis === 'kerusakan')
                                Umum / tidak spesifik
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if ($laporan->jenis === 'kerusakan')
                        <div>
                            <dt class="text-xs text-slate-500">Persentase kerusakan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $laporan->label_persentase_kerusakan }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Panjang kerusakan</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $laporan->label_panjang_kerusakan }}</dd>
                        </div>
                    @endif
                    @if ($laporan->tanggal_kejadian)
                        <div>
                            <dt class="text-xs text-slate-500">Tanggal kejadian</dt>
                            <dd class="text-sm font-medium text-slate-800">{{ $laporan->label_tanggal_kejadian }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-slate-500">Lokasi administratif</dt>
                        <dd class="text-sm font-medium text-slate-800">
                            @if ($laporan->kelurahan_id && $laporan->kelurahan)
                                Kel. {{ $laporan->kelurahan->nama }}, Kec. {{ $laporan->kelurahan->kecamatan->nama ?? '-' }}
                            @else
                                Kel. {{ $laporan->perumahan->kelurahan->nama ?? '-' }},
                                Kec. {{ $laporan->perumahan->kelurahan->kecamatan->nama ?? '-' }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">
                    Foto Bukti
                    <span class="ml-1 font-normal text-slate-500">({{ $laporan->fotos->count() }} lampiran)</span>
                </h2>

                @if ($laporan->fotos->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">
                        @if ($laporan->jenis === 'bangunan_liar')
                            Tidak ada foto terlampir.
                        @else
                            Pelapor tidak melampirkan foto.
                        @endif
                    </p>
                @else
                    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($laporan->fotos as $foto)
                            <a href="{{ $foto->url }}" target="_blank" rel="noopener"
                               class="group block overflow-hidden rounded-lg border border-slate-200">
                                <img src="{{ $foto->url }}" alt="Foto bukti {{ $laporan->kode }} #{{ $loop->iteration }}"
                                     loading="lazy"
                                     class="h-36 w-full object-cover transition group-hover:scale-105">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Pelapor</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-slate-500">Nama</dt>
                        <dd class="font-medium text-slate-800">{{ $laporan->nama_pelapor }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Kontak</dt>
                        @if ($laporan->kontak_pelapor)
                            <dd class="font-medium text-slate-800">{{ $laporan->kontak_pelapor }}</dd>
                        @else
                            <dd class="text-slate-400">Tidak dicantumkan</dd>
                        @endif
                    </div>
                </dl>

                @if ($laporan->kontak_wa)
                    <a href="https://wa.me/{{ $laporan->kontak_wa }}" target="_blank" rel="noopener"
                       class="mt-4 flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        Hubungi via WhatsApp
                    </a>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-800">Penanganan</h2>

                <form action="{{ route('laporan.update', $laporan) }}" method="POST" class="mt-3 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status <span class="text-rose-500">*</span></label>
                        <select id="status" name="status" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            @foreach (\App\Models\Laporan::STATUS as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $laporan->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="catatan_petugas" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Catatan tindak lanjut
                        </label>
                        <textarea id="catatan_petugas" name="catatan_petugas" rows="5" maxlength="2000"
                                  placeholder="Contoh: Tim lapangan sudah turun pada 12 Oktober, reparasi PJU blok C selesai."
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ old('catatan_petugas', $laporan->catatan_petugas) }}</textarea>
                    </div>

                    <div>
                        <label for="deskripsi_aksi_selesai" class="mb-1.5 block text-sm font-medium text-slate-700">
                            Detail aksi selesai <span class="font-normal text-slate-500">(opsional)</span>
                        </label>
                        <textarea id="deskripsi_aksi_selesai" name="deskripsi_aksi_selesai" rows="3" maxlength="2000"
                                  placeholder="Jelaskan kegiatan yang selesai dilakukan mengatasi kerusakan ini…"
                                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ old('deskripsi_aksi_selesai', $laporan->deskripsi_aksi_selesai) }}</textarea>
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        Simpan Penanganan
                    </button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm text-xs text-slate-500">
                <h2 class="text-sm font-semibold text-slate-800">Riwayat</h2>
                <ul class="mt-3 space-y-2">
                    <li>Dilaporkan: <span class="font-medium text-slate-700">{{ $laporan->created_at->translatedFormat('d F Y, H:i') }}</span></li>
                    @if ($laporan->ditangani_pada)
                        <li>
                            Terakhir ditangani:
                            <span class="font-medium text-slate-700">{{ $laporan->ditangani_pada->translatedFormat('d F Y, H:i') }}</span>
                            @if ($laporan->penangan)
                                oleh <span class="font-medium text-slate-700">{{ $laporan->penangan->name }}</span>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection
