<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (geography) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country', function (Blueprint $table) {
            $table->increments('CountryID');
            $table->string('cName', 30)->nullable()->default(null);
            $table->string('shortName', 2);
            $table->string('Continent', 10);
            $table->string('Prefix', 20);
            $table->tinyInteger('Hidden');
            $table->integer('noWar');
            $table->tinyInteger('CurID')->nullable()->default(null);
            $table->string('curName', 4);
            $table->string('Flag', 25)->nullable()->default(null);
            $table->integer('cpID')->nullable()->default(null);
            $table->double('cFee')->default(25);
            $table->integer('nFee')->default(4);
            $table->integer('cgCount')->default(0);
            $table->text('welcome_message');
            $table->text('army_message');
            $table->integer('minister_war');
            $table->integer('minister_fa');
            $table->integer('minister_e')->nullable()->default(null);
            $table->integer('nca_war')->nullable()->default(null);
            $table->integer('impind1')->nullable()->default(null);
            $table->integer('impind2')->nullable()->default(null);
            $table->integer('impind3')->nullable()->default(null);
            $table->index('minister_e', 'country_minister_e_ix');
            $table->index('cName', 'country_cname_ix');
        });

        Schema::create('region', function (Blueprint $table) {
            $table->bigIncrements('RegionID');
            $table->string('rName', 40)->nullable()->default(null);
            $table->integer('CountryID')->nullable()->default(null);
            $table->integer('oCountryID');
            $table->integer('Clinic')->default(0);
            $table->integer('Munic')->default(0);
            $table->tinyInteger('isCapital')->default(0);
            $table->integer('capFor');
            $table->integer('hasRoute')->default(0);
            $table->integer('goddessType')->nullable()->default(null);
            $table->integer('goddessDeath')->nullable()->default(null);
            $table->integer('stat_pop')->default(0);
            $table->decimal('stat_value', 10, 2)->default(0.00);
            $table->integer('stat_comps')->nullable()->default(null);
            $table->double('stat_cfactor')->nullable()->default(null);
            $table->index('CountryID', 'region_countryid_ix');
            $table->index('stat_pop', 'region_stat_pop_ix');
            $table->index('stat_value', 'region_stat_value_ix');
            $table->index(['goddessType', 'goddessDeath'], 'region_goddesstype_ix');
            $table->index(['stat_comps', 'stat_cfactor'], 'region_stat_comps_ix');
        });

        Schema::create('neighbors', function (Blueprint $table) {
            $table->bigIncrements('nID');
            $table->bigInteger('Region1')->nullable()->default(null);
            $table->bigInteger('Region2')->nullable()->default(null);
            $table->tinyInteger('Type')->nullable()->default(null);
        });

        Schema::create('map_colors', function (Blueprint $table) {
            $table->increments('CountryID');
            $table->integer('r')->default(0);
            $table->integer('g')->default(0);
            $table->integer('b')->default(0);
            $table->integer('x');
            $table->integer('y');
            $table->set('border', ['0', '1'])->default('1');
        });

        Schema::create('map_cords', function (Blueprint $table) {
            $table->increments('RegionID');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
        });

        Schema::create('map_regions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('l');
            $table->integer('t');
            $table->integer('r');
            $table->integer('b');
            $table->integer('c');
        });

        Schema::create('stat_country', function (Blueprint $table) {
            $table->increments('CountryID');
            $table->integer('ep')->nullable()->default(null);
            $table->integer('pop')->nullable()->default(null);
            $table->double('avgEP')->nullable()->default(null);
            $table->double('avgMilitary')->nullable()->default(null);
            $table->double('avgWorking')->nullable()->default(null);
            $table->integer('numComps')->nullable()->default(null);
            $table->double('native')->nullable()->default(null);
            $table->integer('gdp')->nullable()->default(null);
            $table->double('apc')->nullable()->default(null);
            $table->double('inflation')->nullable()->default(null);
            $table->double('unemp')->nullable()->default(null);
            $table->index(['ep', 'pop', 'avgEP', 'avgWorking', 'numComps', 'gdp', 'apc', 'inflation', 'unemp'], 'stat_country_ep_ix');
            $table->index('avgMilitary', 'stat_country_avgmilitary_ix');
            $table->index('native', 'stat_country_native_ix');
        });

        Schema::create('currency', function (Blueprint $table) {
            $table->bigIncrements('CurID');
            $table->string('Name', 6)->nullable()->default(null);
            $table->string('Flag', 15)->nullable()->default(null);
            $table->double('Exchange')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency');
        Schema::dropIfExists('stat_country');
        Schema::dropIfExists('map_regions');
        Schema::dropIfExists('map_cords');
        Schema::dropIfExists('map_colors');
        Schema::dropIfExists('neighbors');
        Schema::dropIfExists('region');
        Schema::dropIfExists('country');
    }
};
