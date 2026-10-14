# Sistem Informasi Data Perumahan

Aplikasi web (Laravel 12 + Blade + Tailwind CSS 4) untuk pendataan perumahan: identitas perumahan, lokasi administratif (kelurahan, kecamatan, RT/RW), titik koordinat GIS, luasan lahan, jumlah unit, data PSU, RTH, KDB/KDH, lahan pemakaman, tahun pembangunan, tipe unit, dan status perumahan (subsidi/komersil).

## Fitur

Aplikasi terdiri dari dua bagian: **portal publik** (tanpa login) dan **area admin** (prefix `/admin`, butuh login).

### Portal Publik

| Halaman | URL | Keterangan |
| --- | --- | --- |
| Beranda | `/` | Hero + pencarian, statistik, komposisi status, sebaran per kecamatan, peta ringkas, perumahan terbaru |
| Data Perumahan | `/perumahan` | Daftar kartu perumahan dengan filter kata kunci, kecamatan, status + ringkasan hasil |
| Detail Perumahan | `/perumahan/{id}` | Seluruh informasi: lokasi administratif, luasan, unit, RTH, KDB/KDH, tipe unit, PSU, lahan pemakaman, peta titik, perumahan lain di kecamatan sama |
| Peta Sebaran | `/peta` | Peta penuh dengan filter, daftar lokasi yang bisa diklik, daftar perumahan tanpa koordinat |
| GeoJSON | `/peta/geojson` | FeatureCollection titik perumahan untuk integrasi WebGIS/QGIS |

### Area Admin (`/admin`)

| Modul | Keterangan |
| --- | --- |
| Autentikasi & Role | Login/logout, role **admin** (semua akses) dan **petugas** (data perumahan & wilayah) |
| Dashboard | Statistik total perumahan, unit, luas lahan, RTH, sebaran per kecamatan, tren tahun selesai (Chart.js), komposisi kondisi PSU, jenis PSU terbanyak |
| Data Perumahan | CRUD lengkap + filter (kata kunci, kecamatan, status, tahun) |
| Detail Perumahan | Ringkasan luasan, KDB/KDH, RTH, lahan pemakaman, rincian tipe unit, tabel PSU, mini map |
| Peta Sebaran (GIS) | Leaflet + OpenStreetMap, marker berwarna per status, pencarian lokasi, endpoint GeoJSON |
| Titik Lokasi | Pemilih titik di peta saat input/ubah data, tombol "gunakan lokasi saya" (geolokasi browser) |
| Master Wilayah | Kelola kecamatan dan kelurahan sebagai sumber dropdown |
| Manajemen Pengguna | Khusus admin: tambah/ubah/nonaktifkan/hapus akun |

Data yang di-soft delete otomatis hilang dari portal publik namun tetap tersimpan di basis data.

## Struktur Data

- `kecamatans` — kode, nama, kabupaten/kota
- `kelurahans` — kecamatan_id, kode, nama, kode_pos
- `perumahans` — nama, pengembang, kelurahan_id, alamat, rt, rw, latitude, longitude, luas_lahan, jumlah_unit, status (`subsidi`/`komersil`), tahun_pembuatan, tahun_selesai, jumlah_rth, luas_rth, kdb, kdh, ada_lahan_pemakaman, luas_lahan_pemakaman, lokasi_lahan_pemakaman, keterangan — penghapusan data bersifat **permanen** (hard delete, detail PSU & tipe unit ikut terhapus via FK cascade)
- `psu_perumahans` — perumahan_id, jenis, jumlah, satuan, kondisi, keterangan
- `tipe_units` — perumahan_id, tipe, jumlah_unit, luas_tanah, luas_bangunan, keterangan

Satuan luas menggunakan **m²** (dibantu tampilan hektar pada dashboard/daftar).

## Menjalankan Aplikasi

```bash
composer install          # jika belum
cp .env.example .env      # jika .env belum ada
php artisan key:generate
# sesuaikan DB_* pada .env (default: mysql, database sistem_perumahan)
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Akses `http://localhost:8000` untuk portal publik dan `http://localhost:8000/admin` untuk area admin (dari publik tekan tombol **Masuk Admin**).

### Akun bawaan (dari seeder)

| Email | Password | Role |
| --- | --- | --- |
| admin@perumahan.test | password | Administrator |
| petugas@perumahan.test | password | Petugas |

> Ganti password akun bawaan sebelum dipakai di lingkungan nyata.

## Wilayah & Peta

- Peta dipusatkan ke **Kota Cirebon** (`-6.7320, 108.5523`, zoom 13). Ubah lewat `config/peta.php` atau variabel `PETA_*` di `.env` (mis. `PETA_LATITUDE`, `PETA_LONGITUDE`, `PETA_ZOOM`, `PETA_WILAYAH`).
- Nama wilayah pada beranda, footer, dan halaman login diambil dari `config('peta.nama_wilayah')`.

## Data Contoh

`database/seeders/WilayahSeeder.php` mengisi **5 kecamatan dan 22 kelurahan Kota Cirebon** (kode Kemendagri 32.74.xx + kode pos), sedangkan `PerumahanSeeder.php` mengisi 7 perumahan contoh yang tersebar di kelima kecamatan, lengkap dengan PSU dan tipe unit. Sesuaikan atau hapus sesuai kebutuhan, lalu jalankan ulang `php artisan migrate:fresh --seed`.

## Pengujian

```bash
php artisan test
```

Mencakup autentikasi, hak akses role, CRUD perumahan, validasi rentang tahun, penyimpanan PSU & tipe unit, GeoJSON, statistik dashboard, dan render seluruh halaman utama.

## Catatan Teknis

- Peta memakai Leaflet 1.9.4 dari CDN (Unpkg) dan tile OpenStreetMap, sehingga browser perlu akses internet.
- Grafik dashboard memakai Chart.js 4 dari CDN (jsDelivr).
- Kedua CDN memakai **Subresource Integrity** (`integrity="sha256/sha384-..."` + `crossorigin="anonymous"`), jadi file yang dimodifikasi pihak ketiga akan ditolak browser. Saat menaikkan versi Leaflet/Chart.js, hash harus dihitung ulang: `curl -sSL <url> | openssl dgst -sha384 -binary | openssl base64 -A` (Chart.js: pakai file asli `chart.umd.js`, bukan `chart.umd.min.js` karena minify ulang otomatis jsDelivr membuat hash tak bisa divalidasi).
- Filter pencarian publik dan admin memakai scope yang sama (`Perumahan::filter()`), jadi hasil pencarian konsisten.
- Kolom harga pada tipe unit telah dihapus sepenuhnya dari fitur (form, filter, tampilan, dan kolom database).
- Endpoint GeoJSON tersedia di publik (`/peta/geojson`) maupun admin (`/admin/perumahan/geojson`).
- Filter pencarian pada daftar perumahan mencakup nama, pengembang, alamat, kelurahan, dan kecamatan.
