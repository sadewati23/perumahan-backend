<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SesiPengguna
{
    public static function cabut(User $user, ?string $kecualiSesiId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id);

        if ($kecualiSesiId !== null) {
            $query->where('id', '<>', $kecualiSesiId);
        }

        $query->delete();

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
    }
}
