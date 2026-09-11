<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (forum) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_cats', function (Blueprint $table) {
            $table->increments('cat_id');
            $table->string('cat_name', 30)->default('');
            $table->integer('cat_order')->default(0);
        });

        Schema::create('forum_boards', function (Blueprint $table) {
            $table->increments('board_id');
            $table->string('board_name', 30)->default('');
            $table->text('board_desc')->nullable();
            $table->tinyInteger('board_view_rights')->default(1);
            $table->tinyInteger('board_post_rights')->default(1);
            $table->tinyInteger('board_edit_rights')->default(1);
            $table->integer('board_ticketing_style')->default(0);
            $table->set('board_post_count', ['0', '1'])->default('1');
            $table->integer('board_last_post_time')->default(0);
            $table->set('board_special_view', ['', 'own', 'cp', 'cg'])->default('');
            $table->integer('cat_id')->default(0);
            $table->integer('board_order')->default(0);
            $table->index('board_ticketing_style', 'forum_boards_board_ticketing_style_ix');
            $table->index('board_special_view', 'forum_boards_board_special_view_ix');
            $table->index('board_last_post_time', 'forum_boards_board_last_post_time_ix');
            $table->index('cat_id', 'forum_boards_cat_id_ix');
        });

        Schema::create('forum_board_views', function (Blueprint $table) {
            $table->integer('citizen_id');
            $table->integer('board_id');
            $table->integer('view_time');
            $table->primary(['citizen_id', 'board_id']);
        });

        Schema::create('forum_topics', function (Blueprint $table) {
            $table->increments('topic_id');
            $table->string('topic_name', 30)->default('');
            $table->text('topic_desc')->nullable();
            $table->integer('board_id');
            $table->integer('topic_starter');
            $table->integer('topic_create_time')->default(0);
            $table->integer('topic_last_post_time')->default(0);
            $table->integer('topic_special_var');
            $table->integer('topic_locked')->default(0);
            $table->integer('topic_sticky')->default(0);
            $table->integer('topic_removed')->default(0);
            $table->mediumInteger('topic_views')->unsigned()->default(0);
        });

        Schema::create('forum_topic_views', function (Blueprint $table) {
            $table->integer('citizen_id');
            $table->integer('topic_id');
            $table->integer('view_time');
            $table->primary(['citizen_id', 'topic_id']);
        });

        Schema::create('forum_posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->integer('post_poster')->default(0);
            $table->string('post_title', 50);
            $table->text('post_body');
            $table->integer('post_time')->default(0);
            $table->set('post_removed', ['0', '1'])->default('0');
            $table->integer('post_edit_time');
            $table->integer('post_edit_by');
            $table->integer('topic_id')->default(0);
            $table->index('topic_id', 'forum_posts_topic_id_ix');
            $table->index('post_removed', 'forum_posts_post_removed_ix');
        });

        Schema::create('forum_posts_history', function (Blueprint $table) {
            $table->increments('ID');
            $table->integer('post_id')->default(0);
            $table->integer('edit_by')->default(0);
            $table->string('edit_reason', 30)->default('');
            $table->integer('edit_time')->default(0);
            $table->text('post_before');
            $table->text('post_after');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts_history');
        Schema::dropIfExists('forum_posts');
        Schema::dropIfExists('forum_topic_views');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forum_board_views');
        Schema::dropIfExists('forum_boards');
        Schema::dropIfExists('forum_cats');
    }
};
