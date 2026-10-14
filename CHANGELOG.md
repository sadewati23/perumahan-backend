# Changelog Sistem Informasi Data Perumahan

Dokumentasi riwayat pekerjaan pada proyek ini, mencatat setiap perubahan fitur yang dilakukan beserta alasannya.

---

## Ringkasan Isu

| Tanggal | Fitur | Ringkasan |
|---|---|---|
| 2026-09-30 | Dropdown & filter kolom harga | Dropdown rentang harga di halaman daftar + perubahan input harga di form (datalist → dropdown → dihapus) |
| 2026-09-30 | Penghapusan total fitur harga | Harga dihapus dari form, filter, tampilan, dan database |
| 2026-09-30 | Pembersihan fitur polygon & soft delete | Migration `batas_polygon` & `deleted_at` dibersihkan bersamaan |
| 2026-09-30 | Commit & push | Semua perubahan di-commit sebagai `d147957` dan di-push ke `origin/main` |
| 2026-09-30 | Reset database | `migrate:fresh --seed` agar data contoh sesuai skema baru |
| 2026-10-05 | Fitur laporan kerusakan | Warga bisa melaporkan kerusakan sarana-prasarana tanpa login: form publik, foto bukti, nomor tiket, kelola di admin |
| 2026-10-05 | Ukuran kerusakan pada laporan | Laporan kini mencatat persentase kerusakan (%) dan panjang kerusakan (meter), opsional |
| 2026-10-06 | 3 jenis laporan warga | Laporan kini mencakup kerusakan, pengaduan teks (foto opsional), dan bangunan liar (foto wajib, kelurahan wajib, tanggal kejadian) dengan kode tiket berbeda (LPR/PGN/BLR) |
| 2026-10-06 | Penghapusan nomor HP/WA dari form lapor | Kolom kontak HP/WA tidak lagi diminta saat warga membuat laporan; kolom tetap ada di DB (nullable) untuk data lama |
| 2026-10-06 | Pembersihan tabel & storage | Tabel `personal_access_tokens` & `password_reset_tokens` dihapus (tidak dipakai); 3 file foto yatim dibersihkan dari storage |
| 2026-10-06 | Fitur lupa password | Admin/petugas yang lupa password bisa meminta tautan reset via email dengan token sekali pakai (tabel `password_reset_tokens` dipulihkan) |
| 2026-10-06 | Perbaikan 4 temuan review | Throttle login anti brute-force, sinkronisasi PSU/tipe unit tanpa delete-all, guard hapus kecamatan berkelurahan, dan pembersihan route API |
| 2026-10-06 | Perbaikan Stored XSS popup peta | Nilai perumahan (nama/alamat/status) yang diinterpolasi ke HTML popup Leaflet kini di-escape di 3 halaman peta (penemuan user) |
| 2026-10-12 | SRI pada CDN Leaflet & Chart.js | Tag CDN di 8 view kini pakai `integrity` + `crossorigin` agar browser memverifikasi checksum file eksternal (penemuan user) |

---

## 1. Dropdown & Filter Kolom Harga

**Status akhir: diganti/dihapus pada pekerjaan berikutnya (lihat bagian 2 & 3).**

Permintaan awal: "lanjutkan drop down kolom harga". Setelah konfirmasi, disepakati:
- **Filter rentang harga dinamis** di halaman daftar admin (`/admin/perumahan`), rentang dihitung otomatis dari data harga tipe unit yang tersimpan.
- **Datalist saran harga** di form tipe unit (input tetap bebas, muncul saran harga terdata).

### Perubahan

**`app/Models/Perumahan.php`**
- `scopeFilterHarga()` — filter perumahan berdasarkan rentang harga tipe unit (format `min-maks` atau `min-` untuk tanpa batas atas), dijalankan via `whereHas('tipeUnits')`.
- `rentangHargaOptions()` — menghasilkan pilihan rentang dinamis (langkah 25 jt / 50 jt / 100 jt / 250 jt / 500 jt menyesuaikan harga tertinggi data) dengan label ringkas.
- `formatSingkatHarga()` — formatter label: `250000000` → `250 jt`, `1.350.000.000` → `1,4 M`.
- `scopeFilter()` diperluas menerima `$filters['harga']`.

