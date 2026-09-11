<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (core_settings) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('SettingID');
            $table->string('Title', 20)->default('');
            $table->text('Value')->nullable();
        });

        Schema::create('active_users', function (Blueprint $table) {
            $table->string('username', 30)->default('')->primary();
            $table->integer('timestamp')->unsigned()->default(0);
        });

        Schema::create('active_guests', function (Blueprint $table) {
            $table->string('ip', 15)->default('')->primary();
            $table->integer('timestamp')->unsigned()->default(0);
        });

        Schema::create('cronlog', function (Blueprint $table) {
            $table->bigIncrements('ID');
            $table->set('Type', ['monthly', 'daily', 'hourly'])->default('');
            $table->string('Day', 4)->default('');
            $table->char('Hour', 2)->default('-');
            $table->char('Min', 2)->default('-');
            $table->string('timestamp', 11)->default('');
            $table->text('Report');
        });

        Schema::create('ip2c', function (Blueprint $table) {
            $table->increments('id');
            $table->string('begin_ip', 20)->nullable()->default(null);
            $table->string('end_ip', 20)->nullable()->default(null);
            $table->integer('begin_ip_num')->unsigned()->nullable()->default(null);
            $table->integer('end_ip_num')->unsigned()->nullable()->default(null);
            $table->string('country_code', 3)->nullable()->default(null);
            $table->string('country_name', 150)->nullable()->default(null);
            $table->string('language', 2)->default('en');
            $table->index(['begin_ip_num', 'end_ip_num'], 'ip2c_begin_ip_num_ix');
            $table->index('begin_ip_num', 'ip2c_begin_ip_num_2_ix');
            $table->index('end_ip_num', 'ip2c_end_ip_num_ix');
        });

        Schema::create('dev_news', function (Blueprint $table) {
            $table->increments('newsID');
            $table->set('newsType', ['bugfix', 'minupd', 'majupd']);
            $table->string('ver', 4);
            $table->text('title');
            $table->integer('repBy')->nullable()->default(null);
            $table->integer('timestamp');
            $table->integer('access')->default(0);
            $table->string('link', 60)->nullable()->default(null);
            $table->index('repBy', 'dev_news_repby_ix');
        });

        Schema::create('waiting_list', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Name', 30)->default('');
            $table->string('Email', 45)->default('');
            $table->integer('timestamp')->default(0);
            $table->integer('Usable')->default(1);
        });

        Schema::create('susp_queries', function (Blueprint $table) {
            $table->bigIncrements('queryID');
            $table->integer('citID');
            $table->text('query');
            $table->string('page', 100);
            $table->integer('timestamp');
        });

        Schema::create('susp_slow_queries', function (Blueprint $table) {
            $table->bigIncrements('queryID');
            $table->text('query');
            $table->string('page', 100);
            $table->float('time');
            $table->unique('page', 'susp_slow_queries_page_2_uq');
            $table->index('page', 'susp_slow_queries_page_ix');
        });

        Schema::create('log_speed', function (Blueprint $table) {
            $table->integer('logTime');
            $table->string('Page', 40);
            $table->integer('loadSpeed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_speed');
        Schema::dropIfExists('susp_slow_queries');
        Schema::dropIfExists('susp_queries');
        Schema::dropIfExists('waiting_list');
        Schema::dropIfExists('dev_news');
        Schema::dropIfExists('ip2c');
        Schema::dropIfExists('cronlog');
        Schema::dropIfExists('active_guests');
        Schema::dropIfExists('active_users');
        Schema::dropIfExists('settings');
    }
};
