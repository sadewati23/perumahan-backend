<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TurnstileKonfigurasi;
use Illuminate\Console\Command;
use Throwable;

class PeriksaProduksi extends Command
{
    protected $signature = 'produksi:periksa';

    protected $description = 'Memeriksa kesiapan konfigurasi aplikasi untuk produksi';

    /**
     * Pemeriksaan ini sengaja hanya membaca konfigurasi, filesystem, dan database.
     */
    public function handle(): int
    {
        $rows = [];

        $this->tambah($rows, 'APP_ENV', config('app.env') === 'production' ? 'OK' : 'KRITIS',
            config('app.env') === 'production' ? 'production' : 'harus production');
        $this->tambah($rows, 'APP_DEBUG', config('app.debug') === false ? 'OK' : 'KRITIS',
            config('app.debug') === false ? 'false' : 'harus false');
        $this->tambah($rows, 'APP_KEY', filled(config('app.key')) ? 'OK' : 'KRITIS',
            filled(config('app.key')) ? 'terisi' : 'kosong');

        $https = str_starts_with((string) config('app.url'), 'https://');
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $hostLokal = is_string($appHost) && (in_array(strtolower($appHost), ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with(strtolower($appHost), '.test')
            || str_ends_with(strtolower($appHost), '.local')
            || str_ends_with(strtolower($appHost), '.localhost'));
        $appUrlStatus = config('app.env') === 'production' && $hostLokal ? 'KRITIS' : ($https ? 'OK' : 'PERINGATAN');
        $this->tambah($rows, 'APP_URL', $appUrlStatus, $hostLokal ? 'host lokal tidak boleh di produksi' : ($https ? 'https' : 'bukan https'));
        $this->tambah($rows, 'TRUSTED_HOSTS', config('app.trusted_hosts', []) === [] ? 'INFO' : 'OK',
            config('app.trusted_hosts', []) === [] ? 'hanya host dari APP_URL yang diizinkan' : 'host tambahan terdaftar');
        $this->tambah($rows, 'session.secure', ! $https || config('session.secure') === true ? 'OK' : 'PERINGATAN',
            ! $https ? 'tidak relevan sebelum APP_URL https' : (config('session.secure') === true ? 'true' : 'false'));

        $proxies = config('trustedproxy.proxies');
        $rawProxies = array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', '')));
        $hasWildcardProxy = (is_array($proxies) && in_array('*', $proxies, true))
            || in_array('*', $rawProxies, true);
        $this->tambah($rows, 'TRUSTED_PROXIES', $hasWildcardProxy ? 'KRITIS' : ($proxies ? 'OK' : 'INFO'),
            $hasWildcardProxy ? 'jangan mempercayai semua proxy' : ($proxies ? 'terbatas' : 'kosong = tanpa proxy'));

        $maxInputVars = (int) (ini_get('max_input_vars') ?: 0);
        $this->tambah($rows, 'max_input_vars', $maxInputVars > 0 && $maxInputVars < 3000 ? 'PERINGATAN' : 'OK',
            $maxInputVars > 0 && $maxInputVars < 3000
                ? 'form perumahan dengan banyak baris bisa terpotong diam-diam; naikkan ke 3000'
                : (string) $maxInputVars);

        $mailer = (string) config('mail.default');
        $this->tambah($rows, 'MAIL_MAILER', in_array($mailer, ['log', 'array'], true) ? 'PERINGATAN' : 'OK',
            in_array($mailer, ['log', 'array'], true) ? 'email reset password tidak terkirim' : 'terkonfigurasi');

        $turnstile = config('services.turnstile', []);
        $siteKey = $turnstile['site_key'] ?? null;
        $secret = $turnstile['secret'] ?? null;
        if (TurnstileKonfigurasi::kunciUjiTerpasang()) {
            $turnstileStatus = ['KRITIS', 'kunci uji'];
        } elseif (filled($secret) && blank($siteKey)) {
            $turnstileStatus = ['KRITIS', 'secret terisi, site key kosong'];
        } elseif (blank($secret) && blank($siteKey)) {
            $turnstileStatus = ['PERINGATAN', 'kosong'];
        } else {
            $turnstileStatus = ['OK', filled($secret) && filled($siteKey) ? 'terisi' : 'terisi sebagian'];
        }
        $this->tambah($rows, 'Turnstile', $turnstileStatus[0], $turnstileStatus[1]);

        $timezone = (string) config('app.timezone');
        $this->tambah($rows, 'Zona waktu', $timezone === 'Asia/Jakarta' ? 'OK' : 'PERINGATAN',
            $timezone === 'Asia/Jakarta' ? 'Asia/Jakarta' : 'bukan Asia/Jakarta');

        $this->tambah($rows, 'public/build/manifest.json', is_file(public_path('build/manifest.json')) ? 'OK' : 'KRITIS',
            is_file(public_path('build/manifest.json')) ? 'tersedia' : 'tidak tersedia');
        $this->tambah($rows, 'public/storage', is_dir(public_path('storage')) ? 'OK' : 'KRITIS',
            is_dir(public_path('storage')) ? 'tersedia' : 'tidak tersedia');

        try {
            $akunBawaan = User::query()
                ->whereIn('email', ['admin@perumahan.test', 'petugas@perumahan.test'])
                ->exists();
            $this->tambah($rows, 'Akun bawaan', $akunBawaan ? 'KRITIS' : 'OK',
                $akunBawaan ? 'akun bawaan masih ada' : 'tidak ditemukan');
        } catch (Throwable) {
            $this->tambah($rows, 'Akun bawaan', 'PERINGATAN', 'tidak dapat diperiksa');
        }

        $autoDelete = (bool) config('laporan.hapus_otomatis');
        $deleteSince = config('laporan.hapus_otomatis_sejak');
        $this->tambah($rows, 'Batas penghapusan otomatis', $autoDelete && blank($deleteSince) ? 'PERINGATAN' : 'OK',
            $autoDelete && blank($deleteSince)
                ? 'laporan lama ikut terhapus pada jalan pertama penjadwal'
                : ($autoDelete ? 'terbatas tanggal' : 'tidak aktif'));

        if (config('admin.login_path') !== null) {
            $loginPath = config('admin.login_path');
            $this->tambah($rows, 'Alamat login admin', $loginPath === 'login' ? 'PERINGATAN' : 'OK',
                $loginPath === 'login' ? 'alamat login standar' : 'login tersembunyi');
            if (config('admin.login_tersembunyi') === false) {
                $this->tambah($rows, 'admin.login_tersembunyi', 'INFO', 'false');
            }
        }

        $this->newLine();
        $this->line('Penilaian terhadap standar PRODUKSI (hasil di komputer lokal akan berisi temuan: itu wajar)');
        $this->table(['Pemeriksaan', 'Status', 'Catatan'], $rows);

        $counts = array_count_values(array_column($rows, 'status'));
        $this->line(sprintf(
            'Ringkasan: KRITIS %d, PERINGATAN %d, OK %d, INFO %d',
            $counts['KRITIS'] ?? 0,
            $counts['PERINGATAN'] ?? 0,
            $counts['OK'] ?? 0,
            $counts['INFO'] ?? 0,
        ));

        return ($counts['KRITIS'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @param array<int, array{pemeriksaan: string, status: string, catatan: string}> $rows */
    private function tambah(array &$rows, string $pemeriksaan, string $status, string $catatan): void
    {
        $rows[] = compact('pemeriksaan', 'status', 'catatan');
    }
}
