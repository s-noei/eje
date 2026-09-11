<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (politics) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party', function (Blueprint $table) {
            $table->bigIncrements('pID');
            $table->string('pName', 30);
            $table->bigInteger('PP')->default(1);
            $table->integer('coPP');
            $table->set('doElections', ['0', '1']);
            $table->tinyInteger('eOrient')->default(0);
            $table->tinyInteger('sOrient')->default(0);
            $table->string('pLogo', 45)->default('noavatar.gif');
            $table->float('pTala')->default(0);
            $table->bigInteger('cpProposed')->nullable()->default(null);
            $table->integer('CountryID')->default(0);
            $table->index('CountryID', 'party_countryid_ix');
        });

        Schema::create('party_members', function (Blueprint $table) {
            $table->bigIncrements('ID');
            $table->bigInteger('CitizenID')->default(0);
            $table->bigInteger('PartyID')->default(0);
            $table->integer('timestamp');
            $table->index('CitizenID', 'party_members_citizenid_ix');
            $table->index('PartyID', 'party_members_partyid_ix');
        });

        Schema::create('congressmen', function (Blueprint $table) {
            $table->increments('cgID');
            $table->integer('cgCitizenID')->default(0);
            $table->integer('cgPartyID')->default(0);
            $table->integer('cgCountryID')->default(0);
            $table->index('cgCitizenID', 'congressmen_cgcitizenid_ix');
            $table->index('cgCitizenID', 'congressmen_cgcitizenid_2_ix');
        });

        Schema::create('laws', function (Blueprint $table) {
            $table->increments('lawID');
            $table->integer('CountryID')->default(0);
            $table->integer('byID')->default(0);
            $table->string('Type', 15);
            $table->integer('pTime')->default(0);
            $table->integer('dTime')->default(0);
            $table->text('Params');
            $table->string('Debate', 50)->default('');
            $table->integer('Status')->default(0);
        });

        Schema::create('law_votes', function (Blueprint $table) {
            $table->increments('voteID');
            $table->integer('CitizenID')->default(0);
            $table->integer('LawID')->default(0);
            $table->set('Vote', ['YES', 'NO', 'NA'])->default('');
            $table->integer('timestamp')->default(0);
            $table->index('LawID', 'law_votes_lawid_ix');
        });

        Schema::create('elections_all', function (Blueprint $table) {
            $table->increments('eID');
            $table->set('eType', ['CG', 'CP', 'PP'])->default('');
            $table->integer('timestamp')->default(0);
            $table->integer('day')->default(1);
            $table->integer('processed')->default(0);
            $table->index('eType', 'elections_all_etype_ix');
        });

        Schema::create('elections_cg_candidates', function (Blueprint $table) {
            $table->increments('cID');
            $table->bigInteger('CitizenID')->default(0);
            $table->integer('RegionID')->nullable()->default(null);
            $table->integer('PartyID')->default(0);
            $table->float('Order')->default(0);
            $table->string('DocURL', 40)->nullable()->default(null);
            $table->index('RegionID', 'elections_cg_candidates_regionid_ix');
        });

        Schema::create('elections_cg_elections', function (Blueprint $table) {
            $table->increments('cID');
            $table->integer('ElectionID')->default(0);
            $table->integer('CandidateID');
            $table->bigInteger('PartyID')->default(0);
            $table->integer('CountryID')->default(0);
            $table->integer('RegionID');
            $table->string('DocURL', 40)->nullable()->default(null);
            $table->integer('State')->default(0);
            $table->index('ElectionID', 'elections_cg_elections_electionid_ix');
            $table->index('PartyID', 'elections_cg_elections_partyid_ix');
            $table->index('CountryID', 'elections_cg_elections_countryid_ix');
        });

        Schema::create('elections_cg_votes', function (Blueprint $table) {
            $table->bigIncrements('voteID');
            $table->integer('ElectionID')->default(0);
            $table->integer('VoterID')->default(0);
            $table->integer('CandidateID')->default(0);
            $table->index('CandidateID', 'elections_cg_votes_candidateid_ix');
        });

        Schema::create('elections_cp_elections', function (Blueprint $table) {
            $table->increments('cID');
            $table->integer('ElectionID')->default(0);
            $table->integer('CandidateID')->default(0);
            $table->bigInteger('PartyID')->default(0);
            $table->integer('CountryID')->default(0);
            $table->string('DocURL', 40)->nullable()->default(null);
        });

        Schema::create('elections_cp_votes', function (Blueprint $table) {
            $table->bigIncrements('voteID');
            $table->integer('ElectionID')->default(0);
            $table->integer('VoterID')->default(0);
            $table->integer('CandidateID')->default(0);
            $table->index('ElectionID', 'elections_cp_votes_electionid_ix');
            $table->index('CandidateID', 'elections_cp_votes_candidateid_ix');
        });

        Schema::create('elections_pp_candidates', function (Blueprint $table) {
            $table->increments('cID');
            $table->bigInteger('CitizenID')->default(0);
            $table->integer('PartyID')->default(0);
            $table->string('DocURL', 40)->nullable()->default(null);
            $table->index(['CitizenID', 'PartyID'], 'elections_pp_candidates_citizenid_ix');
        });

        Schema::create('elections_pp_elections', function (Blueprint $table) {
            $table->increments('cID');
            $table->integer('ElectionID')->default(0);
            $table->bigInteger('CandidateID')->default(0);
            $table->integer('PartyID')->default(0);
            $table->string('DocURL', 40)->nullable()->default(null);
            $table->index('PartyID', 'elections_pp_elections_partyid_ix');
        });

        Schema::create('elections_pp_votes', function (Blueprint $table) {
            $table->bigIncrements('voteID');
            $table->integer('ElectionID')->default(0);
            $table->integer('VoterID')->default(0);
            $table->integer('CandidateID')->default(0);
            $table->index('CandidateID', 'elections_pp_votes_candidateid_ix');
        });

        Schema::create('elections_votes', function (Blueprint $table) {
            $table->bigIncrements('voteID');
            $table->integer('ElectionID')->default(0);
            $table->integer('VoterID')->default(0);
            $table->integer('CandidateID')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections_votes');
        Schema::dropIfExists('elections_pp_votes');
        Schema::dropIfExists('elections_pp_elections');
        Schema::dropIfExists('elections_pp_candidates');
        Schema::dropIfExists('elections_cp_votes');
        Schema::dropIfExists('elections_cp_elections');
        Schema::dropIfExists('elections_cg_votes');
        Schema::dropIfExists('elections_cg_elections');
        Schema::dropIfExists('elections_cg_candidates');
        Schema::dropIfExists('elections_all');
        Schema::dropIfExists('law_votes');
        Schema::dropIfExists('laws');
        Schema::dropIfExists('congressmen');
        Schema::dropIfExists('party_members');
        Schema::dropIfExists('party');
    }
};
