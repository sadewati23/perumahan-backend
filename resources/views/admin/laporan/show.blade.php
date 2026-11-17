@extends('layouts.admin')

@section('title', 'Detail Laporan')
@section('subtitle', $laporan->kode)

@section('content')
    @if ($laporan->jatuh_tempo_hapus)
        <div role="status" class="mb-4 flex flex-wrap items-start gap-3 rounded-card border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800">
            <span class="mt-0.5 shrink-0 text-warning-700" aria-hidden="true">
                <x-icon name="jam" class="h-5 w-5"/>
            </span>
            <p class="min-w-0">
                Laporan ini akan dihapus otomatis pada {{ $laporan->jatuh_tempo_hapus->translatedFormat('d F Y, H:i') }} WIB.
                <a class="font-semibold underline" href="{{ route('laporan.cetak', $laporan) }}">Cetak laporan</a> terlebih dahulu bila masih diperlukan.
            </p>
        </div>
    @elseif ($laporan->jangan_hapus_otomatis)
        <div role="status" class="mb-4 flex flex-wrap items-start gap-3 rounded-card border border-garis bg-kertas-tua p-4 text-sm text-slate-700">
            <span class="mt-0.5 shrink-0 text-slate-500" aria-hidden="true">
                <x-icon name="perisai" class="h-5 w-5"/>
            </span>
            <p class="min-w-0">Laporan ini disimpan permanen.</p>
        </div>
    @endif

    {{-- Identitas laporan --}}
    <div class="rounded-card border border-garis bg-white p-4 shadow-card sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-tinta px-2.5 py-1 font-mono text-xs font-semibold text-white">{{ $laporan->kode }}</span>
                    <x-badge :tone="match ($laporan->jenis) { 'pengaduan' => 'info', 'bangunan_liar' => 'danger', default => 'warning' }">
                        {{ $laporan->label_jenis }}
                    </x-badge>
                    <x-badge :tone="match ($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }">
                        {{ $laporan->label_status }}
                    </x-badge>
                    @if ($laporan->label_tingkat)
                        <x-badge :tone="match ($laporan->tingkat) { 'rusak_berat' => 'danger', 'rusak_ringan' => 'warning', default => 'neutral' }">
                            <x-icon name="grafik" class="h-3.5 w-3.5"/>
                            {{ $laporan->label_tingkat }}
                        </x-badge>
                    @endif
                </div>
                <p class="mt-2 font-judul text-xl font-semibold tracking-tight text-tinta">{{ $laporan->judul }}</p>
                <p class="mt-0.5 text-sm text-slate-600">
                    Dilaporkan {{ $laporan->created_at->translatedFormat('d F Y, H:i') }} WIB
                    &middot; {{ $laporan->nama_pelapor }}
                </p>
            </div>

            <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                <x-button href="{{ route('laporan.cetak', $laporan) }}" variant="secondary" class="flex-1 sm:flex-none" target="_blank" rel="noopener">
                    <x-icon name="cetak" class="h-4 w-4"/>
                    Cetak laporan
                </x-button>
                <x-button href="{{ route('laporan.index') }}" variant="ghost" class="flex-1 sm:flex-none">
                    <x-icon name="panah-kiri" class="h-4 w-4"/>
                    Kembali ke daftar
                </x-button>
            </div>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="min-w-0 space-y-4 lg:col-span-2">
            <x-card heading="Isi Laporan">
                <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $laporan->deskripsi }}</p>

                <dl class="mt-5 grid gap-4 border-t border-garis pt-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium text-slate-600">Lokasi</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-800">
                            @if ($laporan->perumahan)
                                <a href="{{ route('perumahan.show', $laporan->perumahan) }}" class="text-brand-700 hover:underline">
                                    {{ $laporan->perumahan->nama }}
                                </a>
                            @elseif ($laporan->kelurahan)
                                Kel. {{ $laporan->kelurahan->nama }}, Kec. {{ $laporan->kelurahan->kecamatan->nama ?? '-' }}
                                <span class="text-xs font-normal text-slate-600">(di luar area perumahan)</span>
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if ($laporan->lokasi_teks)
                        <div>
                            <dt class="text-xs font-medium text-slate-600">Titik / alamat kejadian</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $laporan->lokasi_teks }}</dd>
                        </div>
                    @endif
                    @if ($laporan->punyaTitik())
                        <div>
                            <dt class="text-xs font-medium text-slate-600">Titik GPS dari pelapor</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-800">
                                {{ number_format((float) $laporan->latitude, 7, '.') }}, {{ number_format((float) $laporan->longitude, 7, '.') }}
                                <a href="{{ $laporan->tautan_peta }}" target="_blank" rel="noopener" class="ml-1 font-medium text-brand-700 hover:underline">buka di peta &nearr;</a>
                            </dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs font-medium text-slate-600">Sarana / prasarana</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-800">
                            @if ($laporan->psu)
                                {{ $laporan->psu->jenis }}
                                <span class="text-xs font-normal text-slate-600">(kondisi terdata: {{ $laporan->psu->label_kondisi }})</span>
                            @elseif ($laporan->jenis === 'kerusakan')
                                Umum / tidak spesifik
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    @if ($laporan->jenis === 'kerusakan')
                        <div>
                            <dt class="text-xs font-medium text-slate-600">Persentase kerusakan</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $laporan->label_persentase_kerusakan }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-slate-600">Panjang kerusakan</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $laporan->label_panjang_kerusakan }}</dd>
                        </div>
                    @endif
                    @if ($laporan->tanggal_kejadian)
                        <div>
                            <dt class="text-xs font-medium text-slate-600">Tanggal kejadian</dt>
                            <dd class="mt-0.5 text-sm font-medium text-slate-800">{{ $laporan->label_tanggal_kejadian }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-600">Lokasi administratif</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-800">
                            @if ($laporan->kelurahan_id && $laporan->kelurahan)
                                Kel. {{ $laporan->kelurahan->nama }}, Kec. {{ $laporan->kelurahan->kecamatan->nama ?? '-' }}
                            @else
                                Kel. {{ $laporan->perumahan->kelurahan->nama ?? '-' }},
                                Kec. {{ $laporan->perumahan->kelurahan->kecamatan->nama ?? '-' }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-card>

            <x-card>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-800">Foto Bukti</h2>
                    <span class="text-xs text-slate-600">{{ $laporan->fotosLaporan->count() }} lampiran warga</span>
                </div>

                @if ($laporan->fotosLaporan->isEmpty())
                    <p class="mt-3 rounded-lg bg-kertas px-3 py-2 text-sm text-slate-600">
                        @if ($laporan->jenis === 'bangunan_liar')
                            Tidak ada foto terlampir.
                        @else
                            Pelapor tidak melampirkan foto.
                        @endif
                    </p>
                @else
                    <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($laporan->fotosLaporan as $foto)
                            <li>
                                <a href="{{ $foto->url }}" target="_blank" rel="noopener"
                                   class="group block overflow-hidden rounded-lg border border-garis focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                                    <img src="{{ $foto->url }}" alt="Foto bukti {{ $laporan->kode }} #{{ $loop->iteration }}"
                                         loading="lazy"
                                         class="h-32 w-full object-cover transition motion-safe:group-hover:scale-105 sm:h-36">
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            {{-- Peringatan bila laporan sudah selesai tapi belum ada bukti hasil perbaikan. --}}
            @if ($laporan->status === 'selesai' && $laporan->fotosPerbaikan->isEmpty())
                <div class="flex flex-wrap items-start gap-3 rounded-card border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800">
                    <span class="mt-0.5 shrink-0 text-warning-700" aria-hidden="true">
                        <x-icon name="jam" class="h-5 w-5"/>
                    </span>
                    <p class="min-w-0">
                        Laporan sudah ditandai <strong>selesai</strong>, tetapi belum ada foto hasil perbaikan.
                        Unggah dokumentasi hasil kerja di bawah agar arsip penanganan lengkap.
                    </p>
                </div>
            @endif

            <x-card>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-800">Foto Hasil Perbaikan</h2>
                    <span class="text-xs text-slate-600">{{ $laporan->fotosPerbaikan->count() }}/10 foto</span>
                </div>

                @if ($laporan->status === 'ditolak')
                    <p class="mt-3 rounded-lg bg-kertas px-3 py-2 text-sm text-slate-600">
                        Laporan ditolak — unggahan foto hasil perbaikan tidak tersedia.
                    </p>
                @else
                    @if ($laporan->fotosPerbaikan->isEmpty())
                        <p class="mt-3 rounded-lg bg-kertas px-3 py-2 text-sm text-slate-600">Belum ada foto hasil perbaikan.</p>
                    @else
                        <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                            @foreach ($laporan->fotosPerbaikan as $foto)
                                <li class="overflow-hidden rounded-lg border border-garis">
                                    <a href="{{ $foto->url }}" target="_blank" rel="noopener" class="group block">
                                        <img src="{{ $foto->url }}" alt="Foto perbaikan {{ $laporan->kode }} #{{ $loop->iteration }}"
                                             loading="lazy"
                                             class="h-32 w-full object-cover transition motion-safe:group-hover:scale-105 sm:h-36">
                                    </a>
                                    <div class="flex items-start justify-between gap-2 border-t border-garis px-2 py-2">
                                        <div class="min-w-0 text-xs text-slate-600">
                                            <p class="line-clamp-2">{{ $foto->keterangan ?? 'Tanpa keterangan' }}</p>
                                            @if ($foto->pengunggah)
                                                <p class="mt-0.5 text-slate-500">oleh {{ $foto->pengunggah->name }}</p>
                                            @endif
                                        </div>
                                        @if (auth()->user()->isAdmin() || $foto->diunggah_oleh === auth()->id())
                                            <button type="button" data-confirm="Hapus foto hasil perbaikan ini?\nFile akan dihapus permanen dari penyimpanan."
                                                    data-action="{{ route('laporan.foto-perbaikan.destroy', [$laporan, $foto]) }}"
                                                    aria-label="Hapus foto hasil perbaikan {{ $loop->iteration }}"
                                                    class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-control border border-danger-200 px-2 py-1 text-danger-700 transition hover:bg-danger-50 sm:min-h-8">
                                                <x-icon name="sampah" class="h-4 w-4"/>
                                            </button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Form unggah multi-foto hasil perbaikan. --}}
                    <form action="{{ route('laporan.foto-perbaikan.store', $laporan) }}" method="POST" enctype="multipart/form-data"
                          class="mt-4 space-y-3 rounded-lg border border-garis bg-kertas p-4">
                        @csrf
                        <div>
                            <label for="foto_perbaikan" class="label mb-1.5">
                                Tambah foto hasil perbaikan
                                <span class="font-normal text-slate-600">(maks. 5 foto, 5 MB / foto — foto besar otomatis dikompres)</span>
                            </label>
                            <div data-foto-upload data-maks="5" data-maks-ukuran="5120"
                                 data-tipe="image/jpeg,image/png,image/webp">
                                <input type="file" id="foto_perbaikan" name="foto_perbaikan[]" accept=".jpg,.jpeg,.png,.webp,.heic,.heif" multiple required
                                       class="input cursor-pointer file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700">
                                <ul data-foto-daftar class="mt-2 space-y-2"></ul>
                                <p data-foto-pesan class="mt-1 text-xs font-medium" aria-live="polite"></p>
                            </div>
                            @error('foto_perbaikan.*')
                                <p class="mt-1 text-xs font-medium text-danger-600">{{ $message }}</p>
                            @enderror
                            @error('foto_perbaikan')
                                <p class="mt-1 text-xs font-medium text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="keterangan_foto" class="label mb-1.5">
                                Keterangan foto
                                <span class="font-normal text-slate-600">(opsional, berlaku untuk semua foto di unggahan ini)</span>
                            </label>
                            <input type="text" id="keterangan_foto" name="keterangan" value="{{ old('keterangan') }}" maxlength="255"
                                   placeholder="Contoh: PJU blok C sudah diganti tegangan baru"
                                   class="input">
                            @error('keterangan')
                                <p class="mt-1 text-xs font-medium text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-button type="submit">
                            <x-icon name="unggah" class="h-4 w-4"/>
                            Unggah foto perbaikan
                        </x-button>
                    </form>
                @endif
            </x-card>
        </div>

        <div class="min-w-0 space-y-4">
            <x-card heading="Pelapor">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-600">Nama</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->nama_pelapor }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-600">Kontak</dt>
                        @if ($laporan->kontak_pelapor)
                            <dd class="mt-0.5 font-medium text-slate-800">{{ $laporan->kontak_pelapor }}</dd>
                        @else
                            <dd class="mt-0.5 text-slate-500">Tidak dicantumkan</dd>
                        @endif
                    </div>
                </dl>

                @if ($laporan->kontak_wa)
                    <x-button href="https://wa.me/{{ $laporan->kontak_wa }}" variant="secondary" class="mt-4 w-full" target="_blank" rel="noopener">
                        <x-icon name="telepon" class="h-4 w-4"/>
                        Hubungi via WhatsApp
                    </x-button>
                @endif
            </x-card>

            <x-card heading="Penanganan">
                <form action="{{ route('laporan.update', $laporan) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="label mb-1.5">
                            Status <span class="text-danger-600" aria-hidden="true">*</span><span class="sr-only">(wajib diisi)</span>
                        </label>
                        <select id="status" name="status" required class="input">
                            @foreach (\App\Models\Laporan::STATUS as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $laporan->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (config('laporan.hapus_otomatis'))
                            <p class="mt-1 text-xs text-slate-600">Laporan yang ditandai selesai akan dihapus otomatis {{ config('laporan.hapus_otomatis_hari') }} hari kemudian, beserta semua fotonya.</p>
                        @endif
                        <label class="mt-3 flex min-h-11 items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="jangan_hapus_otomatis" value="1" @checked(old('jangan_hapus_otomatis', $laporan->jangan_hapus_otomatis))
                                   class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                            Simpan permanen (jangan hapus otomatis)
                        </label>
                    </div>

                    <div>
                        <label for="catatan_petugas" class="label mb-1.5">Catatan tindak lanjut</label>
                        <textarea id="catatan_petugas" name="catatan_petugas" rows="5" maxlength="2000"
                                  placeholder="Contoh: Tim lapangan sudah turun pada 12 Oktober, reparasi PJU blok C selesai."
                                  class="input">{{ old('catatan_petugas', $laporan->catatan_petugas) }}</textarea>
                    </div>

                    <div>
                        <label for="deskripsi_aksi_selesai" class="label mb-1.5">
                            Detail aksi selesai <span class="font-normal text-slate-600">(opsional)</span>
                        </label>
                        <textarea id="deskripsi_aksi_selesai" name="deskripsi_aksi_selesai" rows="3" maxlength="2000"
                                  placeholder="Jelaskan kegiatan yang selesai dilakukan mengatasi kerusakan ini…"
                                  class="input">{{ old('deskripsi_aksi_selesai', $laporan->deskripsi_aksi_selesai) }}</textarea>
                    </div>

                    <x-button type="submit" class="w-full">
                        <x-icon name="simpan" class="h-4 w-4"/>
                        Simpan penanganan
                    </x-button>
                </form>
            </x-card>

            <x-card heading="Riwayat">
                <ul class="space-y-2 text-xs text-slate-600">
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

                <h3 class="mt-4 border-t border-garis pt-3 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Perubahan Status
                </h3>
                @if ($laporan->riwayats->isEmpty())
                    <p class="mt-2 text-xs">Belum ada perubahan status.</p>
                @else
                    <ol class="mt-3 space-y-3 text-xs">
                        @foreach ($laporan->riwayats as $riwayat)
                            <li class="border-l-2 border-brand-200 pl-3">
                                <p class="font-medium text-slate-700">
                                    {{ $riwayat->label_status_lama }} &rarr; {{ $riwayat->label_status_baru }}
                                </p>
                                <p class="mt-0.5 text-slate-600">
                                    {{ $riwayat->created_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB
                                    &middot; oleh
                                    <span class="font-medium text-slate-700">{{ $riwayat->user->name ?? 'Pengguna tidak dikenal' }}</span>
                                </p>
                                @if ($riwayat->catatan)
                                    <p class="mt-1 whitespace-pre-line text-slate-600">{{ $riwayat->catatan }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Pola konfirmasi hapus yang sama dengan halaman daftar: tombol memuat
            // data-confirm + data-action, lalu form DELETE dibuat dinamis.
            document.querySelectorAll('[data-confirm]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!window.confirm(button.dataset.confirm)) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = button.dataset.action;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
@endpush
