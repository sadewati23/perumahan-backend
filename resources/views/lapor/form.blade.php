@extends('layouts.publik')

@section('title', 'Lapor — Kerusakan, Pengaduan, & Bangunan Liar')
@section('description', 'Laporkan kerusakan sarana dan prasarana perumahan, ajukan pengaduan, atau laporkan bangunan liar. Tanpa perlu akun, cukup isi form dan unggah foto bukti.')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-500">
                <a href="{{ route('publik.beranda') }}" class="hover:text-brand-600">Beranda</a>
                <span class="mx-1">/</span>
                <span class="text-slate-700">Lapor</span>
            </nav>

            <h1 class="mt-3 text-2xl font-semibold text-slate-900">Lapor Kerusakan, Pengaduan, &amp; Bangunan Liar</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">
                Menemukan jalan berlubang, lampu jalan mati, ingin mengadu, atau melihat bangunan tanpa izin?
                Laporkan di sini. Tidak perlu akun — laporan Anda akan ditindaklanjuti oleh petugas.
            </p>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if (session('laporan_kode'))
                    <div class="mb-6 rounded-xl border border-success-200 bg-success-50 p-6">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-6 w-6 shrink-0 text-success-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-success-800">Terima kasih, laporan Anda sudah kami terima!</p>
                                @if (session('laporan_jenis'))
                                    <p class="mt-1 text-sm text-success-700">Jenis laporan: {{ session('laporan_jenis') }}.</p>
                                @endif
                                <p class="mt-1 text-sm text-success-700">Nomor tiket laporan Anda:</p>
                                <p class="mt-1 select-all rounded-lg border border-success-300 bg-white px-4 py-2 text-lg font-bold tracking-wide text-success-800">
                                    {{ session('laporan_kode') }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button type="button" data-copy="{{ session('laporan_kode') }}"
                                            class="rounded-control border border-success-300 bg-white px-3 py-2 text-xs font-semibold text-success-800 transition hover:bg-success-50">
                                        Salin nomor tiket
                                    </button>
                                    <p class="text-xs text-success-700" data-copy-status aria-live="polite"></p>
                                </div>
                                <p class="mt-2 text-sm text-success-700">
                                    Simpan tangkapan layar atau catat nomor tiket ini. Gunakan nomor tiket bila ingin menanyakan progres penanganan.
                                </p>
                                <ol class="mt-3 space-y-1 text-sm text-success-700">
                                    <li>1. Petugas akan meninjau laporan Anda.</li>
                                    <li>2. Status laporan berubah menjadi “Diproses” saat ditangani.</li>
                                    <li>3. Laporan “Selesai” bila tindak lanjut sudah selesai.</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success') && ! session('laporan_kode'))
                    <div class="mb-6 rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-800">
                        <p class="font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($perumahans->isEmpty() && $kelurahans->isEmpty())
                    <div class="rounded-xl border border-warning-200 bg-warning-50 p-6 text-sm text-warning-800">
                        Belum ada data perumahan maupun wilayah yang bisa dipilih. Laporan dapat dikirim setelah petugas
                        mendata wilayah terlebih dahulu.
                    </div>
                @else
                    <form id="form-lapor" action="{{ route('lapor.store') }}" method="POST" enctype="multipart/form-data"
                          class="space-y-6">
                        @csrf
                        {{-- Honeypot: field palsu di luar layar — manusia tidak melihat & tidak mengisi;
                             bot yang mengisi otomatis semua input akan menandai dirinya sendiri. --}}
                        <input type="text" name="jawaban_cadangan" value=""
                               style="position:absolute;left:-9999px"
                               aria-hidden="true" tabindex="-1" autocomplete="off">
                        {{-- 1. Jenis laporan --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">1. Pilih Jenis Laporan</h2>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach (\App\Models\Laporan::JENIS as $value => $label)
                                    <label class="jenis-kartu flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-brand-500 has-[:checked]:ring-2 has-[:checked]:ring-brand-500/30
                                                  {{ old('jenis', $jenisTerpilih ?? 'kerusakan') === $value ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-slate-300' }}">
                                        <input type="radio" name="jenis" value="{{ $value }}" required
                                               @checked(old('jenis', $jenisTerpilih ?? 'kerusakan') === $value)
                                               class="mt-0.5 h-4 w-4 text-brand-600 focus:ring-brand-500">
                                        <span>
                                            <span class="block text-sm font-medium text-slate-800">{{ $label }}</span>
                                            <span class="mt-0.5 block text-xs text-slate-500">
                                                @if ($value === 'kerusakan')
                                                    Jalan, drainase, PJU, air bersih, dll. yang rusak.
                                                @elseif ($value === 'pengaduan')
                                                    Aduan, usulan, atau saran — cukup teks, foto opsional.
                                                @else
                                                    Bangunan tanpa izin; wajib foto &amp; lokasi, perumahan opsional.
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- 2. Lokasi — tampilan dinamis per jenis --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">2. Lokasi</h2>

                            {{-- Bagian khusus kerusakan: perumahan + PSU --}}
                            <div class="panel-jenis mt-4 grid gap-4 sm:grid-cols-2" data-panel="kerusakan">
                                <x-field name="perumahan_id" for="pilih-perumahan" label="Perumahan" required class="sm:col-span-2">
                                    <select id="pilih-perumahan" name="perumahan_id"
                                            data-old="{{ old('perumahan_id') }}"
                                            data-wajib-jenis="kerusakan"
                                            class="input">
                                        <option value="">— Pilih perumahan —</option>
                                        @foreach ($perumahans as $item)
                                            <option value="{{ $item['id'] }}"
                                                    @selected((string) old('perumahan_id', $perumahanTerpilih) === (string) $item['id'])>
                                                {{ $item['nama'] }} — {{ $item['wilayah'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-field>

                                <x-field name="psu_perumahan_id" for="pilih-sarana" label="Sarana / prasarana yang rusak" optional class="sm:col-span-2">
                                    <select id="pilih-sarana" name="psu_perumahan_id"
                                            data-old="{{ old('psu_perumahan_id') }}" disabled
                                            class="input">
                                        <option value="">— Pilih perumahan dahulu —</option>
                                    </select>
                                    <p id="sarana-kosong" class="mt-1 text-xs text-slate-500"></p>
                                </x-field>
                            </div>

                            {{-- Bagian khusus bangunan liar: kelurahan wajib + teks lokasi + tanggal kejadian --}}
                            <div class="panel-jenis mt-4 hidden grid gap-4 sm:grid-cols-2" data-panel="bangunan_liar">
                                <x-field name="kelurahan_id" for="pilih-kelurahan" label="Kelurahan" required>
                                    <select id="pilih-kelurahan" name="kelurahan_id" class="input">
                                        <option value="">— Pilih kelurahan —</option>
                                        @foreach ($kelurahans as $kel)
                                            <option value="{{ $kel['id'] }}" @selected((string) old('kelurahan_id') === (string) $kel['id'])>
                                                Kel. {{ $kel['nama'] }} — Kec. {{ $kel['kecamatan'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-field>

                                <x-field name="tanggal_kejadian" for="tanggal_kejadian" label="Tanggal kejadian" optional>
                                    <input type="date" id="tanggal_kejadian" name="tanggal_kejadian"
                                           value="{{ old('tanggal_kejadian') }}" class="input">
                                </x-field>

                                <x-field name="lokasi_teks" for="lokasi_teks" label="Lokasi kejadian" required class="sm:col-span-2">
                                    <input type="text" id="lokasi_teks" name="lokasi_teks"
                                           value="{{ old('lokasi_teks') }}" maxlength="255" autocomplete="off"
                                           enterkeyhint="next"
                                           placeholder="Contoh: Jl. Melati gang 3, di samping lapangan desa"
                                           class="input">
                                </x-field>

                                <x-field name="perumahan_id" for="pilih-perumahan-bl" label="Perumahan" optional showErrors="false" class="sm:col-span-2">
                                    <select id="pilih-perumahan-bl" name="perumahan_id_bl" class="input">
                                        <option value="">— Di luar area perumahan —</option>
                                        @foreach ($perumahans as $item)
                                            <option value="{{ $item['id'] }}" @selected((string) old('perumahan_id') === (string) $item['id'])>
                                                {{ $item['nama'] }} — {{ $item['wilayah'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-field>
                                <input type="hidden" id="perumahan-id-bersama" name="perumahan_id" value="{{ old('perumahan_id') }}">
                            </div>

                            {{-- Pengaduan: tanpa lokasi wajib --}}
                            <p class="panel-jenis mt-4 hidden rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500" data-panel="pengaduan">
                                Pengaduan tidak wajib menyebut lokasi. Bila berkaitan dengan perumahan tertentu,
                                sebutkan nama perumahannya di uraian laporan.
                            </p>

                            {{-- Titik GPS opsional: berlaku untuk semua jenis, di luar panel --}}
                            {{-- jenis agar tidak ikut disabled oleh toggle panel. --}}
                            <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700">
                                            Titik lokasi di peta <span class="font-normal text-slate-500">(opsional — membantu petugas menemukan titiknya)</span>
                                        </p>
                                        <p id="peta-keterangan" class="mt-0.5 text-xs text-slate-500">
                                            Belum ada titik — klik langsung di peta untuk menandai lokasi.
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button" id="peta-posisi-saya"
                                                class="min-h-11 rounded-control border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 md:min-h-10">
                                            Gunakan posisi saya
                                        </button>
                                        <button type="button" id="peta-hapus" class="hidden min-h-11 rounded-control border border-danger-200 bg-white px-3 py-1.5 text-xs font-medium text-danger-600 transition hover:bg-danger-50 md:min-h-10">
                                            Hapus titik
                                        </button>
                                    </div>
                                </div>

                                <div id="peta-pemilih" class="mt-3 z-0 h-64 w-full rounded-lg border border-slate-200 bg-slate-100"
                                     aria-label="Peta pemilih titik lokasi"></div>

                                <input type="hidden" id="peta-latitude" name="latitude" value="{{ old('latitude') }}">
                                <input type="hidden" id="peta-longitude" name="longitude" value="{{ old('longitude') }}">

                                @error('latitude')
                                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- 3. Data pelapor --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">3. Data Pelapor</h2>

                            <div class="mt-4 grid gap-4">
                                <x-field name="nama_pelapor" for="nama_pelapor" label="Nama Lengkap"
                                         required hint="Nama seperti pada KTP; hanya dipakai untuk tindak lanjut laporan.">
                                    <input type="text" id="nama_pelapor" name="nama_pelapor"
                                           value="{{ old('nama_pelapor') }}" maxlength="100" required
                                           autocomplete="name" enterkeyhint="next"
                                           placeholder="Contoh: Budi Santoso"
                                           class="input">
                                </x-field>
                            </div>
                        </div>

                        {{-- 4. Detail per jenis --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">4. Detail Laporan</h2>

                            <div class="mt-4 grid gap-4">
                                <x-field name="judul" for="judul" label="Judul Laporan" required>
                                    <input type="text" id="judul" name="judul" required
                                           value="{{ old('judul') }}" maxlength="150" autocomplete="off"
                                           enterkeyhint="next"
                                           placeholder="Contoh: Jalan blok C berlubang dan berbahaya"
                                           class="input">
                                </x-field>

                                {{-- Khusus kerusakan --}}
                                <div class="panel-jenis grid gap-4" data-panel="kerusakan">
                                    <div>
                                        <span class="mb-1.5 block text-sm font-medium text-slate-700">Tingkat Kerusakan <span class="text-danger-600">*</span></span>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (\App\Models\Laporan::TINGKAT as $value => $label)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50
                                                              {{ old('tingkat') === $value ? 'border-brand-500 bg-brand-50' : 'border-slate-300' }}">
                                                    <input type="radio" name="tingkat" value="{{ $value }}"
                                                           @checked(old('tingkat') === $value)
                                                           class="mt-0.5 h-4 w-4 text-brand-600 focus:ring-brand-500">
                                                    <span>
                                                        <span class="block text-sm font-medium text-slate-800">{{ $label }}</span>
                                                        <span class="block text-xs text-slate-500">
                                                            {{ $value === 'rusak_ringan' ? 'Masih dapat dilewati / digunakan dengan hati-hati.' : 'Berbahaya atau tidak dapat digunakan sama sekali.' }}
                                                        </span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div>
                                        <span class="mb-1.5 block text-sm font-medium text-slate-700">
                                            Ukuran Kerusakan <span class="font-normal text-slate-500">(opsional)</span>
                                        </span>

                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <x-field name="persentase_kerusakan" for="persentase_kerusakan" label="Persentase kerusakan" optional>
                                                <div class="relative">
                                                    <input type="number" id="persentase_kerusakan" name="persentase_kerusakan"
                                                           inputmode="decimal" autocomplete="off"
                                                           min="0" max="100" step="any"
                                                           value="{{ old('persentase_kerusakan') }}" placeholder="Contoh: 45"
                                                           class="input pr-8">
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                                                </div>
                                            </x-field>

                                            <x-field name="panjang_kerusakan" for="panjang_kerusakan" label="Panjang kerusakan (meter)" optional>
                                                <input type="number" id="panjang_kerusakan" name="panjang_kerusakan"
                                                       inputmode="decimal" autocomplete="off"
                                                       min="0" step="any"
                                                       value="{{ old('panjang_kerusakan') }}" placeholder="Contoh: 12,5"
                                                       class="input">
                                            </x-field>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Isi yang relevan saja — misal jalan rusak: panjang &amp; persentase; lampu PJU mati: cukup dikosongkan.
                                        </p>
                                    </div>
                                </div>

                                <x-field name="deskripsi" for="deskripsi" label="Uraian" required
                                         hint="Jelaskan kronologi, perkiraan lokasi (blok/RT/RW), dan sejak kapan terjadi.">
                                    <textarea id="deskripsi" name="deskripsi" required rows="5" maxlength="2000"
                                              enterkeyhint="enter"
                                              placeholder="Jelaskan kondisi/kronologi laporan, perkiraan lokasi (blok/RT/RW), dan sejak kapan terjadi…"
                                              class="input">{{ old('deskripsi') }}</textarea>
                                </x-field>

                                <x-field name="foto" for="foto" label="Foto Bukti" hint="JPG / PNG / WEBP, maksimal 2 MB per foto.">
                                    <span id="foto-keterangan" class="font-normal text-slate-500">(opsional, maks. 3 foto)</span>

                                    <div id="foto-upload" data-foto-upload data-maks="3" data-maks-ukuran="2048"
                                         data-min="1" data-min-jenis="bangunan_liar" data-tipe="image/jpeg,image/png,image/webp">
                                        <button type="button" data-foto-buka
                                                class="flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-brand-50">
                                            <svg class="h-6 w-6 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4"/>
                                            </svg>
                                            <span class="block text-sm font-medium text-slate-700">Pilih foto atau seret ke sini</span>
                                            <span class="block text-xs text-slate-500">Bisa beberapa file sekaligus. Format JPG / PNG / WEBP, maksimal {{ (int) round(2048/1024) }} MB per foto.</span>
                                        </button>

                                        <input type="file" id="foto" name="foto[]" multiple accept=".jpg,.jpeg,.png,.webp"
                                               class="hidden" aria-describedby="foto-petunjuk">

                                        <ul id="foto-daftar" data-foto-daftar class="mt-2 space-y-2"></ul>

                                        <p id="foto-pesan" data-foto-pesan class="mt-1 text-xs font-medium" aria-live="polite"></p>
                                    </div>

                                    <p id="foto-petunjuk" class="mt-1 text-xs text-slate-500">Format JPG / PNG / WEBP, masing-masing maksimal 2 MB.</p>
                                </x-field>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <x-button variant="secondary" href="{{ route('publik.beranda') }}">Batal</x-button>
                        @if (config('services.turnstile.site_key'))
                            <div>
                                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                                @error('cf-turnstile-response')
                                    <p class="mt-1 text-xs text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                            <x-button type="submit">Kirim Laporan</x-button>
                        </div>
                    </form>
                @endif
            </div>

            <aside class="space-y-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Cara Melapor</h2>
                    <ol class="mt-4 space-y-4">
                        @foreach ([
                            'Pilih jenis laporan: kerusakan, pengaduan, atau bangunan liar.',
                            'Isi lokasi sesuai jenis laporan yang dipilih.',
                            'Isi identitas, judul, dan uraian laporan.',
                            'Lampirkan foto bukti — wajib untuk laporan bangunan liar.',
                            'Kirim laporan, lalu simpan nomor tiket yang muncul.',
                        ] as $i => $langkah)
                            <li class="flex items-start gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-pill bg-brand-100 text-xs font-bold text-brand-700">{{ $i + 1 }}</span>
                                <span class="text-sm text-slate-600">{{ $langkah }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Setelah Dilaporkan</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            'Baru' => ['bg-warning-100 text-warning-700 ring-warning-200', 'Laporan diterima dan menunggu peninjauan petugas.'],
                            'Diproses' => ['bg-info-100 text-info-700 ring-info-200', 'Petugas sedang menangani laporan.'],
                            'Selesai' => ['bg-success-100 text-success-700 ring-success-200', 'Laporan telah ditangani.'],
                        ] as $status => [$warna, $penjelasan])
                            <li>
                                <span class="inline-flex rounded-pill px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $warna }}">{{ $status }}</span>
                                <span class="mt-1 block text-slate-600">{{ $penjelasan }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 text-xs text-slate-500 shadow-sm">
                    Laporan dikirim tanpa perlu membuat akun. Nama pelapor hanya digunakan untuk
                    tindak lanjut laporan dan tidak ditampilkan pada halaman publik.
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    <script>
        // --- Pemilih titik lokasi (Leaflet): klik peta / gunakan posisi perangkat. ---
        document.addEventListener('DOMContentLoaded', () => {
            const petaEl = document.getElementById('peta-pemilih');
            if (!petaEl || typeof window.L === 'undefined') return;

            const PETA = @json(config('peta'));
            const latInput = document.getElementById('peta-latitude');
            const lngInput = document.getElementById('peta-longitude');
            const keterangan = document.getElementById('peta-keterangan');
            const tombolHapus = document.getElementById('peta-hapus');

            const terbaca = () => {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                return Number.isFinite(lat) && Number.isFinite(lng) ? [lat, lng] : null;
            };

            const titikAwal = terbaca();
            const map = L.map(petaEl, { scrollWheelZoom: true }).setView(
                titikAwal ?? [PETA.latitude, PETA.longitude],
                titikAwal ? PETA.zoom_detail : PETA.zoom,
            );

            L.tileLayer(PETA.tile_url, { maxZoom: 19, attribution: PETA.tile_attribution }).addTo(map);

            let marker = titikAwal ? L.marker(titikAwal).addTo(map) : null;
            const segarkanKeterangan = () => {
                const titik = terbaca();
                if (titik) {
                    keterangan.textContent = 'Titik terpasang: ' + titik[0].toFixed(6) + ', ' + titik[1].toFixed(6);
                    tombolHapus.classList.remove('hidden');
                } else {
                    keterangan.textContent = 'Belum ada titik — klik langsung di peta untuk menandai lokasi.';
                    tombolHapus.classList.add('hidden');
                }
            };

            const setTitik = (lat, lng) => {
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                if (marker) marker.setLatLng([lat, lng]);
                else marker = L.marker([lat, lng]).addTo(map);
                segarkanKeterangan();
            };

            map.on('click', (lokasi) => setTitik(lokasi.latlng.lat, lokasi.latlng.lng));

            tombolHapus.addEventListener('click', () => {
                latInput.value = '';
                lngInput.value = '';
                if (marker) { map.removeLayer(marker); marker = null; }
                segarkanKeterangan();
            });

            // Ambil GPS perangkat (perlu izin; hanya berfungsi di HTTPS/localhost).
            document.getElementById('peta-posisi-saya').addEventListener('click', () => {
                if (!navigator.geolocation) {
                    keterangan.textContent = 'Perangkat tidak mendukung pengambilan posisi — silakan klik peta.';
                    return;
                }
                keterangan.textContent = 'Mengambil posisi GPS…';
                navigator.geolocation.getCurrentPosition(
                    (posisi) => {
                        setTitik(posisi.coords.latitude, posisi.coords.longitude);
                        map.setView([posisi.coords.latitude, posisi.coords.longitude], Math.max(map.getZoom(), PETA.zoom_detail));
                    },
                    () => { keterangan.textContent = 'Gagal mengambil posisi — silakan klik langsung di peta.'; },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
                );
            });

            segarkanKeterangan();
        });
    </script>
    @if (config('services.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = @json($perumahans);
            const form = document.getElementById('form-lapor');

            const selPerumahan = document.getElementById('pilih-perumahan');
            const selSarana = document.getElementById('pilih-sarana');
            const infoSarana = document.getElementById('sarana-kosong');

            const selPerumahanBl = document.getElementById('pilih-perumahan-bl');
            const perumahanIdBersama = document.getElementById('perumahan-id-bersama');

            const keteranganFoto = document.getElementById('foto-keterangan');
            const petunjukFoto = document.getElementById('foto-petunjuk');

            function jenisAktif() {
                const pilihan = form.querySelector('input[name="jenis"]:checked');
                return pilihan ? pilihan.value : 'kerusakan';
            }

            function renderJenis() {
                const jenis = jenisAktif();

                form.querySelectorAll('.panel-jenis').forEach((panel) => {
                    const cocok = panel.dataset.panel === jenis;
                    panel.classList.toggle('hidden', !cocok);

                    // Input pada panel tersembunyi dinonaktifkan agar tidak ikut terkirim
                    // (mencegah dua field perumahan_id saling menimpa saat submit).
                    panel.querySelectorAll('input, select, textarea').forEach((el) => {
                        el.disabled = !cocok;
                    });
                });

                // Sinkronkan nilai perumahan bersama dari kontrol panel yang aktif.
                perumahanIdBersama.value = jenis === 'bangunan_liar' ? selPerumahanBl.value : selPerumahan.value;

                // Keterangan & wajib-tidaknya foto per jenis.
                if (keteranganFoto && petunjukFoto) {
                    const wajib = jenis === 'bangunan_liar';
                    keteranganFoto.textContent = wajib ? '(wajib minimal 1 foto, maks. 3)' : '(opsional, maks. 3 foto)';
                    petunjukFoto.textContent = wajib
                        ? 'Wajib lampirkan foto bangunan tersebut. Format JPG / PNG / WEBP, masing-masing maksimal 2 MB.'
                        : 'Format JPG / PNG / WEBP, masing-masing maksimal 2 MB.';
                }
            }

            function renderSarana() {
                const item = data.find((d) => String(d.id) === String(selPerumahan.value));
                const terpilihSebelumnya = selSarana.dataset.old;

                selSarana.innerHTML = '';
                infoSarana.textContent = '';

                if (!item) {
                    selSarana.disabled = true;
                    selSarana.appendChild(new Option('— Pilih perumahan dahulu —', ''));
                    return;
                }

                selSarana.disabled = false;
                selSarana.appendChild(new Option('— Umum / tidak spesifik —', ''));

                if (item.psu.length === 0) {
                    infoSarana.textContent = 'Belum ada data PSU untuk perumahan ini — pilih "Umum" dan jelaskan lokasinya di uraian.';
                } else {
                    item.psu.forEach((s) => selSarana.appendChild(new Option(s.jenis, s.id)));
                }

                if (terpilihSebelumnya && [...selSarana.options].some((o) => o.value === terpilihSebelumnya)) {
                    selSarana.value = terpilihSebelumnya;
                }
            }

            selPerumahan.addEventListener('change', () => {
                selSarana.dataset.old = '';
                renderSarana();
            });

            // Satu sumber nilai perumahan: dropdown khusus BL menyalin ke input bersama.
            selPerumahanBl.addEventListener('change', () => {
                if (jenisAktif() === 'bangunan_liar') {
                    perumahanIdBersama.value = selPerumahanBl.value;
                }
            });

            form.querySelectorAll('input[name="jenis"]').forEach((radio) => {
                radio.addEventListener('change', () => {
                    renderJenis();

                    // Wajib-tidaknya perumahan mengikuti jenis: hanya kerusakan yang wajib.
                    selPerumahan.required = jenisAktif() === 'kerusakan';
                });
            });

            renderJenis();
            selPerumahan.required = jenisAktif() === 'kerusakan';
            renderSarana();
        });
    </script>
@endpush