**`app/Http/Controllers/PerumahanController.php`**
- `index()` menerima filter `harga`, mengirim `$rentangHarga` dan `$hargaUnik` ke view.

**`resources/views/perumahan/index.blade.php`**
- Dropdown "Rentang harga" ditambahkan di form filter.

**`resources/views/perumahan/_form.blade.php`**
- Input harga diberi atribut `list="saran-harga"` + elemen `<datalist id="saran-harga">` berisi harga unik terdata.

---

## 2. Dropdown Harga pada Form Input Admin

**Status akhir: dihapus pada pekerjaan berikutnya (lihat bagian 3).**

Permintaan lanjutan: dropdown juga untuk form inputan harga di admin. Kolom Harga (Rp) di form Tipe Unit diubah dari input number biasa menjadi **dropdown pilihan harga terdata + opsi "Lainnya..."**.

### Perilaku

- Dropdown berisi harga-harga unik yang pernah diinput (format `Rp 205.000.000`).
- Memilih **"Lainnya..."** memunculkan input manual di bawahnya untuk harga baru.
- Data lama dengan harga yang tidak ada di daftar otomatis memilih "Lainnya..." dan menampilkan nilainya (tidak ada data yang hilang saat edit).
- Baris dinamis "+ Tambah Tipe" ikut memakai dropdown.
- Keamanan: nilai dropdown divalidasi **di sisi server** terhadap daftar harga terdata; nilai hasil manipulasi diabaikan.

### Perubahan

**`app/Models/Perumahan.php`**
- `daftarHargaUnik()` — daftar harga unik ternormalisasi (tanpa nol desimal) untuk pilihan dropdown.

**`app/Http/Controllers/PerumahanController.php`**
- Validasi baru `tipe_units.*.harga_pilih`.
- `syncDetail()` memakai harga dari dropdown hanya jika nilainya ada di daftar harga terdata; jika tidak, fallback ke input manual.

**`resources/views/perumahan/_form.blade.php`**
- `<select data-harga-select name="...[harga_pilih]">` + input manual `data-harga-manual` (tersembunyi secara default).

**`resources/views/perumahan/_form-scripts.blade.php`**
- Logika toggle via event delegation: pilih harga → input manual terisi otomatis & tersembunyi; pilih "Lainnya..." → input manual muncul dan fokus.

---

## 3. Penghapusan Total Fitur Harga

**Status: selesai & ter-commit.**

Permintaan akhir: "hapus saja form harga". Setelah konfirmasi, disepakati menghapus **semua** fitur harga dan harga lama dibiarkan kosong (tidak dipindahkan).

### Yang dihapus

| Area | Perubahan |
|---|---|
| Database | Migration `2026_09_30_100000_drop_harga_from_tipe_units_table` — drop kolom `harga` dari `tipe_units` (dengan rollback di `down()`) |
| Form admin | Kolom Harga (Rp) hilang dari tabel Tipe Unit, termasuk baris dinamis |
| Halaman daftar admin | Dropdown filter rentang harga dihapus |
| Detail admin & publik | Kolom Harga dihilangkan dari tabel tipe unit (`perumahan/show` & `publik/show`) |
| Model | `filterHarga()`, `rentangHargaOptions()`, `daftarHargaUnik()`, `formatSingkatHarga()` dihapus; cast `harga` dibersihkan dari `TipeUnit` |
| Controller | Validasi `tipe_units.*.harga` & `harga_pilih` dihapus; `syncDetail()` tidak lagi menyimpan harga |
| Seeder | Semua `'harga' => ...` dihapus dari `PerumahanSeeder` |
| Test | 3 test terkait harga dihapus dari `PerumahanTest`; data harga di `PublikTest` dibersihkan |
| JS | Logika toggle dropdown/manual harga dihapus dari `_form-scripts` |
| README | Struktur data & catatan teknis diperbarui |

### Verifikasi
- `php artisan migrate` ✅
- 20 test lulus (91 assertions) ✅
- Tidak ada referensi `harga` tersisa di kode aplikasi (sisa hanya di file migration create/drop yang historis)

---

