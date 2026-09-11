<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Allow bcrypt hashes for lens moderators (legacy column was MD5-sized). */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE lens_info MODIFY Password VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE lens_info MODIFY Password VARCHAR(32) NOT NULL');
    }
};
