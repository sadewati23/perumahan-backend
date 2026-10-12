<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements CanResetPasswordContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, CanResetPassword;

    public const ROLE = [
        'admin' => 'Administrator',
        'petugas' => 'Petugas',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'is_active',
        'jabatan',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getLabelRoleAttribute(): string
    {
        return self::ROLE[$this->role] ?? $this->role;
    }

    /**
     * Kirim link reset password lewat notifikasi kustom (email berbahasa Indonesia).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Email pengirim tautan reset password.
     */
    public function routeNotificationForMail(): ?string
    {
        return $this->email;
    }

    /**
     * Email tampil pada tautan reset — dibungkus agar aman di URL.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    /**
     * Token acak baru untuk password reset (helper test / konfigurasi broker).
     */
    public static function tokenBaru(): string
    {
        return Str::random(64);
    }
}