## 4. Commit & Push

Semua perubahan di-commit sekaligus (sesuai konfirmasi, karena working tree juga memuat perubahan polygon & soft delete yang tercampur di file yang sama):

```
d147957 refactor: hapus fitur harga tipe unit, polygon batas area, dan soft delete
20 files changed, 273 insertions(+), 920 deletions(-)
```

Isi commit:
- Penghapusan fitur harga (bagian 3 di atas)
- 3 migration baru: `drop_deleted_at_from_perumahans`, `drop_batas_polygon_from_perumahans`, `drop_harga_from_tipe_units`
- Pembersihan fitur polygon batas area (kolom, JS gambar polygon, statistik dashboard "Belum Punya Polygon", tampilan luas polygon & deviasi)
- Pembersihan soft delete — penghapusan data kini **hard delete permanen**, detail PSU & tipe unit ikut terhapus via FK `cascadeOnDelete()`
- README & test diperbarui menyeluruh

Push ke remote:
```
dae9e53..d147957  main -> main   (github.com/sadewati23/perumahan-backend)
```

---

## 5. Reset Database Sesuai Skema Baru

`php artisan migrate:fresh --seed` dijalankan agar data contoh tidak lagi membawa harga:

```
Dropping all tables .................................. DONE
14 migrations dijalankan (termasuk 3 migration drop)  DONE
Seeder: User, Wilayah, Perumahan ..................... DONE
```

Verifikasi via tinker:

| Tabel | Jumlah |
|---|---|
| Perumahan | 7 |
| Tipe Unit | 16 (tanpa harga) |
| PSU | 26 |

Kolom `harga` terkonfirmasi **tidak ada** di tabel `tipe_units`.

---

## 6. Fitur Laporan Kerusakan Sarana-Prasarana (2026-10-05)

**Status: selesai (belum di-commit).**

Permintaan: user bisa membuat laporan ketika ada sarana dan prasarana yang rusak. Setelah konfirmasi, disepakati: laporan **tanpa login** (nama + kontak), form di **detail perumahan + halaman tersendiri**, **upload foto bukti**, dan **kelola laporan lengkap di admin**.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Tabel `laporans` (kode tiket unik `LPR-YYYYMMDD-XXXX`, pelapor, judul, deskripsi, tingkat, status, penanganan) + `laporan_fotos` (path foto, disk `public`) |
| Form publik | Halaman `/lapor` — dropdown perumahan → PSU dinamis (JS), nama, kontak, tingkat kerusakan, uraian, maks. 3 foto (2 MB/foto); setelah kirim tampil **nomor tiket** |
| Detail perumahan publik | Tombol "Lapor Kerusakan" (prefill perumahan) + tautan di kartu PSU |
| Anti-spam | Route `lapor.store` dibatasi `throttle:10/menit` |
| Admin | Menu sidebar **Laporan Kerusakan** dengan badge laporan baru (view composer `layouts.app`); daftar + ringkasan status + filter; detail + galeri foto + tombol WhatsApp pelapor (normalisasi 08xxx → 628xxx); form penanganan status (baru/diproses/selesai/ditolak) + catatan tindak lanjut (tercatat petugas & waktu) |
| Dashboard | Kartu ringkasan laporan (baru/diproses/selesai/total) + 5 laporan terbaru |
| Integritas data | Laporan ikut terhapus saat perumahan dihapus (FK `cascadeOnDelete`) |
| Test | `tests/Feature/LaporanTest.php` — 10 kasus uji |

### Verifikasi
- `php artisan migrate` ✅ & `php artisan storage:link` ✅
- 30 test lulus (136 assertions) ✅

---

## 7. Ukuran Kerusakan pada Laporan (2026-10-05)

**Status: selesai (belum di-commit).**

