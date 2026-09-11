<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (activity_logs) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log', function (Blueprint $table) {
            $table->bigIncrements('logID');
            $table->string('Type', 8);
            $table->bigInteger('CitID')->default(0);
            $table->string('Param', 15);
            $table->text('Desc');
            $table->integer('timestamp');
            $table->index(['Type', 'CitID', 'timestamp'], 'log_type_ix');
        });

        Schema::create('log_consume', function (Blueprint $table) {
            $table->integer('citID');
            $table->integer('day');
            $table->integer('food');
            $table->integer('house');
            $table->double('change');
            $table->integer('timestamp');
            $table->index('citID', 'log_consume_citid_ix');
        });

        Schema::create('log_consume_healthkit', function (Blueprint $table) {
            $table->integer('citID');
            $table->integer('day');
            $table->integer('healthkit');
            $table->integer('house');
            $table->double('change');
            $table->integer('timestamp');
            $table->index('citID', 'log_consume_healthkit_citid_ix');
        });

        Schema::create('log_donations', function (Blueprint $table) {
            $table->bigIncrements('logID');
            $table->bigInteger('FromID')->default(0);
            $table->string('FromType', 7)->default('');
            $table->bigInteger('ToID')->default(0);
            $table->string('ToType', 7)->default('');
            $table->set('sentBy', ['from', 'to'])->default('from');
            $table->integer('Type')->default(0);
            $table->bigInteger('Amount')->default(0);
            $table->integer('timestamp')->default(0);
            $table->index('FromID', 'log_donations_fromid_ix');
            $table->index('FromType', 'log_donations_fromtype_ix');
            $table->index('ToID', 'log_donations_toid_ix');
            $table->index('ToType', 'log_donations_totype_ix');
        });

        Schema::create('log_ep_gains', function (Blueprint $table) {
            $table->bigIncrements('repID');
            $table->integer('citID')->default(0);
            $table->integer('Amount')->default(0);
            $table->string('Why', 25)->default('');
            $table->integer('timestamp')->default(0);
            $table->index('citID', 'log_ep_gains_citid_ix');
        });

        Schema::create('log_exploring', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('CitizenID');
            $table->integer('Day');
            $table->integer('timestamp');
            $table->integer('duration');
            $table->string('wellness', 15);
            $table->double('chance');
            $table->string('ep', 9);
            $table->double('points');
            $table->string('totpoints', 14);
            $table->double('received');
            $table->index(['CitizenID', 'Day'], 'log_exploring_citizenid_ix');
        });

        Schema::create('log_training', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('CitizenID');
            $table->integer('Day');
            $table->integer('timestamp');
            $table->set('type', ['1', '2', '3']);
            $table->string('wellness', 15);
            $table->string('skill', 15);
            $table->double('ep');
            $table->double('received');
            $table->index(['CitizenID', 'Day'], 'log_training_citizenid_ix');
        });

        Schema::create('log_working', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('CitizenID');
            $table->integer('CompanyID');
            $table->integer('Day');
            $table->integer('timestamp');
            $table->set('type', ['1', '2', '3']);
            $table->string('wellness', 15);
            $table->string('skill', 15);
            $table->string('ep', 8);
            $table->double('products');
            $table->double('tooldec');
            $table->double('salary');
            $table->double('formula_base');
            $table->integer('formula_cfactor');
            $table->integer('formula_impind');
            $table->double('tax');
            $table->integer('curID');
            $table->index(['CitizenID', 'CompanyID', 'Day'], 'log_working_citizenid_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_working');
        Schema::dropIfExists('log_training');
        Schema::dropIfExists('log_exploring');
        Schema::dropIfExists('log_ep_gains');
        Schema::dropIfExists('log_donations');
        Schema::dropIfExists('log_consume_healthkit');
        Schema::dropIfExists('log_consume');
        Schema::dropIfExists('log');
    }
};
