<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (media) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('np_details', function (Blueprint $table) {
            $table->increments('npID');
            $table->string('npName', 25);
            $table->string('Avatar', 40)->default('noavatar.gif');
            $table->integer('CountryID')->default(0);
        });

        Schema::create('np_subs', function (Blueprint $table) {
            $table->bigIncrements('subID');
            $table->integer('npID')->default(0);
            $table->integer('citID')->default(0);
            $table->integer('timestamp')->default(0);
            $table->index('citID', 'np_subs_citid_ix');
            $table->index('npID', 'np_subs_npid_ix');
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->bigIncrements('aID');
            $table->bigInteger('npID')->default(0);
            $table->integer('respFor');
            $table->tinyInteger('RTL');
            $table->text('aTitle');
            $table->string('aPic', 75)->nullable()->default(null);
            $table->string('aSound', 55)->default('');
            $table->integer('aCountry');
            $table->text('aContent');
            $table->integer('aVotes')->default(0);
            $table->integer('aReaches')->default(0);
            $table->tinyInteger('mastprove')->default(0);
            $table->string('timestamp', 11)->default('');
            $table->char('Deleted', 1)->default('0');
            $table->integer('DeletedBy')->nullable()->default(null);
            $table->integer('isDraft')->default(0);
            $table->index(['npID', 'Deleted'], 'articles_npid_ix');
            $table->index('timestamp', 'articles_timestamp_ix');
            $table->index('npID', 'articles_npid_2_ix');
            $table->index('DeletedBy', 'articles_deletedby_ix');
        });

        Schema::create('article_comments', function (Blueprint $table) {
            $table->bigIncrements('cmID');
            $table->bigInteger('CitID')->default(0);
            $table->bigInteger('ArtID')->default(0);
            $table->text('cmBody');
            $table->integer('quote')->nullable()->default(null);
            $table->string('timestamp', 11)->default('');
            $table->integer('thumbs_up')->default(0);
            $table->integer('thumbs_down')->default(0);
            $table->smallInteger('Deleted')->default(0);
            $table->integer('DeletedBy')->nullable()->default(null);
            $table->index('quote', 'article_comments_quote_ix');
            $table->index('DeletedBy', 'article_comments_deletedby_ix');
        });

        Schema::create('article_comments_votes', function (Blueprint $table) {
            $table->integer('CommentID');
            $table->integer('CitizenID');
            $table->set('Vote', ['1', '-1']);
            $table->index(['CommentID', 'CitizenID'], 'article_comments_votes_commentid_ix');
        });

        Schema::create('article_votes', function (Blueprint $table) {
            $table->increments('voteID');
            $table->string('citID', 9)->default('');
            $table->string('artID', 9)->default('');
            $table->set('vote', ['1', '-1'])->default('1');
            $table->set('reason', ['Spam', 'Insult', 'Other'])->default('');
            $table->index('citID', 'article_votes_citid_ix');
            $table->index('artID', 'article_votes_artid_ix');
        });

        Schema::create('article_poll_q', function (Blueprint $table) {
            $table->increments('qID');
            $table->integer('aID');
            $table->text('q');
            $table->index('aID', 'article_poll_q_aid_ix');
        });

        Schema::create('article_poll_s', function (Blueprint $table) {
            $table->increments('sID');
            $table->integer('qID');
            $table->text('title');
            $table->index('qID', 'article_poll_s_qid_ix');
        });

        Schema::create('article_poll_a', function (Blueprint $table) {
            $table->increments('ansID');
            $table->integer('citID');
            $table->integer('qID');
            $table->integer('sID');
            $table->index(['citID', 'qID', 'sID'], 'article_poll_a_citid_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_poll_a');
        Schema::dropIfExists('article_poll_s');
        Schema::dropIfExists('article_poll_q');
        Schema::dropIfExists('article_votes');
        Schema::dropIfExists('article_comments_votes');
        Schema::dropIfExists('article_comments');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('np_subs');
        Schema::dropIfExists('np_details');
    }
};
