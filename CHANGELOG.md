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
