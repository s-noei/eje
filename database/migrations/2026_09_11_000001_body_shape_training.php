<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Body-shape training: strength (weights) and stamina (cardio), 0..7, plus the training streak.
 * Existing players keep their power: both stats start at min(7, old military skill).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->unsignedTinyInteger('strength')->default(0)->after('mSkill');
            $table->unsignedTinyInteger('stamina')->default(0)->after('strength');
            $table->unsignedSmallInteger('train_streak')->default(0)->after('stamina');
        });
        DB::statement('UPDATE citizens SET strength = LEAST(7, mSkill), stamina = LEAST(7, mSkill)');
    }

    public function down(): void
    {
        Schema::table('citizens', fn (Blueprint $table) => $table->dropColumn(['strength', 'stamina', 'train_streak']));
    }
};
