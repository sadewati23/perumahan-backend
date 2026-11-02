<?php

use App\Support\UsernameBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 30)->nullable()->after('name');
            });
        }

        $terpakai = [];
        foreach (\App\Models\User::query()->orderBy('id')->get(['id', 'email', 'username']) as $user) {
            if (filled($user->username)) {
                $terpakai[] = $user->username;
                continue;
            }

            $username = UsernameBackfill::dari((string) $user->email, $terpakai);
            $user->forceFill(['username' => $username])->saveQuietly();
            $terpakai[] = $username;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->nullable(false)->change();
            $table->unique('username');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }
    }
};
