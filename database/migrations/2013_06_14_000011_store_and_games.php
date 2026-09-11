<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (store_and_games) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_items', function (Blueprint $table) {
            $table->integer('itemno')->primary();
            $table->string('itemtitle', 30);
            $table->string('itemimage', 15);
            $table->set('itemtype', ['TALA', 'ACC'])->default('TALA');
            $table->integer('itemamount');
            $table->float('itemprice');
            $table->float('itempriceht');
        });

        Schema::create('store_sales', function (Blueprint $table) {
            $table->increments('saleID');
            $table->integer('itemno');
            $table->float('price');
            $table->string('currency', 3)->default('USD');
            $table->integer('buyer');
            $table->string('txn_id', 400);
            $table->set('txn_bank', ['Paypal', 'Zarinpal', 'Sepehr', 'Siba', 'PayGol', 'Other'])->default('Paypal');
            $table->string('buyermail', 40);
            $table->string('transtat', 10)->default('Pending');
            $table->integer('timestamp');
            $table->index('currency', 'store_sales_currency_ix');
        });

        Schema::create('chanceboxes', function (Blueprint $table) {
            $table->increments('cbID');
            $table->integer('toID');
            $table->string('reason', 10);
            $table->integer('day');
            $table->integer('points');
            $table->set('canGetTala', ['0', '1'])->default('1');
            $table->double('prize')->nullable()->default(null);
            $table->integer('result')->nullable()->default(null);
            $table->integer('check')->default(0);
            $table->integer('expResult');
            $table->index('toID', 'chanceboxes_toid_ix');
        });

        Schema::create('lottery_stats', function (Blueprint $table) {
            $table->increments('lottery_day');
            $table->integer('lottery_citizens')->default(0);
            $table->integer('lottery_tickets')->default(0);
            $table->integer('lottery_winners')->default(0);
        });

        Schema::create('lottery_tickets', function (Blueprint $table) {
            $table->increments('ticketID');
            $table->integer('buyerID')->default(0);
            $table->integer('timestamp')->default(0);
        });

        Schema::create('lottery_winners', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('ticket')->default(0);
            $table->integer('winnerID')->default(0);
            $table->integer('Day')->default(0);
            $table->text('Prize');
        });

        Schema::create('ads', function (Blueprint $table) {
            $table->increments('adID');
            $table->string('title', 60);
            $table->string('link', 60);
            $table->string('pic', 60)->default('noavatar.gif');
            $table->text('desc');
            $table->integer('creator');
            $table->string('admd5', 32);
            $table->double('cost');
            $table->integer('viewin');
            $table->set('rtl', ['0', '1'])->default('0');
            $table->set('status', ['0', '1', '2'])->default('1');
            $table->integer('edit');
            $table->integer('views');
            $table->integer('clicks');
            $table->index('cost', 'ads_cost_ix');
            $table->index('viewin', 'ads_viewin_ix');
            $table->index('status', 'ads_status_ix');
        });

        Schema::create('log_wbox', function (Blueprint $table) {
            $table->increments('logID');
            $table->integer('citID')->default(0);
            $table->integer('day')->default(0);
            $table->index(['citID', 'day'], 'log_wbox_citid_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_wbox');
        Schema::dropIfExists('ads');
        Schema::dropIfExists('lottery_winners');
        Schema::dropIfExists('lottery_tickets');
        Schema::dropIfExists('lottery_stats');
        Schema::dropIfExists('chanceboxes');
        Schema::dropIfExists('store_sales');
        Schema::dropIfExists('store_items');
    }
};
