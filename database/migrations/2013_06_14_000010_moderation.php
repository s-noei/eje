<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (moderation) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lens_info', function (Blueprint $table) {
            $table->string('ModID', 8)->primary();
            $table->string('Password', 32);
            $table->string('LensID', 32);
            $table->integer('AssignedTo');
            $table->integer('Access');
            $table->integer('AccessD');
            $table->integer('at_citizen');
            $table->integer('at_company');
            $table->integer('at_country');
            $table->integer('at_elections');
            $table->integer('at_transactions');
            $table->string('at_tickets', 8);
            $table->integer('at_multrack');
            $table->integer('at_payments');
            $table->integer('at_ads');
            $table->integer('at_mods');
            $table->integer('at_punishment');
            $table->integer('at_viewmoney');
            $table->integer('ModVio');
            $table->index(['LensID', 'AssignedTo'], 'lens_info_lensid_ix');
            $table->index('Password', 'lens_info_password_ix');
            $table->index('AssignedTo', 'lens_info_assignedto_ix');
        });

        Schema::create('lens_registration', function (Blueprint $table) {
            $table->integer('citID')->primary();
            $table->string('invID', 32);
            $table->string('invBy', 8);
            $table->integer('access');
            $table->string('fName', 30);
            $table->string('lName', 30);
            $table->integer('country');
            $table->string('language', 2);
            $table->integer('birth_year');
            $table->integer('birth_month');
            $table->integer('birth_day');
            $table->string('proof', 32);
            $table->integer('status');
            $table->index('invBy', 'lens_registration_invby_ix');
            $table->index('country', 'lens_registration_country_ix');
        });

        Schema::create('mod_comments', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('Type', 8);
            $table->integer('TypeID');
            $table->integer('By');
            $table->string('ByMod', 8)->nullable()->default(null);
            $table->text('Body');
            $table->integer('timestamp');
            $table->index(['Type', 'By'], 'mod_comments_type_ix');
            $table->index('TypeID', 'mod_comments_typeid_ix');
            $table->index('ByMod', 'mod_comments_bymod_ix');
        });

        Schema::create('mod_logs', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('modID');
            $table->string('IP', 15);
            $table->string('prevPage', 50);
            $table->string('Page', 50);
            $table->integer('timestamp');
            $table->index('modID', 'mod_logs_modid_ix');
        });

        Schema::create('forfeit_types', function (Blueprint $table) {
            $table->increments('forfeitID');
            $table->string('Title', 30);
            $table->integer('Points');
        });

        Schema::create('forfeit_forfeits', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('forfeitID');
            $table->integer('citID');
            $table->integer('byID');
            $table->string('Title', 30);
            $table->text('Description');
            $table->integer('Points');
            $table->integer('timestamp');
            $table->integer('Expire');
            $table->integer('Active')->default(1);
            $table->text('appeal')->nullable();
            $table->text('appeal_reply')->nullable();
            $table->index(['forfeitID', 'citID'], 'forfeit_forfeits_forfeitid_ix');
            $table->index('byID', 'forfeit_forfeits_byid_ix');
            $table->index('Points', 'forfeit_forfeits_points_ix');
            $table->index('Expire', 'forfeit_forfeits_expire_ix');
            $table->index('Active', 'forfeit_forfeits_active_ix');
        });

        Schema::create('ticket_head', function (Blueprint $table) {
            $table->increments('ticket_id');
            $table->integer('by_id')->default(0);
            $table->string('by_name', 20)->default('');
            $table->string('by_email', 30)->default('');
            $table->string('reason', 15)->default('');
            $table->integer('country');
            $table->string('subject', 100);
            $table->string('proof', 60)->nullable()->default(null);
            $table->tinyInteger('priority')->default(0);
            $table->integer('started_time')->default(0);
            $table->integer('last_reply')->default(0);
            $table->string('last_replier', 30);
            $table->tinyInteger('status')->default(0);
            $table->index('by_id', 'ticket_head_by_id_ix');
            $table->index('priority', 'ticket_head_priority_ix');
        });

        Schema::create('ticket_posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->integer('ticket_id')->default(0);
            $table->integer('by_id')->default(0);
            $table->string('by_name', 30)->default('');
            $table->text('body');
            $table->string('proof', 75)->nullable()->default(null);
            $table->integer('auto_answer')->default(0);
            $table->integer('rate')->default(0);
            $table->integer('timestamp')->default(0);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('repID');
            $table->string('repType', 7);
            $table->integer('citID')->default(0);
            $table->string('Desc', 40);
            $table->integer('timestamp')->default(0);
            $table->index(['repType', 'citID', 'Desc'], 'reports_reptype_ix');
            $table->index(['citID', 'Desc'], 'reports_citid_ix');
            $table->index('Desc', 'reports_desc_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('ticket_posts');
        Schema::dropIfExists('ticket_head');
        Schema::dropIfExists('forfeit_forfeits');
        Schema::dropIfExists('forfeit_types');
        Schema::dropIfExists('mod_logs');
        Schema::dropIfExists('mod_comments');
        Schema::dropIfExists('lens_registration');
        Schema::dropIfExists('lens_info');
    }
};
