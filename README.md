# Sistem Informasi Data Perumahan

Aplikasi web untuk pendataan perumahan dan sarana-prasarana (PSU), visualisasi lokasi berbasis GIS, serta pengelolaan laporan warga. Aplikasi dibangun dengan Laravel 12, Blade, Tailwind CSS 4, Leaflet, dan OpenStreetMap.

## Daftar Isi

- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Akun dan Hak Akses](#akun-dan-hak-akses)
- [Modul Aplikasi](#modul-aplikasi)
- [Endpoint Utama](#endpoint-utama)
- [Struktur Data](#struktur-data)
- [Struktur Direktori](#struktur-direktori)
- [Pengujian](#pengujian)
- [Operasional dan Deployment](#operasional-dan-deployment)
- [Catatan Teknis](#catatan-teknis)

## Fitur

### Portal publik

- Beranda dengan statistik, pencarian, perumahan terbaru, dan ringkasan wilayah.
- Daftar dan detail perumahan dengan filter kata kunci, kecamatan, status, dan tahun selesai.
- Peta sebaran perumahan menggunakan Leaflet dan OpenStreetMap.
- Endpoint GeoJSON untuk integrasi WebGIS atau QGIS.
- Form laporan warga tanpa login untuk kerusakan PSU, pengaduan, dan bangunan liar.

### Area admin

- Dashboard statistik perumahan, unit, luas lahan, RTH, PSU, dan laporan.
- CRUD data perumahan, termasuk koordinat, PSU, dan tipe unit.
- Peta admin dengan pemilih titik koordinat dan geolokasi browser.
- Pengelolaan master kecamatan dan kelurahan.
- Pengelolaan laporan: filter, detail, perubahan status, foto hasil perbaikan, dan cetak.
- Manajemen pengguna (khusus administrator): tambah, ubah, nonaktifkan, dan hapus akun.
- Cache statistik dan cache data peta dibersihkan otomatis setelah perubahan data terkait.

## Teknologi

| Komponen | Teknologi |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Tampilan | Blade, Tailwind CSS 4, Vite |
| Database | MySQL (default) |
| Peta | Leaflet 1.9.4, OpenStreetMap |
| Grafik | Chart.js 4 melalui CDN |
| Autentikasi | Laravel session authentication |
| Upload | Laravel Filesystem disk `public` |
| Pengujian | PHPUnit melalui Laravel Test Runner |

## Persyaratan

- PHP `8.2` atau lebih baru dengan ekstensi Laravel yang diperlukan.
- Composer.
- Node.js dan npm.
- MySQL/MariaDB.
- Ekstensi GD PHP untuk pemrosesan foto laporan.
- Akses internet pada browser untuk tile OpenStreetMap dan library CDN.

## Instalasi

1. Clone atau salin project, lalu masuk ke direktori project.

2. Install dependency backend dan frontend:

   ```bash
   composer install
   npm install
   ```

3. Buat file environment dan application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Pada Windows, file `.env.example` dapat disalin manual menjadi `.env`.

4. Buat database, kemudian sesuaikan nilai `DB_*` pada `.env`.

5. Jalankan migration dan data contoh:

   ```bash
   php artisan migrate --seed
   ```

6. Hubungkan storage publik agar foto dapat diakses browser:

   ```bash
   php artisan storage:link
   ```

7. Build asset frontend dan jalankan server:

   ```bash
   npm run build
   php artisan serve
   ```

   Buka `http://localhost:8000` untuk portal publik dan `http://localhost:8000/admin` untuk area admin.

### Mode development

Untuk menjalankan server Laravel, queue, log viewer, dan Vite secara bersamaan:

```bash
npm run dev
# atau script Composer:
composer run dev
```

### Foto tidak muncul?

Pastikan tautan storage sudah dibuat:

```bash
php artisan storage:link
```

Di Windows, jika symlink tidak dapat dibuat, gunakan junction dari folder project:

```text
mklink /J "public\storage" "storage\app\public"
```

Nilai `STORAGE_URL` bergantung pada cara menjalankan aplikasi:

- XAMPP subfolder: `/perumahan-backend/public/storage`
- `php artisan serve` atau virtual host: `/storage`
- Untuk akses HP/ngrok/HTTPS, gunakan URL relatif tersebut agar host dan protokol mengikuti halaman.

Jika unggahan gagal, sesuaikan `php.ini`, lalu restart Apache:

```ini
upload_max_filesize=10M
post_max_size=12M
```

## Konfigurasi

Konfigurasi penting pada `.env`:

```dotenv
APP_NAME="Sistem Data Perumahan"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_perumahan
DB_USERNAME=root
DB_PASSWORD=

PETA_WILAYAH="Kota Cirebon"
PETA_LATITUDE=-6.7320
PETA_LONGITUDE=108.5523
PETA_ZOOM=13

# Opsional: mengaktifkan verifikasi Cloudflare Turnstile pada form laporan
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

Pengaturan peta lengkap tersedia pada `config/peta.php`, termasuk zoom detail, batas koordinat, URL tile, dan atribusi peta. Setelah mengubah konfigurasi pada environment production, jalankan:

```bash
php artisan config:clear
php artisan config:cache
```

Mail default menggunakan driver `log`. Untuk mengaktifkan reset password melalui email, isi konfigurasi mail pada `.env`.

## Akun dan Hak Akses

Seeder membuat akun berikut:

| Email | Password awal | Role | Hak akses |
| --- | --- | --- | --- |
| `admin@perumahan.test` | `password` | `admin` | Semua modul, termasuk seluruh penghapusan dan pengguna |
| `petugas@perumahan.test` | `password` | `petugas` | Melihat, menambah, mengubah data, dan mengunggah foto perbaikan; tidak dapat menghapus, kecuali foto perbaikan yang diunggah sendiri |

Semua akun admin harus aktif (`is_active = true`). Middleware `active` menolak akun nonaktif, sedangkan middleware `role:admin` membatasi menu manajemen pengguna. Segera ganti password bawaan setelah instalasi.

## Modul Aplikasi

### Data perumahan

Data utama perumahan meliputi identitas, pengembang, alamat, RT/RW, kelurahan, koordinat, luas lahan, jumlah unit, status subsidi/komersil/campuran, tahun pembangunan/selesai, RTH, KDB/KDH, lahan pemakaman, PSU, dan tipe unit.

Penghapusan perumahan bersifat **permanen**. Detail PSU dan tipe unit ikut terhapus melalui foreign key cascade. Satuan luas yang digunakan adalah meter persegi (`m²`).

### Laporan warga

Form `/lapor` dapat digunakan tanpa login untuk tiga jenis laporan:

| Jenis | Kode tiket | Keterangan |
| --- | --- | --- |
| Kerusakan sarana-prasarana | `LPR-YYYYMMDD-XXXX` | Dapat dikaitkan dengan perumahan dan PSU |
| Pengaduan | `PGN-YYYYMMDD-XXXX` | Pengaduan umum terkait lokasi |
| Bangunan liar | `BLR-YYYYMMDD-XXXX` | Dapat dilaporkan di luar area perumahan |

Laporan baru berstatus `baru`, kemudian dapat diproses menjadi `diproses`, `selesai`, atau `ditolak` oleh petugas/admin. Upload laporan dibatasi maksimal tiga foto; foto diproses ulang untuk menghapus metadata EXIF sebelum disimpan. Foto hasil perbaikan disimpan terpisah dari foto bukti warga.

### Peta dan GeoJSON

Hanya perumahan dengan koordinat valid yang ditampilkan sebagai titik pada peta. GeoJSON memakai format `FeatureCollection`, dengan koordinat GeoJSON `[longitude, latitude]`.

## Endpoint Utama

Semua endpoint berikut menggunakan method HTTP yang tercantum. Endpoint admin membutuhkan login dan akun aktif.

### Publik

| Method | URL | Fungsi |
| --- | --- | --- |
| GET | `/` | Beranda |
| GET | `/perumahan` | Daftar perumahan dan filter |
| GET | `/perumahan/{perumahan}` | Detail perumahan |
| GET | `/peta` | Peta sebaran publik |
| GET | `/peta/geojson` | GeoJSON perumahan |
| GET | `/lapor` | Form laporan |
| POST | `/lapor` | Menyimpan laporan, dilindungi rate limit |
| GET/POST | `/login` | Login |
| GET/POST | `/lupa-password` | Meminta reset password |

### Admin

| Method | URL | Fungsi |
| --- | --- | --- |
| GET | `/admin/dashboard` | Dashboard |
| GET/POST/PUT/DELETE | `/admin/perumahan` | CRUD perumahan |
| GET | `/admin/perumahan/peta` | Peta admin |
| GET | `/admin/perumahan/geojson` | GeoJSON admin |
| GET/PUT/DELETE | `/admin/laporan/{laporan}` | Detail, update, dan hapus laporan |
| GET | `/admin/laporan/cetak-rekap` | Cetak rekap laporan |
| GET | `/admin/laporan/{laporan}/cetak` | Cetak satu laporan |
| POST/DELETE | `/admin/laporan/{laporan}/foto-perbaikan` | Kelola foto hasil perbaikan |
| GET/POST/PUT/DELETE | `/admin/wilayah` | Kelola kecamatan dan kelurahan |
| GET/POST/PUT/DELETE | `/admin/pengguna` | Kelola pengguna, khusus admin |

Petugas dapat melihat, menambah, mengubah data, dan mengunggah foto perbaikan, tetapi tidak dapat menghapus data. Petugas hanya dapat menghapus foto perbaikan yang diunggahnya sendiri; seluruh penghapusan data lainnya khusus admin.

Detail seluruh route dapat dilihat pada `routes/web.php`. Endpoint GeoJSON cocok digunakan untuk frontend peta atau diimpor ke aplikasi GIS.

## Struktur Data

- `users` — akun, role, jabatan, dan status aktif.
- `kecamatans` — kode, nama, dan kabupaten/kota.
- `kelurahans` — kecamatan, kode, nama, dan kode pos.
- `perumahans` — data identitas, lokasi, luasan, unit, status, dan atribut lingkungan.
- `psu_perumahans` — jenis PSU, jumlah, satuan, kondisi, dan keterangan.
- `tipe_units` — tipe unit, jumlah, luas tanah, luas bangunan, dan keterangan.
- `laporans` — tiket laporan warga, lokasi, status penanganan, dan catatan petugas.
- `laporan_fotos` — foto bukti warga dan foto hasil perbaikan petugas.

Seeder utama berada di `database/seeders/DatabaseSeeder.php` dan menjalankan `UserSeeder`, `WilayahSeeder`, serta `PerumahanSeeder`. Untuk mengulang database beserta data contoh:

```bash
php artisan migrate:fresh --seed
```

> Perintah tersebut menghapus seluruh data pada database yang dikonfigurasi. Jangan jalankan di production tanpa backup.

## Struktur Direktori

```text
app/
├── Http/Controllers/       # Controller publik, admin, auth, dan laporan
├── Http/Middleware/        # Middleware akun aktif dan role
├── Http/Requests/          # Validasi form
├── Models/                 # Model Eloquent dan relasi
├── Services/               # Statistik dashboard dan layanan peta
└── Support/                # Utility pemrosesan file/foto
database/
├── migrations/             # Skema dan perubahan database
└── seeders/                # Data awal/contoh
resources/views/            # Template Blade publik dan admin
routes/web.php              # Definisi seluruh route web
config/peta.php             # Konfigurasi wilayah dan peta
public/                     # Entry point, asset hasil build, dan storage link
tests/Feature/              # Pengujian alur aplikasi
```

## Pengujian

Jalankan seluruh test dengan:

```bash
php artisan test
```

Atau melalui script Composer:

```bash
composer test
```

Test feature mencakup portal publik, login dan throttling, role, akun nonaktif, CRUD perumahan, validasi, laporan warga, foto perbaikan, GeoJSON, dashboard, dan render halaman.

## Operasional dan Deployment

Panduan berikut dapat digunakan untuk VPS, shared hosting, atau cPanel.

### 1. Siapkan server

Pastikan hosting menyediakan:

- PHP 8.2 atau lebih baru beserta ekstensi `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, dan `gd`.
- Composer 2.x.
- MySQL/MariaDB.
- Node.js/npm untuk build asset. Jika tidak tersedia di hosting, jalankan `npm install` dan `npm run build` di komputer lokal, lalu unggah folder `public/build`.
- HTTPS dan kemampuan membuat symbolic link untuk foto upload.

### 2. Upload source code

Letakkan seluruh project di folder aplikasi yang **tidak dapat diakses langsung dari web**, misalnya:

```text
/home/username/apps/perumahan-backend/
```

Jangan mengarahkan domain ke root project. Document root harus menunjuk ke:

```text
/home/username/apps/perumahan-backend/public
```

Dengan begitu file `.env`, `app`, `config`, dan `storage` tidak menjadi file publik. Apache memakai aturan rewrite dari `public/.htaccess` yang sudah tersedia.

### 3. Install dependency

Jalankan dari root project:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika hosting tidak menyediakan Node.js, lakukan dua perintah npm tersebut secara lokal menggunakan versi Node.js yang kompatibel, lalu upload `public/build` ke server.

### 4. Buat dan isi `.env`

```bash
cp .env.example .env
php artisan key:generate --force
```

Minimal sesuaikan nilai berikut:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=nama_user_database
DB_PASSWORD=password_database

FILESYSTEM_DISK=local
STORAGE_URL=https://domain-anda.example/storage
```

Gunakan `APP_KEY` baru dan rahasiakan file `.env`. Jangan commit `.env` ke repository publik.

### 5. Database dan storage foto

Buat database serta user MySQL terlebih dahulu, kemudian jalankan migration production:

```bash
php artisan migrate --force
php artisan storage:link
```

Perintah `migrate --seed` hanya untuk instalasi awal/development. Jangan menjalankan `migrate:fresh` di production karena akan menghapus data.

Foto perumahan, foto bukti laporan, dan foto hasil perbaikan disimpan pada `storage/app/public`. Pastikan symbolic link berikut tersedia:

```text
public/storage -> storage/app/public
```

Jika shared hosting tidak mengizinkan `php artisan storage:link`, buat symbolic link melalui SSH/cPanel Terminal. Jangan memindahkan folder `storage/app/public` ke `public/images`.

### 6. Cache, permission, dan akses aplikasi

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

User web server harus dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Contoh pada Linux:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

Setelah deploy atau mengubah `.env`, ulangi `php artisan optimize:clear` lalu cache kembali.

### 7. Akun admin awal

Untuk membuat akun awal, gunakan seeder hanya bila database masih kosong:

```bash
php artisan db:seed --class=DatabaseSeeder --force
```

Segera ganti password akun bawaan setelah login. Jangan menjalankan seeder berulang kali tanpa memeriksa isi database terlebih dahulu.

### 8. Queue, scheduler, dan backup

Jika queue database digunakan, jalankan worker melalui Supervisor, systemd, atau service manager hosting:

```bash
php artisan queue:work --tries=3
```

Tambahkan cron Laravel bila scheduler digunakan:

```cron
* * * * * cd /home/username/apps/perumahan-backend && php artisan schedule:run >> /dev/null 2>&1
```

Buat backup rutin database dan folder `storage/app/public`, karena data foto tidak tersimpan di database.

### 9. Checklist setelah deploy

- Buka `https://domain-anda.example` dan `/admin`.
- Pastikan login admin berhasil.
- Pastikan daftar perumahan, peta, dan GeoJSON dapat dibuka.
- Upload satu foto perumahan dan satu foto laporan, lalu pastikan URL fotonya dapat diakses.
- Pastikan form `/lapor` dapat mengirim laporan.
- Periksa `storage/logs/laravel.log` jika terjadi error.
- Pastikan `APP_DEBUG=false` dan `.env` tidak dapat diunduh dari browser.

## Catatan Teknis

- Leaflet dan Chart.js dimuat dari CDN dengan Subresource Integrity (SRI). Saat menaikkan versi library, hash integrity pada view terkait harus diperbarui.
- Tile OpenStreetMap membutuhkan akses internet dan tetap harus menampilkan atribusi OpenStreetMap.
- Pencarian perumahan publik dan admin menggunakan scope filter yang sama pada `App\Models\Perumahan`.
- Statistik dashboard dan data peta menggunakan cache; perubahan melalui controller akan menghapus cache yang relevan.
- Form laporan memakai honeypot, rate limit, validasi kondisional, dan opsional Cloudflare Turnstile untuk mengurangi spam.
- Data laporan dan foto dihapus permanen ketika laporan dihapus dari admin.
