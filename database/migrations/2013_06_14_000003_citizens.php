<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (citizens) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizens', function (Blueprint $table) {
            $table->bigIncrements('CitizenID');
            $table->string('name', 30)->nullable()->default(null);
            $table->string('oldname', 30);
            $table->integer('editnametime');
            $table->string('password', 32)->nullable()->default(null);
            $table->set('accType', ['citizen', 'co-account', 'nca'])->default('citizen');
            $table->integer('accOwner')->nullable()->default(null);
            $table->text('aboutme')->nullable();
            $table->char('female', 1)->default('0');
            $table->string('email', 50)->nullable()->default(null);
            $table->integer('nationality')->default(0);
            $table->integer('regionID')->nullable()->default(null);
            $table->integer('joined')->nullable()->default(null);
            $table->float('wellness')->default(100);
            $table->integer('poisoning')->default(0);
            $table->bigInteger('CompanyID')->nullable()->default(null);
            $table->integer('rowWorkedStart')->nullable()->default(null);
            $table->integer('LastWorked')->nullable()->default(null);
            $table->integer('LastTrained')->default(-1);
            $table->integer('LastExplored');
            $table->integer('LastLoggedIn')->nullable()->default(null);
            $table->integer('activeTask')->default(1);
            $table->integer('LastJuiceDay')->default(0);
            $table->integer('LastJuiceAmount')->default(0);
            $table->integer('LastJuiceWellness');
            $table->integer('LastDayWP')->default(0);
            $table->integer('referrer');
            $table->char('active', 1)->default('0');
            $table->set('v2prev', ['0', '1'])->default('0');
            $table->string('actLink', 32);
            $table->integer('ep')->default(0);
            $table->tinyInteger('puberty');
            $table->tinyInteger('userlevel')->unsigned()->default(0);
            $table->integer('timestamp')->unsigned()->default(0);
            $table->string('userid', 32)->nullable()->default(null);
            $table->string('userip', 32);
            $table->string('Avatar', 45)->default('noavatar.gif');
            $table->integer('dDeath')->nullable()->default(null);
            $table->integer('npID')->default(0);
            $table->float('wSP')->default(0);
            $table->integer('wSkill')->default(1);
            $table->float('mSP')->default(0);
            $table->integer('mSkill')->default(1);
            $table->integer('medals_hardwork');
            $table->integer('medals_lm');
            $table->integer('medals_pp');
            $table->integer('medals_cg');
            $table->integer('medals_cp');
            $table->integer('medals_revolt');
            $table->integer('medals_hero');
            $table->integer('medals_am')->default(0);
            $table->integer('medals_ap')->default(0);
            $table->integer('medals_gg')->default(0);
            $table->integer('medals_is')->default(0);
            $table->integer('medals_mh')->default(0);
            $table->integer('medals_mp')->default(0);
            $table->integer('medals_wf')->default(0);
            $table->double('mines_advance');
            $table->integer('mines_tried');
            $table->integer('mines_done');
            $table->integer('forum_posts')->default(0);
            $table->integer('fight_count');
            $table->integer('total_damage');
            $table->integer('mRank');
            $table->integer('plusExpire');
            $table->integer('proExpire');
            $table->integer('occDue');
            $table->integer('cron_lastcheck');
            $table->text('cron_data');
            $table->string('setting_theme', 6)->default('green');
            $table->char('setting_lang', 2)->default('en');
            $table->string('setting_font', 15)->default('Arial');
            $table->string('setting_bg', 100)->default('Default');
            $table->text('secu_question');
            $table->text('secu_answer');
            $table->string('ban_due', 11);
            $table->set('ban_type', ['', 'jail', 'ban'])->default('');
            $table->text('ban_reason');
            $table->integer('stat_maxadvance')->default(0);
            $table->integer('stat_rank_int');
            $table->integer('stat_rank_coun_id');
            $table->integer('stat_rank_coun');
            $table->integer('LastCBsOpened')->default(0);
            $table->integer('LastDayClinic')->default(0);
            $table->integer('LastDaily')->default(0);
            $table->integer('gold_pack');
            $table->integer('gold_pack_q')->default(0);
            $table->bigInteger('military_unit')->default(0);
            $table->integer('military_unit_q')->default(0);
            $table->integer('dmg_booster')->default(0);
            $table->integer('dmg_booster_q')->default(0);
            $table->index('regionID', 'citizens_regionid_ix');
            $table->index('name', 'citizens_name_ix');
            $table->index('accType', 'citizens_acctype_ix');
            $table->index('npID', 'citizens_npid_ix');
            $table->index('accOwner', 'citizens_accowner_ix');
            $table->index('wellness', 'citizens_wellness_ix');
            $table->index('ban_due', 'citizens_ban_due_ix');
            $table->index('stat_rank_int', 'citizens_stat_rank_ix');
            $table->index('stat_rank_coun_id', 'citizens_stat_rank_coun_id_ix');
            $table->index('stat_rank_coun', 'citizens_stat_rank_coun_ix');
            $table->index('mSP', 'citizens_mskill_ix');
            $table->index('wSP', 'citizens_wskill_ix');
            $table->index('medals_lm', 'citizens_medals_lm_ix');
            $table->index('medals_pp', 'citizens_medals_pp_ix');
            $table->index('medals_cg', 'citizens_medals_cg_ix');
            $table->index('medals_cp', 'citizens_medals_cp_ix');
            $table->index('medals_revolt', 'citizens_medals_revolt_ix');
            $table->index('medals_hero', 'citizens_medals_hero_ix');
            $table->index('fight_count', 'citizens_fight_count_ix');
            $table->index('total_damage', 'citizens_total_damage_ix');
            $table->index('v2prev', 'citizens_v2prev_ix');
            $table->index('setting_font', 'citizens_setting_font_ix');
            $table->index('setting_bg', 'citizens_setting_bg_ix');
            $table->index('wSkill', 'citizens_wskill_2_ix');
            $table->index('stat_maxadvance', 'citizens_stat_maxadvance_ix');
        });

        Schema::create('citizen_money', function (Blueprint $table) {
            $table->increments('ID');
            $table->bigInteger('CitID')->nullable()->default(null);
            $table->integer('CurID')->nullable()->default(null);
            $table->double('Amount')->default(0);
            $table->index(['CitID', 'CurID'], 'citizen_money_citid_ix');
        });

        Schema::create('citizen_diaries', function (Blueprint $table) {
            $table->increments('dID');
            $table->integer('citID');
            $table->string('dType', 7);
            $table->integer('Day');
            $table->text('Param');
            $table->index('citID', 'citizen_diaries_citid_ix');
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->bigIncrements('pID');
            $table->integer('Type')->default(0);
            $table->tinyInteger('Stars')->default(0);
            $table->bigInteger('Owner')->default(0);
            $table->tinyInteger('Usable')->default(1);
            $table->integer('Expires')->default(0);
            $table->index(['Type', 'Owner', 'Usable'], 'inventory_type_ix');
            $table->index('Owner', 'inventory_owner_ix');
            $table->index('Usable', 'inventory_usable_ix');
        });

        Schema::create('invites', function (Blueprint $table) {
            $table->string('inviteID', 32)->default('')->primary();
            $table->integer('byID')->default(0);
            $table->integer('toID')->default(0);
            $table->string('emailID', 40)->default('');
            $table->integer('sended');
            $table->set('Valid', ['0', '1'])->default('1');
        });

        Schema::create('prizes', function (Blueprint $table) {
            $table->integer('citizenID')->default(0)->primary();
            $table->integer('countryID')->default(0);
        });

        Schema::create('log_logins', function (Blueprint $table) {
            $table->integer('citID');
            $table->string('ip', 15);
            $table->string('session', 40);
            $table->string('agent', 32);
            $table->integer('timestamp');
            $table->index(['citID', 'ip', 'session', 'agent', 'timestamp'], 'log_logins_citid_ix');
        });

        Schema::create('log_nchange', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('CitizenID');
            $table->integer('oNation');
            $table->integer('nNation');
            $table->text('reason');
            $table->integer('timestamp');
            $table->integer('approved')->default(0);
            $table->index(['oNation', 'nNation'], 'log_nchange_onation_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_nchange');
        Schema::dropIfExists('log_logins');
        Schema::dropIfExists('prizes');
        Schema::dropIfExists('invites');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('citizen_diaries');
        Schema::dropIfExists('citizen_money');
        Schema::dropIfExists('citizens');
    }
};
