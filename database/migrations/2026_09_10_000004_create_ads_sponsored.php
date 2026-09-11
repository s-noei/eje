<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sponsored ads table used by lens/include/ads (absent from the legacy dump; columns inferred from the code). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ads_sponsored')) {
            return;
        }
        Schema::create('ads_sponsored', function (Blueprint $table) {
            $table->increments('adID');
            $table->string('location', 10)->default('top');
            $table->string('title', 100)->default('');
            $table->text('content')->nullable();
            $table->string('link', 255)->default('');
            $table->string('pic', 40)->default('');
            $table->string('language', 2)->default('');
            $table->unsignedInteger('country')->default(0);
            $table->decimal('credit', 12, 3)->default(0);
            $table->unsignedTinyInteger('costType')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->boolean('active')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads_sponsored');
    }
};
