@extends('layouts.publik')

@section('title', 'Lapor — Kerusakan, Pengaduan, & Bangunan Liar')
@section('description', 'Laporkan kerusakan sarana dan prasarana perumahan, ajukan pengaduan, atau laporkan bangunan liar. Tanpa perlu akun, cukup isi form dan unggah foto bukti.')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-8">
            <nav class="text-xs text-slate-500">
                <a href="{{ route('publik.beranda') }}" class="hover:text-indigo-600">Beranda</a>
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
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-6">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-6 w-6 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold text-emerald-800">Terima kasih, laporan Anda sudah kami terima!</p>
                                @if (session('laporan_jenis'))
                                    <p class="mt-1 text-sm text-emerald-700">Jenis laporan: {{ session('laporan_jenis') }}.</p>
                                @endif
                                <p class="mt-1 text-sm text-emerald-700">Nomor tiket laporan Anda:</p>
                                <p class="mt-1 select-all rounded-lg border border-emerald-300 bg-white px-4 py-2 text-lg font-bold tracking-wide text-emerald-800">
                                    {{ session('laporan_kode') }}
                                </p>
                                <p class="mt-2 text-sm text-emerald-700">
                                    Petugas akan meninjau laporan ini. Simpan nomor tiket di atas bila ingin menanyakan progres penanganan.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success') && ! session('laporan_kode'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($perumahans->isEmpty() && $kelurahans->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                        Belum ada data perumahan maupun wilayah yang bisa dipilih. Laporan dapat dikirim setelah petugas
                        mendata wilayah terlebih dahulu.
                    </div>
                @else
                    <form id="form-lapor" action="{{ route('lapor.store') }}" method="POST" enctype="multipart/form-data"
                          class="space-y-6">
                        @csrf

                        {{-- 1. Jenis laporan --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">1. Pilih Jenis Laporan</h2>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach (\App\Models\Laporan::JENIS as $value => $label)
                                    <label class="jenis-kartu flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-indigo-500 has-[:checked]:ring-2 has-[:checked]:ring-indigo-500/30
                                                  {{ old('jenis', $jenisTerpilih ?? 'kerusakan') === $value ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-slate-300' }}">
                                        <input type="radio" name="jenis" value="{{ $value }}" required
                                               @checked(old('jenis', $jenisTerpilih ?? 'kerusakan') === $value)
                                               class="mt-0.5 h-4 w-4 text-indigo-600 focus:ring-indigo-500">
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
                                <div class="sm:col-span-2">
                                    <label for="pilih-perumahan" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Perumahan <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="pilih-perumahan" name="perumahan_id"
                                            data-old="{{ old('perumahan_id') }}"
                                            data-wajib-jenis="kerusakan"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <option value="">— Pilih perumahan —</option>
                                        @foreach ($perumahans as $item)
                                            <option value="{{ $item['id'] }}"
                                                    @selected((string) old('perumahan_id', $perumahanTerpilih) === (string) $item['id'])>
                                                {{ $item['nama'] }} — {{ $item['wilayah'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="pilih-sarana" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Sarana / prasarana yang rusak
                                    </label>
                                    <select id="pilih-sarana" name="psu_perumahan_id"
                                            data-old="{{ old('psu_perumahan_id') }}" disabled
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-100 disabled:text-slate-500">
                                        <option value="">— Pilih perumahan dahulu —</option>
                                    </select>
                                    <p id="sarana-kosong" class="mt-1 text-xs text-slate-500"></p>
                                </div>
                            </div>

                            {{-- Bagian khusus bangunan liar: kelurahan wajib + teks lokasi + tanggal kejadian --}}
                            <div class="panel-jenis mt-4 hidden grid gap-4 sm:grid-cols-2" data-panel="bangunan_liar">
                                <div>
                                    <label for="pilih-kelurahan" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Kelurahan <span class="text-rose-500">*</span>
                                    </label>
                                    <select id="pilih-kelurahan" name="kelurahan_id"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <option value="">— Pilih kelurahan —</option>
                                        @foreach ($kelurahans as $kel)
                                            <option value="{{ $kel['id'] }}" @selected((string) old('kelurahan_id') === (string) $kel['id'])>
                                                Kel. {{ $kel['nama'] }} — Kec. {{ $kel['kecamatan'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">Pilih juga perumahan di bawah bila lokasinya di dalam perumahan.</p>
                                </div>

                                <div>
                                    <label for="tanggal_kejadian" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Tanggal kejadian <span class="font-normal text-slate-500">(opsional)</span>
                                    </label>
                                    <input type="date" id="tanggal_kejadian" name="tanggal_kejadian"
                                           value="{{ old('tanggal_kejadian') }}"
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="lokasi_teks" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Lokasi kejadian <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="lokasi_teks" name="lokasi_teks"
                                           value="{{ old('lokasi_teks') }}" maxlength="255"
                                           placeholder="Contoh: Jl. Melati gang 3, di samping lapangan desa"
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="pilih-perumahan-bl" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Perumahan <span class="font-normal text-slate-500">(opsional)</span>
                                    </label>
                                    <select id="pilih-perumahan-bl" name="perumahan_id_bl"
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                        <option value="">— Di luar area perumahan —</option>
                                        @foreach ($perumahans as $item)
                                            <option value="{{ $item['id'] }}" @selected((string) old('perumahan_id') === (string) $item['id'])>
                                                {{ $item['nama'] }} — {{ $item['wilayah'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" id="perumahan-id-bersama" name="perumahan_id" value="{{ old('perumahan_id') }}">
                                </div>
                            </div>

                            {{-- Pengaduan: tanpa lokasi wajib --}}
                            <p class="panel-jenis mt-4 hidden rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500" data-panel="pengaduan">
                                Pengaduan tidak wajib menyebut lokasi. Bila berkaitan dengan perumahan tertentu,
                                sebutkan nama perumahannya di uraian laporan.
                            </p>
                        </div>

                        {{-- 3. Data pelapor --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">3. Data Pelapor</h2>

                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="nama_pelapor" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Nama Lengkap <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="nama_pelapor" name="nama_pelapor"
                                           value="{{ old('nama_pelapor') }}" maxlength="100" required
                                           placeholder="Contoh: Budi Santoso"
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                <div>
                                    <label for="kontak_pelapor" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        No. HP / WhatsApp <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="kontak_pelapor" name="kontak_pelapor"
                                           value="{{ old('kontak_pelapor') }}" maxlength="100" required
                                           placeholder="Contoh: 0812xxxxxxx"
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                    <p class="mt-1 text-xs text-slate-500">Dipakai petugas untuk menghubungi Anda terkait laporan ini.</p>
                                </div>
                            </div>
                        </div>

                        {{-- 4. Detail per jenis --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 class="text-sm font-semibold text-slate-900">4. Detail Laporan</h2>

                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label for="judul" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Judul Laporan <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="judul" name="judul" required
                                           value="{{ old('judul') }}" maxlength="150"
                                           placeholder="Contoh: Jalan blok C berlubang dan berbahaya"
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                </div>

                                {{-- Khusus kerusakan --}}
                                <div class="panel-jenis grid gap-4" data-panel="kerusakan">
                                    <div>
                                        <span class="mb-1.5 block text-sm font-medium text-slate-700">Tingkat Kerusakan <span class="text-rose-500">*</span></span>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            @foreach (\App\Models\Laporan::TINGKAT as $value => $label)
                                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50
                                                              {{ old('tingkat') === $value ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300' }}">
                                                    <input type="radio" name="tingkat" value="{{ $value }}"
                                                           @checked(old('tingkat') === $value)
                                                           class="mt-0.5 h-4 w-4 text-indigo-600 focus:ring-indigo-500">
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
                                            <div>
                                                <label for="persentase_kerusakan" class="mb-1.5 block text-xs text-slate-500">Persentase kerusakan</label>
                                                <div class="relative">
                                                    <input type="number" id="persentase_kerusakan" name="persentase_kerusakan"
                                                           min="0" max="100" step="any"
                                                           value="{{ old('persentase_kerusakan') }}" placeholder="Contoh: 45"
                                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 pr-8 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                                                </div>
                                            </div>

                                            <div>
                                                <label for="panjang_kerusakan" class="mb-1.5 block text-xs text-slate-500">Panjang kerusakan (meter)</label>
                                                <input type="number" id="panjang_kerusakan" name="panjang_kerusakan"
                                                       min="0" step="any"
                                                       value="{{ old('panjang_kerusakan') }}" placeholder="Contoh: 12,5"
                                                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Isi yang relevan saja — misal jalan rusak: panjang &amp; persentase; lampu PJU mati: cukup dikosongkan.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label for="deskripsi" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Uraian <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea id="deskripsi" name="deskripsi" required rows="5" maxlength="2000"
                                              placeholder="Jelaskan kondisi/kronologi laporan, perkiraan lokasi (blok/RT/RW), dan sejak kapan terjadi…"
                                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">{{ old('deskripsi') }}</textarea>
                                </div>

                                <div>
                                    <label for="foto" class="mb-1.5 block text-sm font-medium text-slate-700">
                                        Foto Bukti <span id="foto-keterangan" class="font-normal text-slate-500">(opsional, maks. 3 foto)</span>
                                    </label>
                                    <input type="file" id="foto" name="foto[]" accept=".jpg,.jpeg,.png,.webp" multiple
                                           class="w-full cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-indigo-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-indigo-700">
                                    <p id="foto-jumlah" class="mt-1 hidden text-xs font-medium text-indigo-600"></p>
                                    <p id="foto-petunjuk" class="mt-1 text-xs text-slate-500">Format JPG / PNG / WEBP, masing-masing maksimal 2 MB.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <a href="{{ route('publik.beranda') }}"
                               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                Batal
                            </a>
                            <button type="submit"
                                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                Kirim Laporan
                            </button>
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
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ $i + 1 }}</span>
                                <span class="text-sm text-slate-600">{{ $langkah }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Setelah Dilaporkan</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        @foreach ([
                            'Baru' => ['bg-amber-100 text-amber-700', 'Laporan diterima dan menunggu peninjauan petugas.'],
                            'Diproses' => ['bg-sky-100 text-sky-700', 'Petugas sedang menangani laporan.'],
                            'Selesai' => ['bg-emerald-100 text-emerald-700', 'Laporan telah ditangani.'],
                        ] as $status => [$warna, $penjelasan])
                            <li>
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $warna }}">{{ $status }}</span>
                                <span class="mt-1 block text-slate-600">{{ $penjelasan }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 text-xs text-slate-500 shadow-sm">
                    Laporan dikirim tanpa perlu membuat akun. Nama dan kontak hanya digunakan untuk
                    tindak lanjut laporan dan tidak ditampilkan pada halaman publik.
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const data = @json($perumahans);
            const form = document.getElementById('form-lapor');

            const selPerumahan = document.getElementById('pilih-perumahan');
            const selSarana = document.getElementById('pilih-sarana');
            const infoSarana = document.getElementById('sarana-kosong');

            const selPerumahanBl = document.getElementById('pilih-perumahan-bl');
            const perumahanIdBersama = document.getElementById('perumahan-id-bersama');

            const inputFoto = document.getElementById('foto');
            const jumlahFoto = document.getElementById('foto-jumlah');
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

            if (inputFoto) {
                inputFoto.addEventListener('change', () => {
                    const jumlah = inputFoto.files.length;
                    jumlahFoto.textContent = jumlah > 0 ? jumlah + ' foto dipilih' : '';
                    jumlahFoto.classList.toggle('hidden', jumlah === 0);
                });
            }

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
