<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the Laravel port needs on top of the legacy schema:
 *  - citizens.password widened so legacy MD5 hashes can be transparently upgraded to bcrypt on login
 *  - citizens.remember_token for Laravel's "remember me"
 *  - sessions table (session driver = database is supported; file is the default)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->string('password', 255)->nullable()->default(null)->change();
            $table->string('remember_token', 100)->nullable()->after('userip');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn('remember_token');
            $table->string('password', 32)->nullable()->default(null)->change();
        });
    }
};