Permintaan lanjutan: masukkan ukuran kerusakan — persentase kerusakan dan panjang kerusakan.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Migration `2026_10_05_100003_add_ukuran_to_laporans_table` — kolom `persentase_kerusakan` (decimal 5,2, 0–100) & `panjang_kerusakan` (decimal 12,2, meter), keduanya **nullable/opsional** |
| Form publik | Dua input opsional di bagian Detail Kerusakan (persen & meter) dengan petunjuk "isi yang relevan saja" |
| Model | Cast desimal 2 digit; accessor `label_persentase_kerusakan`, `label_panjang_kerusakan`, `ukuran_ringkas` |
| Admin | Kolom **Ukuran** di daftar laporan; persentase & panjang tampil di detail |
| Validasi | Persen 0–100, panjang ≥ 0; menolak nilai negatif/lebih dari 100 |
| Test | 2 kasus uji baru: penyimpanan + tampilan admin, dan validasi ukuran |

### Verifikasi
- `php artisan migrate` ✅
- 32 test lulus (149 assertions) ✅

---

## 8. Tiga Jenis Laporan Warga: Kerusakan, Pengaduan, & Bangunan Liar (2026-10-06)

**Status: selesai (belum di-commit).**

Permintaan: menambah **2 jenis laporan baru** — pengaduan (teks + gambar opsional) dan bangunan liar. Disepakati:
- Bangunan liar: **perumahan opsional, kelurahan wajib**, **foto wajib minimal 1**, field **tanggal kejadian** (opsional).
- Pengaduan: cukup teks, foto opsional, tanpa lokasi wajib.
- Kode tiket dibedakan per jenis: **LPR-** kerusakan, **PGN-** pengaduan, **BLR-** bangunan liar.

### Desain
Satu tabel `laporans` untuk semua jenis dengan kolom baru `jenis` (enum `kerusakan`/`pengaduan`/`bangunan_liar`) + validasi kondisional per jenis — tanpa tabel baru, admin tetap satu halaman kelola.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Migration `2026_10_06_000001_add_jenis_dan_lokasi_to_laporans_table`: kolom `jenis` (default `kerusakan`), `kelurahan_id` (FK nullable), `lokasi_teks`, `tanggal_kejadian` (date, nullable); `perumahan_id` kini **nullable**; `tingkat` kini **nullable** (hanya untuk jenis kerusakan) |
| Model | Konstanta `JENIS_*` + `JENIS` + `PREFIX_KODE`; `buatKode($jenis)` dengan prefix per jenis; relasi `kelurahan()`; accessor `label_jenis`, `badge_jenis`, `lokasi_ringkas`, `label_tanggal_kejadian`; `label_tingkat` jadi null-safe |
| Controller publik | Validasi kondisional per jenis: kerusakan (perumahan+tingkat wajib), pengaduan (bebas), bangunan liar (kelurahan+lokasi wajib, foto min 1, tanggal ≤ hari ini); bangunan liar dengan perumahan otomatis memakai kelurahan perumahan; normalisasi field tak relevan per jenis |
| Form publik | Radio pilihan jenis; panel lokasi dinamis per jenis (kerusakan: perumahan+PSU; bangunan liar: kelurahan+tanggal+lokasi teks+perumahan opsional; pengaduan: info tanpa lokasi); keterangan foto berubah "wajib" untuk bangunan liar; input panel tersembunyi dinonaktifkan saat submit agar tidak saling menimpa |
| Admin daftar | Filter **Jenis**, kolom **Jenis** dengan badge warna per jenis, lokasi fleksibel (perumahan atau kelurahan), tingkat "-" untuk non-kerusakan |
| Admin detail | Badge jenis; lokasi perumahan **atau** kelurahan-kecamatan; titik/alamat kejadian; tanggal kejadian; section ukuran/PSU hanya untuk kerusakan |
| Dashboard | Ringkasan jumlah per jenis (kerusakan/pengaduan/bangunan liar) sebagai tautan filter + daftar terbaru lintas jenis |
| Menu | Sidebar admin "Laporan Kerusakan" → **"Laporan Warga"**; menu publik "Lapor Kerusakan" → **"Lapor"** |
| Test | 9 kasus uji baru (pengaduan, bangunan liar, validasi, filter admin); total 22 test di LaporanTest |

### Verifikasi
- `php artisan migrate:fresh --seed` ✅
- 42 test lulus (199 assertions) ✅

---

## 9. Penghapusan Kolom Nomor HP/WA pada Form Lapor (2026-10-06)

**Status: selesai (belum di-commit).**

