<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Workshop stats: craft (productivity) and efficiency (wellness cost), 0..7, seeded from the old work skill. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->unsignedTinyInteger('craft')->default(0)->after('train_streak');
            $table->unsignedTinyInteger('efficiency')->default(0)->after('craft');
        });
        DB::statement('UPDATE citizens SET craft = LEAST(7, wSkill), efficiency = LEAST(7, wSkill)');
    }

    public function down(): void
    {
        Schema::table('citizens', fn (Blueprint $table) => $table->dropColumn(['craft', 'efficiency']));
    }
};
