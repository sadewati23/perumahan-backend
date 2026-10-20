# Panduan Hosting Sistem Informasi Data Perumahan

Panduan deployment Laravel 12 ke VPS, shared hosting, atau cPanel.

## 1. Persyaratan Server

- PHP 8.2 atau lebih baru.
- Ekstensi PHP: `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, dan `gd`.
- Composer 2.x.
- MySQL/MariaDB.
- Node.js/npm untuk build asset, atau build `public/build` di komputer lokal.
- HTTPS dan dukungan symbolic link untuk foto upload.

## 2. Upload Project

Simpan project di luar folder publik web:

```text
/home/username/apps/perumahan-backend/
```

Arahkan document root domain ke folder `public`:

```text
/home/username/apps/perumahan-backend/public
```

Jangan mengarahkan domain ke root project karena file `.env`, `app`, `config`, dan `storage` harus tetap privat.

## 3. Install Dependency

Jalankan dari root project:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Jika hosting tidak menyediakan Node.js, jalankan `npm ci` dan `npm run build` di komputer lokal, lalu upload folder `public/build`.

## 4. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate --force
```

Atur `.env` untuk production:

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

Jangan commit `.env` ke repository publik.

## 5. Database dan Foto

Buat database dan user MySQL, kemudian jalankan:

```bash
php artisan migrate --force
php artisan storage:link
```

Foto disimpan pada `storage/app/public` dan diakses melalui:

```text
public/storage -> storage/app/public
```

Jika `storage:link` tidak dapat dijalankan di shared hosting, buat symbolic link melalui SSH atau cPanel Terminal.

Jangan menjalankan perintah berikut di production:

```bash
php artisan migrate:fresh
```

Perintah tersebut menghapus seluruh data.

## 6. Cache dan Permission

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan user web server dapat menulis ke:

```text
storage/
bootstrap/cache/
```

Contoh Linux:

```bash
chmod -R ug+rwX storage bootstrap/cache
```

## 7. Akun Admin

Jika database masih kosong, jalankan seeder:

```bash
php artisan db:seed --class=DatabaseSeeder --force
```

Segera ganti password akun bawaan setelah login.

## 8. Queue dan Scheduler

Jika queue database digunakan, jalankan worker menggunakan Supervisor atau service manager:

```bash
php artisan queue:work --tries=3
```

Jika scheduler digunakan, tambahkan cron:

```cron
* * * * * cd /home/username/apps/perumahan-backend && php artisan schedule:run >> /dev/null 2>&1
```

## 9. Backup

Buat backup rutin untuk:

- Database MySQL/MariaDB.
- Folder `storage/app/public`, karena file foto tidak disimpan di database.

## 10. Checklist Setelah Deploy

- Domain dapat membuka halaman publik dan `/admin`.
- Login admin berhasil.
- Daftar perumahan, peta, dan GeoJSON dapat dibuka.
- Upload foto perumahan dan foto laporan berhasil.
- Form `/lapor` dapat digunakan.
- `APP_DEBUG=false`.
- File `.env` tidak dapat diakses melalui browser.
- Error dapat diperiksa di `storage/logs/laravel.log`.
