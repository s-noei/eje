<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (military) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alliances', function (Blueprint $table) {
            $table->increments('allyID');
            $table->integer('Country1')->default(0);
            $table->integer('Country2')->default(0);
            $table->integer('Expire')->default(0);
        });

        Schema::create('wars', function (Blueprint $table) {
            $table->increments('warID');
            $table->set('Type', ['war', 'revolt'])->default('war');
            $table->integer('Attacker')->default(0);
            $table->integer('Defender')->default(0);
            $table->integer('allies')->default(0);
            $table->string('ally_def', 130)->nullable()->default(null);
            $table->string('ally_att', 130)->nullable()->default(null);
            $table->integer('Start')->default(0);
            $table->integer('End')->default(0);
            $table->integer('defPoints')->default(0);
            $table->integer('winID')->nullable()->default(null);
            $table->integer('winDue')->nullable()->default(null);
            $table->index('Type', 'wars_type_ix');
            $table->index('Attacker', 'wars_attacker_ix');
            $table->index('Defender', 'wars_defender_ix');
            $table->index('allies', 'wars_allies_ix');
            $table->index('ally_def', 'wars_ally_def_ix');
            $table->index('ally_att', 'wars_ally_att_ix');
            $table->index('Start', 'wars_start_ix');
            $table->index('End', 'wars_end_ix');
        });

        Schema::create('battles', function (Blueprint $table) {
            $table->increments('battleID');
            $table->set('battle_type', ['battle', 'revolt'])->default('battle');
            $table->integer('warID')->default(0);
            $table->integer('regionID')->default(0);
            $table->integer('Attacker');
            $table->integer('Defender')->default(0);
            $table->string('ally_att', 130)->nullable()->default(null);
            $table->string('ally_def', 130)->nullable()->default(null);
            $table->decimal('Trust', 10, 0)->default(0);
            $table->bigInteger('wall')->default(0);
            $table->integer('extra')->default(0);
            $table->integer('sPoint')->default(0);
            $table->integer('Start')->default(0);
            $table->integer('End_P1')->nullable()->default(null);
            $table->integer('End')->default(0);
            $table->integer('endFlag');
            $table->set('Result', ['', 'conq', 'secu', 'attreat', 'defreat'])->default('');
            $table->integer('stat_totfights')->nullable()->default(null);
            $table->integer('stat_totdmg')->nullable()->default(null);
            $table->double('stat_trophy')->nullable()->default(null);
            $table->index('End_P1', 'battles_end_p1_ix');
            $table->index('warID', 'battles_warid_ix');
            $table->index(['ally_att', 'ally_def'], 'battles_ally_att_ix');
            $table->index('ally_def', 'battles_ally_def_ix');
            $table->index('ally_att', 'battles_ally_att_2_ix');
            $table->index('Result', 'battles_result_ix');
        });

        Schema::create('fights', function (Blueprint $table) {
            $table->increments('fightID');
            $table->integer('fighterID')->default(0);
            $table->integer('countryID')->default(0);
            $table->integer('battleID')->default(0);
            $table->integer('weapon')->default(0);
            $table->integer('damage')->default(0);
            $table->integer('timestamp')->default(0);
            $table->index('battleID', 'fights_battleid_ix');
            $table->index('countryID', 'fights_countryid_ix');
            $table->index('damage', 'fights_damage_ix');
            $table->index('fighterID', 'fights_fighterid_ix');
        });

        Schema::create('military_unit', function (Blueprint $table) {
            $table->bigIncrements('mID');
            $table->string('mName', 30);
            $table->bigInteger('mOwner')->default(0);
            $table->integer('mCountryID')->default(0);
            $table->bigInteger('mCommander')->default(0);
            $table->bigInteger('mCaptain')->default(0);
            $table->string('mLogo', 45)->default('noavatar.gif');
            $table->string('mText', 200);
            $table->integer('mBattleID')->default(0);
            $table->integer('timestamp');
            $table->integer('mMembers');
            $table->bigInteger('mMedal')->nullable()->default(null);
            $table->index('mCountryID', 'military_unit_mcountryid_ix');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->increments('eventID');
            $table->set('Type', ['att', 'dec', 'pce', 'conq', 'secu', 'revolt', 'rage']);
            $table->integer('Country1')->default(0);
            $table->integer('Country2')->default(0);
            $table->string('refID', 15)->default('0');
            $table->integer('timestamp')->default(0);
            $table->index('Country1', 'events_country1_ix');
            $table->index('Country2', 'events_country2_ix');
            $table->index('timestamp', 'events_timestamp_ix');
            $table->index('Type', 'events_type_ix');
            $table->index('refID', 'events_refid_ix');
        });

        Schema::create('log_rageshots', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('quality');
            $table->integer('battleID');
            $table->integer('attacker');
            $table->integer('toCoun');
            $table->integer('toReg');
            $table->integer('timestamp');
            $table->index(['battleID', 'attacker', 'toCoun', 'toReg'], 'log_rageshots_battleid_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_rageshots');
        Schema::dropIfExists('events');
        Schema::dropIfExists('military_unit');
        Schema::dropIfExists('fights');
        Schema::dropIfExists('battles');
        Schema::dropIfExists('wars');
        Schema::dropIfExists('alliances');
    }
};
