<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (economy) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry', function (Blueprint $table) {
            $table->tinyIncrements('IndustryID');
            $table->string('iName', 20)->nullable()->default(null);
            $table->string('Icon', 25)->nullable()->default(null);
            $table->integer('Unit')->nullable()->default(null);
            $table->integer('xFactor')->default(1);
            $table->set('Hidden', ['0', '1'])->default('0');
        });

        Schema::create('company', function (Blueprint $table) {
            $table->bigIncrements('CompanyID');
            $table->bigInteger('CitID')->nullable()->default(null);
            $table->string('Avatar', 45)->default('noavatar.gif');
            $table->string('Name', 50)->nullable()->default(null);
            $table->integer('ManagerID')->nullable()->default(null);
            $table->tinyInteger('IndustryID')->nullable()->default(null);
            $table->integer('RegionID')->nullable()->default(null);
            $table->set('freemove', ['0', '1'])->default('0');
            $table->integer('Stock')->nullable()->default(null);
            $table->tinyInteger('Stars')->default(1);
            $table->float('Products')->nullable()->default(null);
            $table->tinyInteger('tool_q')->default(0);
            $table->double('tool_e')->default(0);
            $table->tinyInteger('tool_a')->default(0);
            $table->text('company_message');
            $table->integer('sale_base');
            $table->double('sale_step');
            $table->integer('sale_due');
            $table->integer('sale_bid_id');
            $table->double('sale_bid_amount');
            $table->integer('changeTo');
            $table->index('ManagerID', 'company_managerid_ix');
        });

        Schema::create('company_license', function (Blueprint $table) {
            $table->increments('ID');
            $table->bigInteger('CompanyID')->nullable()->default(null);
            $table->integer('CountryID')->nullable()->default(null);
            $table->tinyInteger('Type')->default(0);
            $table->index('Type', 'company_license_type_ix');
            $table->index('CompanyID', 'company_license_companyid_ix');
        });

        Schema::create('company_market', function (Blueprint $table) {
            $table->bigIncrements('offerID');
            $table->integer('CompanyID')->default(0);
            $table->integer('Price')->default(0);
        });

        Schema::create('company_money', function (Blueprint $table) {
            $table->increments('ID');
            $table->bigInteger('CompID')->nullable()->default(null);
            $table->integer('CurID')->nullable()->default(null);
            $table->double('Amount')->default(0);
            $table->index('CompID', 'company_money_compid_ix');
            $table->index('CurID', 'company_money_curid_ix');
        });

        Schema::create('company_workers', function (Blueprint $table) {
            $table->bigIncrements('wID');
            $table->bigInteger('CitizenID')->default(0);
            $table->bigInteger('CompanyID')->default(0);
            $table->float('Salary')->default(0);
            $table->integer('curID');
            $table->integer('Joined')->default(0);
            $table->index('CitizenID', 'company_workers_citizenid_ix');
            $table->index(['CitizenID', 'CompanyID'], 'company_workers_citizenid_2_ix');
            $table->index('CompanyID', 'company_workers_companyid_ix');
        });

        Schema::create('jobOffers', function (Blueprint $table) {
            $table->bigIncrements('joID');
            $table->bigInteger('CompanyID')->default(0);
            $table->integer('CountryID')->default(0);
            $table->tinyInteger('Amount')->default(0);
            $table->tinyInteger('wSkill')->default(0);
            $table->double('Salary')->default(0);
            $table->index('CountryID', 'joboffers_countryid_ix');
            $table->index('CompanyID', 'joboffers_companyid_ix');
            $table->index('Amount', 'joboffers_amount_ix');
        });

        Schema::create('market', function (Blueprint $table) {
            $table->bigIncrements('OfferID');
            $table->bigInteger('CompanyID')->nullable()->default(null);
            $table->integer('Quality')->default(0);
            $table->integer('CountryID')->nullable()->default(null);
            $table->integer('Stock')->nullable()->default(null);
            $table->double('Price')->nullable()->default(null);
            $table->double('tPrice')->default(0);
            $table->integer('timestamp')->unsigned()->nullable()->default(null);
            $table->index('CountryID', 'market_countryid_ix');
        });

        Schema::create('market_sells', function (Blueprint $table) {
            $table->bigIncrements('soldID');
            $table->integer('companyID')->default(0);
            $table->integer('industryID')->default(0);
            $table->integer('Stars')->default(0);
            $table->integer('CountryID')->default(0);
            $table->integer('Day')->default(0);
            $table->integer('Amount')->default(0);
            $table->double('Price')->default(0);
        });

        Schema::create('imarket', function (Blueprint $table) {
            $table->integer('OfferID')->primary();
            $table->integer('SellerID');
            $table->tinyInteger('Type');
            $table->tinyInteger('Quality');
            $table->double('Price');
            $table->integer('endtime');
            $table->index('SellerID', 'imarket_sellerid_ix');
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->bigIncrements('TaxID');
            $table->integer('CountryID')->default(0);
            $table->integer('IndustryID')->default(0);
            $table->smallInteger('Income')->default(10);
            $table->smallInteger('Import')->default(10);
            $table->smallInteger('VAT')->default(10);
            $table->index('CountryID', 'taxes_countryid_ix');
            $table->index('IndustryID', 'taxes_industryid_ix');
        });

        Schema::create('monetary', function (Blueprint $table) {
            $table->bigIncrements('mOfferID');
            $table->bigInteger('sellerID')->default(0);
            $table->string('sellerType', 8)->default('citizen');
            $table->integer('sCurID')->default(0);
            $table->integer('bCurID')->default(0);
            $table->float('Amount')->default(0);
            $table->float('eRate')->default(0);
            $table->string('timestamp', 11)->default('');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('tID');
            $table->integer('fromID')->default(0);
            $table->string('fromType', 7)->default('');
            $table->integer('toID')->default(0);
            $table->string('toType', 7)->default('');
            $table->tinyInteger('curID')->default(0);
            $table->float('Amount')->default(0);
            $table->string('Page', 40)->default('> Old / Not supported <');
            $table->integer('timestamp')->default(0);
            $table->float('fromBef')->default(0);
            $table->float('fromAft')->default(0);
            $table->float('toBef')->default(0);
            $table->float('toAft')->default(0);
            $table->string('refundBy', 8)->nullable()->default(null);
            $table->index('fromID', 'transactions_fromid_ix');
            $table->index('fromType', 'transactions_fromtype_ix');
            $table->index('toID', 'transactions_toid_ix');
            $table->index('toType', 'transactions_totype_ix');
            $table->index('refundBy', 'transactions_refundby_ix');
        });

        Schema::create('embargo', function (Blueprint $table) {
            $table->increments('embID');
            $table->integer('Country1')->default(0);
            $table->integer('Country2')->default(0);
            $table->set('Type', ['trade', 'travel'])->default('');
            $table->set('Reason', ['law', 'war'])->default('');
            $table->integer('Expire')->default(0);
        });

        Schema::create('country_money', function (Blueprint $table) {
            $table->increments('ID');
            $table->bigInteger('CounID')->nullable()->default(null);
            $table->integer('CurID')->nullable()->default(null);
            $table->double('Amount')->default(0);
            $table->index('CounID', 'country_money_counid_ix');
            $table->index('CurID', 'country_money_curid_ix');
        });

        Schema::create('party_money', function (Blueprint $table) {
            $table->increments('ID');
            $table->bigInteger('PartyID')->nullable()->default(null);
            $table->integer('CurID')->nullable()->default(null);
            $table->double('Amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_money');
        Schema::dropIfExists('country_money');
        Schema::dropIfExists('embargo');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('monetary');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('imarket');
        Schema::dropIfExists('market_sells');
        Schema::dropIfExists('market');
        Schema::dropIfExists('jobOffers');
        Schema::dropIfExists('company_workers');
        Schema::dropIfExists('company_money');
        Schema::dropIfExists('company_market');
        Schema::dropIfExists('company_license');
        Schema::dropIfExists('company');
        Schema::dropIfExists('industry');
    }
};
