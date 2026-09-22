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
| Peta Sebaran (GIS) | Leaflet + OpenStreetMap, marker berwarna per status, polygon batas area, pencarian lokasi, endpoint GeoJSON |
| Titik Lokasi | Pemilih titik di peta saat input/ubah data, tombol "gunakan lokasi saya" (geolokasi browser) |
| Batas Area (Polygon) | Gambar batas lahan perumahan di peta saat input/ubah data; tampil di peta sebaran & detail |
| Master Wilayah | Kelola kecamatan dan kelurahan sebagai sumber dropdown |
| Manajemen Pengguna | Khusus admin: tambah/ubah/nonaktifkan/hapus akun |

Data yang di-soft delete otomatis hilang dari portal publik namun tetap tersimpan di basis data.

## Struktur Data

- `kecamatans` — kode, nama, kabupaten/kota
- `kelurahans` — kecamatan_id, kode, nama, kode_pos
- `perumahans` — nama, pengembang, kelurahan_id, alamat, rt, rw, latitude, longitude, batas_polygon (JSON array [lat,lng]), luas_lahan, jumlah_unit, status (`subsidi`/`komersil`), tahun_pembuatan, tahun_selesai, jumlah_rth, luas_rth, kdb, kdh, ada_lahan_pemakaman, luas_lahan_pemakaman, lokasi_lahan_pemakaman, keterangan (soft delete)
- `psu_perumahans` — perumahan_id, jenis, jumlah, satuan, kondisi, keterangan
- `tipe_units` — perumahan_id, tipe, jumlah_unit, luas_tanah, luas_bangunan, harga, keterangan

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

Mencakup autentikasi, hak akses role, CRUD perumahan, validasi rentang tahun, penyimpanan PSU & tipe unit, penyimpanan & normalisasi batas polygon, GeoJSON polygon, statistik dashboard, dan render seluruh halaman utama.

## Catatan Teknis

- Peta memakai Leaflet 1.9.4 dari CDN (Unpkg) dan tile OpenStreetMap, sehingga browser perlu akses internet.
- Grafik dashboard memakai Chart.js 4 dari CDN (jsDelivr).
- Polygon batas area disimpan sebagai JSON array pasangan `[lat,lng]` pada kolom `perumahans.batas_polygon`; GeoJSON mengeluarkannya sebagai `properties.geometry_polygon` (RingGeometry GeoJSON `[lng,lat]`) sehingga tetap kompatibel QGIS.
- Filter pencarian publik dan admin memakai scope yang sama (`Perumahan::filter()`), jadi hasil pencarian konsisten.
- Endpoint GeoJSON tersedia di publik (`/peta/geojson`) maupun admin (`/admin/perumahan/geojson`).
- Filter pencarian pada daftar perumahan mencakup nama, pengembang, alamat, kelurahan, dan kecamatan.
