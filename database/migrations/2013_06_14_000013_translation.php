<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (translation) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trans_strings', function (Blueprint $table) {
            $table->increments('strID');
            $table->string('location', 15);
            $table->string('phrase', 30);
            $table->text('trans_en');
            $table->text('trans_fa')->nullable();
            $table->text('trans_ro')->nullable();
            $table->text('trans_hu')->nullable();
            $table->text('trans_pl')->nullable();
            $table->text('trans_rs')->nullable();
            $table->text('trans_hr')->nullable();
            $table->text('trans_es')->nullable();
            $table->text('trans_br')->nullable();
            $table->text('trans_pt')->nullable();
            $table->text('trans_ru')->nullable();
            $table->text('trans_de')->nullable();
            $table->text('trans_fr')->nullable();
            $table->text('trans_it')->nullable();
            $table->text('trans_tr')->nullable();
            $table->text('trans_ua')->nullable();
            $table->text('trans_si')->nullable();
            $table->text('trans_kr')->nullable();
            $table->text('trans_me')->nullable();
            $table->unique('phrase', 'trans_strings_phrase_uq');
            $table->index('location', 'trans_strings_location_ix');
        });

        Schema::create('trans_suggests', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('phrase', 30);
            $table->string('lang', 2);
            $table->integer('byID');
            $table->text('str');
            $table->index(['phrase', 'lang'], 'trans_suggests_phrase_ix');
            $table->index('byID', 'trans_suggests_byid_ix');
        });

        Schema::create('trans_team', function (Blueprint $table) {
            $table->integer('citID')->primary();
            $table->string('langID', 2)->default('en');
            $table->integer('post')->default(9);
            $table->index(['langID', 'post'], 'trans_team_langid_ix');
        });

        Schema::create('trans_news', function (Blueprint $table) {
            $table->increments('ID');
            $table->text('body');
            $table->integer('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_news');
        Schema::dropIfExists('trans_team');
        Schema::dropIfExists('trans_suggests');
        Schema::dropIfExists('trans_strings');
    }
};
