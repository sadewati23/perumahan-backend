# Deployment ke `dprkp.cirebonkota.go.id`

Panduan deployment aplikasi Laravel 12 ke domain:

```text
https://dprkp.cirebonkota.go.id
```

## 1. Document root

Simpan source project di luar folder publik web, lalu arahkan document root
domain ke folder `public` Laravel:

```text
/home/USER/apps/perumahan-backend/public
```

Jangan mengarahkan domain ke root project karena `.env`, `app`, `config`, dan
`storage` harus tetap privat.

Pastikan DNS domain sudah mengarah ke server hosting dan SSL/HTTPS sudah aktif.

## 2. Install project

Jalankan dari root project:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika Node.js tidak tersedia di hosting, jalankan `npm ci` dan `npm run build`
di komputer lokal, kemudian upload folder `public/build`.

## 3. Konfigurasi `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dprkp.cirebonkota.go.id
TRUSTED_HOSTS=
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
STORAGE_URL=/storage
```

`STORAGE_URL=/storage` membuat URL foto mengikuti domain dan protokol HTTPS.
Jangan menggunakan URL `localhost` atau URL XAMPP subfolder pada production.

Jangan mengunggah `.env` ke repository publik dan jangan mengaktifkan
`APP_DEBUG` pada server production.

Untuk reverse proxy, isi `TRUSTED_PROXIES` hanya dengan IP/CIDR proxy yang
benar-benar digunakan. Jangan memakai `*` pada production karena akan
diabaikan aplikasi. Header `X-Forwarded-Host` dan `X-Forwarded-Prefix` tidak
dipercaya; pastikan proxy meneruskan host publik yang benar.

Jangan menjalankan `DatabaseSeeder` pada production. Buat akun administrator
melalui prompt interaktif berikut (password tidak menjadi argumen command):

```bash
php artisan admin:buat admin@dprkp.cirebonkota.go.id
```

Setelah deployment, jalankan pemeriksaan konfigurasi. Perintah gagal bila
masih terdapat akun bawaan atau konfigurasi kritis:

```bash
php artisan produksi:periksa
```

## 4. Database dan storage foto

Buat database dan user MySQL, lalu jalankan:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
```

Target storage harus tersedia:

```text
public/storage -> storage/app/public
```

Foto perumahan dan foto laporan disimpan pada `storage/app/public`.

Jika shared hosting tidak mengizinkan `storage:link`, buat symbolic link melalui
SSH atau cPanel Terminal. Jangan memindahkan foto ke `public/images`.

## 5. Cache dan permission

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

Contoh Linux:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

## 6. Perintah yang tidak boleh dijalankan di production

Jangan menjalankan perintah berikut karena menghapus seluruh data:

```bash
php artisan migrate:fresh
php artisan db:wipe
```

## 7. Pengujian setelah deployment

Buka URL berikut melalui HTTPS:

```text
https://dprkp.cirebonkota.go.id/
https://dprkp.cirebonkota.go.id/login
https://dprkp.cirebonkota.go.id/perumahan
https://dprkp.cirebonkota.go.id/admin/perumahan/create
```

Checklist:

- Login admin berhasil.
- Daftar dan detail perumahan dapat dibuka.
- Peta dan GeoJSON dapat dibuka.
- Upload foto perumahan berhasil dan foto tampil di halaman publik.
- Upload foto laporan berhasil.
- Form `/lapor` dapat digunakan.
- `.env` tidak dapat diakses melalui browser.
- `APP_DEBUG=false`.
- Error dapat diperiksa di `storage/logs/laravel.log`.

## 8. Backup

Buat backup rutin untuk:

- Database MySQL/MariaDB.
- Folder `storage/app/public`, karena file foto tidak disimpan di database.