Permintaan: hapus kolom pengisian **nomor HP/WA** saat user membuat laporan karena terlalu ribet.

### Keputusan
- Field kontak dihapus total dari **form lapor publik** — warga cukup mengisi nama tanpa kontak.
- Kolom `kontak_pelapor` **tetap ada di database** (dijadikan **nullable**): data lama yang punya kontak tetap tampil di admin, data baru tersimpan NULL.
- Tombol WhatsApp di detail admin tetap berfungsi bila kontak lama berisi nomor telepon.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Migration `2026_10_06_000002_make_kontak_pelapor_nullable_in_laporans_table` — `kontak_pelapor` string(100) kini **nullable** |
| Form publik | Field "No. HP / WhatsApp" dihapus dari section "3. Data Pelapor"; teks aside disesuaikan (hanya menyebut nama) |
| Controller | Validasi `kontak_pelapor` (required, min:6) + pesan customnya dihapus dari `LaporanController::store()` |
| Model | `getKontakWaAttribute()` jadi null-safe (kontak null → tanpa tombol WA) |
| Admin daftar | Kolom Pelapor menampilkan "-" bila kontak kosong |
| Admin detail | Kartu Pelapor menampilkan "Tidak dicantumkan" + tombol WhatsApp disembunyikan bila kontak kosong |
| Test | Kontak dihapus dari semua data uji; test validasi menyesuaikan; assertion baru: laporan tersimpan dengan kontak NULL |

### Verifikasi
- `php artisan migrate` ✅
- 42 test lulus (199 assertions) ✅

---

## 10. Pembersihan Tabel Tidak Terpakai & Storage (2026-10-06)

**Status: selesai (belum di-commit).**

Permintaan: hapus tabel-tabel yang tidak digunakan pada database untuk menghemat storage.

### Hasil Audit (17 tabel sebelum dibersihkan)

| Kategori | Tabel | Keputusan |
|---|---|---|
| Tidak dipakai & kosong | `personal_access_tokens` | **Dihapus** — Sanctum terpasang tapi tidak ada model yang memakai `HasApiTokens`/`createToken`; aplikasi murni autentikasi session web |
| Tidak dipakai & kosong | `password_reset_tokens` | **Dihapus** — tidak ada fitur lupa password di aplikasi |
| Dipakai framework | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` | **Tetap** — `.env` memakai `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` |
| Dipakai aplikasi | `users`, `kecamatans`, `kelurahans`, `perumahans`, `psu_perumahans`, `tipe_units`, `laporans`, `laporan_fotos`, `migrations` | **Tetap** |

Bonus: 3 file foto yatim (±2 KB) di `storage/app/public/laporan/` yang tidak direferensikan baris `laporan_fotos` manapun ikut dihapus.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Migration `2026_10_06_000003_drop_unused_framework_tables` — drop 2 tabel di atas (17 → 15 tabel); `down()` sengaja kosong karena migration pembuatnya tetap ada untuk `migrate:fresh` |
| Storage | 3 file yatim `storage/app/public/laporan/*.jpg` dihapus via tinker (verifikasi dulu 0 baris di `laporan_fotos`) |

### Verifikasi
- `php artisan migrate` ✅ — sisa 15 tabel, kedua tabel terkonfirmasi hilang
- 42 test lulus (199 assertions) ✅ (test env SQLite `migrate:fresh` ikut sukses)

Catatan: total ukuran data DB hanya ±704 KB, sehingga penghematan riilnya kecil (±80 KB) — manfaat utamanya skema database yang lebih bersih.

---

## 11. Fitur Lupa Password (2026-10-06)

**Status: selesai (belum di-commit).**

Permintaan: kembalikan kemampuan **lupa password** agar admin/petugas bisa mereset password sendiri lewat email — tabel `password_reset_tokens` yang dihapus pada bagian 10 dipulihkan.

### Alur

1. Di halaman login ada tautan **"Lupa password?"** → form minta email.
2. Sistem mengirim email berisi tautan reset (berlaku **60 menit**, satu email per menit per email terdaftar — throttle).
3. Buka tautan → form password baru + konfirmasi (min. 6 karakter).
4. Password tersimpan (hash baru), **token dihapus** (sekali pakai), sesi auth lama di-logout otomatis, lalu diarahkan ke login dengan pesan sukses.

### Perubahan

| Area | Rincian |
|---|---|
| Database | Migration `2026_10_06_000004_restore_password_reset_tokens_table` — memulihkan tabel `password_reset_tokens` (skema identik dengan bawaan Laravel) |
| Routes | `GET/POST /lupa-password` (`password.request`, `password.email` + throttle 5/menit), `GET /reset-password/{token}` (`password.reset`), `POST /reset-password` (`password.update`) — semua di middleware `guest` |
| Notification | `App\Notifications\ResetPasswordNotification` — email berbahasa Indonesia: subject, masa berlaku token, dan tombol "Reset Password"; dipakai lewat override `sendPasswordResetNotification()` di model `User` |
| Model User | Implement `CanResetPassword` + trait bawaan; override pengiriman notifikasi kustom |
| Controller | 4 method baru di `AuthController`: `showForgotPassword`, `sendResetLink`, `showResetPassword`, `resetPassword` (pakai `Password::sendResetLink` / `Password::reset`, password di-hash ulang, event auth.php) |
| View | `auth/forgot-password.blade.php` & `auth/reset-password.blade.php` (gaya seragam dengan halaman login); tautan "Lupa password?" ditambahkan di `auth/login.blade.php` |

### Perilaku pesan
Bebas error bawaan Laravel (berbahasa Inggris): status pengiriman tautan, email tidak terdaftar, dan tautan gagal valid/kedaluwarsa kini berbahasa Indonesia.

### Verifikasi
- `php artisan migrate` ✅ — tabel `password_reset_tokens` kembali ada (15 → 16 tabel)
- 51 test lulus (234 assertions) ✅ — termasuk 9 test baru di `PasswordResetTest`: form, kirim notifikasi, reset sukses (token nyata dari email), token invalid ditolak, konfirmasi tak cocok ditolak, token sekali pakai terhapus, dan login langsung setelah reset
- Smoke test email via tinker ✅ — subject "Notifikasi Reset Password", link `/reset-password/<token>`, isi berbahasa Indonesia

Catatan: `.env` masih `MAIL_MAILER=log` — email terekam ke `storage/logs/laravel.log`. Untuk produksi, ganti mailer SMTP/populer vendor agar email benar-benar terkirim.

---

## 12. Perbaikan 4 Temuan Review Proyek (2026-10-06)

**Status: selesai (belum di-commit).**

Permintaan: eksekusi 4 temuan prioritas dari hasil review proyek — throttle login, sinkronisasi PSU tanpa delete-all, guard hapus kecamatan, dan pembersihan route API.

### Perubahan

| Area | Rincian |
|---|---|
| **Throttle login** | `AuthController::login()` — maks. 5 percobaan gagal/menit ber-key email+IP via `RateLimiter`; login sukses menghapus hitungan; pesan blokir berbahasa Indonesia. Key per email+IP mencegah penyerang mengunci korban hanya dengan spam dari satu IP |
| **Sinkronisasi PSU & tipe unit** | `PerumahanController::syncDetail()` — baris lama di-update by `id` (bukan delete-all + create ulang); hanya baris yang benar-benar dihapus dari form yang di-delete. Form `_form.blade.php` kini mengirim hidden input `id` per baris; validasi `psu.*.id` & `tipe_units.*.id` ditambahkan. Mencegah relasi `laporans.psu_perumahan_id` (nullOnDelete) terputus setiap kali data perumahan disimpan |
| **Guard hapus kecamatan** | `KecamatanController::destroy()` — tolak hapus kecamatan yang masih punya kelurahan (FK `cascadeOnDelete` menghapus kelurahan ikutan & memutus referensi laporan) |
| **Route API** | `routes/api.php` dihapus + registrasi `api:` dicabut dari `bootstrap/app.php` — route `/api/user` (`auth:sanctum`) rusak sejak tabel `personal_access_tokens` di-drop (bagian 10) dan tidak pernah dipakai |
| Transaksi | `store()`/`update()` perumahan dibungkus `DB::transaction` agar data induk & detail tersimpan konsisten |

### Verifikasi
- `php -l` bersih semua file yang diubah ✅
- `vendor/bin/pint --test` pada file yang diubah ✅ — tanpa issue baru
- `php artisan route:list` ✅ — tidak ada lagi route `/api/*`
- 55 test lulus (269 assertions) ✅ — +4 test baru: `LoginThrottleTest` (blokir setelah 5 percobaan gagal; login sukses menghapus hitungan), `PerumahanTest` (edit perumahan mempertahankan id PSU & relasi laporan→PSU), `HalamanTest` (kecamatan berkelurahan ditolak dihapus)

Catatan: temuan review lainnya (enumerasi email pada reset password, password min. 6, `is_active` mid-session, dead code model `User`, 6 style issue Pint lama) belum dikerjakan — di luar 4 prioritas ini.

---

## 13. Perbaikan Stored XSS pada Popup Peta (2026-10-06)

**Status: selesai (belum di-commit).** Temuan dari user — dikonfirmasi valid setelah verifikasi.

Permintaan: benarkan Stored XSS di popup peta: `${p.nama}`, `${p.status}`, dan `${p.alamat}` pada 3 halaman (`publik/peta`, `publik/beranda`, `perumahan/peta`) diinterpolasi mentah ke template literal HTML popup Leaflet. Korban tertular siapa pun yang membuka peta (warga publik di `/peta` & `/`, atau admin/petugas di `/admin/perumahan/peta`) — sedangkan data nama/alamat diisi bebas oleh petugas lewat form perumahan.

### Perubahan

| Area | Rincian |
|---|---|
| `perumahan/peta.blade.php` | Helper `escapeHtml()` di JS + semua interpolasi popup di-escape: `${escapeHtml(p.nama)}`, `${escapeHtml(p.status)}`, `${escapeHtml(p.alamat)}`; `href="${encodeURI(p.url)}"` |
| `publik/peta.blade.php` | Sama dengan baris atas |
| `publik/beranda.blade.php` | Sama dengan baris atas |
| `publik/show.blade.php` & `perumahan/show.blade.php` | Hardening bonus: `bindPopup(@json(e($perumahan->nama)))` — payload kini ter-escape di level HTML, bukan hanya level JSON |

Catatan: interpolasi angka (`p.unit`, `p.luas_lahan`, `p.kdb`, `p.kdh`) sudah aman sejak awal lewat `Intl.NumberFormat` (hanya menghasilkan karakter angka/pemisah).

### Verifikasi
- `grep` — tidak ada lagi interpolasi mentah `${p.nama|status|alamat}` di ketiga view; `escapeHtml` terpasang 4× per file ✅
- Probe request nyata (data payload `<script>alert(1)</script>` & `<img src=x onerror=alert(2)>` disisipkan sementara ke DB lalu dihapus lagi): halaman `/peta` dan `/admin/perumahan/peta` merender payload sebagai `&lt;script&gt;…` (tidak ada tag mentah), HTTP 200 ✅
- Probe JS (node): `escapeHtml()` menghasilkan string sepenuhnya ter-escape untuk payload nama & alamat ✅
- 55 test lulus (269 assertions) ✅ — tidak ada regresi

---

## 14. Subresource Integrity (SRI) untuk Aset CDN (2026-10-12)

**Status: selesai (belum di-commit).** Temuan dari user — script CDN dimuat tanpa atribut `integrity`, sehingga browser tidak memverifikasi integritas file yang dikirim CDN (risiko kebocoran konten bila CDN disusupi/ter-combine dengan versi yang disuntik).

Permintaan: tambahkan `integrity` + `crossorigin`, atau bundle lewat Vite. Dipilih **SRI** karena versi sudah dipin (`leaflet@1.9.4`, `chart.js@4.4.9`) sehingga hash-nya stabil — tanpa mengubah pipeline build `npm run build`.

### Perubahan

| Area | Rincian |
|---|---|
| `perumahan/{create,edit,show,peta}.blade.php` | Tag `leaflet.css` & `leaflet.js` dari unpkg diberi `integrity="sha384-..."` + `crossorigin="anonymous"` |
| `publik/{beranda,show,peta}.blade.php` | Sama — tag Leaflet identik dengan halaman admin |
| `dashboard.blade.php` | Tag `chart.umd.js` 4.4.9 dari jsDelivr diberi `integrity="sha384-..."` + `crossorigin="anonymous"` (foll-up: `chart.umd.min.js` bukan file asli paket npm — jsDelivr meminify ulang otomatis sehingga hash-nya tak bisa diverifikasi; `chart.umd.js` sudah minified) |
| `tests/Feature/HalamanTest.php` | Test regresi `test_script_cdn_memakai_sri_integrity_dan_crossorigin` — memeriksa setiap tag `<script>/<link>` ke unpkg/jsDelivr di 5 halaman admin + 3 halaman publik wajib punya `integrity="sha384-..."` dan `crossorigin="anonymous"`, plus guard agar test gagal bila tag CDN-nya sendiri hilang |
| `README.md` | Catatan teknis: SRI aktif + cara menghitung ulang hash saat naik versi |

Hash dihitung dari byte yang benar-benar disajikan CDN (bukan ditulis manual). Folll-up 2026-10-13: tag Leaflet dipakai `integrity="sha256-..."` (hash resmi dari docs Leaflet 1.9.4), Chart.js dipakai file asli `chart.umd.js` + hash sha384 yang terverifikasi:

```
leaflet.js   sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=
leaflet.css  sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=
chart.umd.js sha384-4W8gMhOYqk0FPg9sAMkb3g/P2fXtzbUyDUUgMlA1EEig8z/U/13EYUjErc1H382b
```

### Verifikasi
- Re-download 3 file dari CDN + `openssl dgst -sha384` → cocok persis dengan hash yang tertanam di view ✅
- Header CORS: unpkg & jsDelivr sama-sama mengirim `Access-Control-Allow-Origin: *` (wajib ada karena `crossorigin="anonymous"`) ✅
- Chrome headless dengan tag persis dari view: `typeof L = object`, `typeof Chart = function`, `L.map()` sukses, tanpa pelanggaran SRI ✅
- **Kontrol negatif**: hash sengaja dipalsukan → browser memblokir Leaflet (`L is not defined`) — atribut SRI aktif, bukan hiasan ✅
- E2E login admin sungguhan (Chrome + server `php artisan serve`): dashboard memuat Chart.js 200 & 3 chart instance terpasang (`grafik-tahun`, `grafik-kondisi`, `grafik-jenis-psu`); `/admin/perumahan/peta` memuat Leaflet 1.9.4 & peta aktif; 0 console error, 0 request gagal ✅
- 67 test lulus (383 assertions) ✅ — termasuk test regresi baru (46 assertion)

---

## Struktur Akhir `tipe_units`

```
tipe_units
├── id
├── perumahan_id   (FK → perumahans, cascadeOnDelete)
├── tipe           (mis. "Tipe 36")
├── jumlah_unit
├── luas_tanah     (m², nullable)
├── luas_bangunan  (m², nullable)
├── keterangan     (nullable)
└── timestamps
```

## Akun Bawaan (Seeder)

| Email | Password | Role |
|---|---|---|
| admin@perumahan.test | password | Administrator |
| petugas@perumahan.test | password | Petugas |
## 2026-10-14 — Perbaikan UI/UX bertahap (Fase 1–5)

- Fondasi: token @theme, komponen Blade, flash toast, modal confirm native, guard submit, aksesibilitas dasar, skip link, welcome.blade.php dihapus.
- Formulir: x-field di seluruh form, section/action bar, counter karakter, toggle password, upload foto dengan pratinjau.
- Daftar & tabel: responsif kartu/mobile + tabel sticky header, filter collapsible + chip aktif + reset + jumlah hasil, pagination Tailwind Indonesia, empty-state terdiferensiasi.
- Admin & dashboard: topbar breadcrumb + menu pengguna, sidebar berkelompok, KPI dashboard + panel laporan menunggu penanganan, chart aksesibel.
- Publik: hero CTA lapor/peta, lapor/form kartu radio, halaman sukses dengan salin tiket, placeholder gambar, bobot aksesibilitas.
- Audit CSS/JS lintas halaman; npm run build dan php artisan test lulus.
