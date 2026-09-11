<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy eJahan schema (social) — generated from the original MySQL dump.
 * Engine: InnoDB, charset utf8mb4 (legacy tables were MyISAM/latin1 with utf8 text columns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm', function (Blueprint $table) {
            $table->increments('pmID');
            $table->integer('fromID')->nullable()->default(null);
            $table->integer('toID')->nullable()->default(null);
            $table->string('Subject', 40)->nullable()->default(null);
            $table->text('Body')->nullable();
            $table->string('timestamp', 11)->nullable()->default(null);
            $table->char('isRead', 1)->default('0');
            $table->char('rem_inbox', 1)->default('0');
            $table->char('rem_sent', 1)->default('0');
            $table->index('pmID', 'pm_pmid_ix');
            $table->index(['toID', 'isRead'], 'pm_toid_ix');
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->increments('NoteID');
            $table->bigInteger('toID')->default(0);
            $table->string('Type', 10)->nullable()->default(null);
            $table->text('Body');
            $table->integer('timestamp');
            $table->tinyInteger('isRead')->default(0);
            $table->index(['toID', 'isRead'], 'notes_toid_ix');
        });

        Schema::create('friendship', function (Blueprint $table) {
            $table->bigIncrements('fID');
            $table->bigInteger('Part1')->default(0);
            $table->bigInteger('Part2')->default(0);
            $table->bigInteger('AddedBy')->default(0);
            $table->tinyInteger('Accepted')->default(0);
            $table->index(['Part1', 'Part2'], 'friendship_part1_ix');
            $table->index('Part1', 'friendship_part1_2_ix');
            $table->index('Part2', 'friendship_part2_ix');
            $table->index('AddedBy', 'friendship_addedby_ix');
            $table->index('Accepted', 'friendship_accepted_ix');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->increments('chatID');
            $table->integer('citID')->default(0);
            $table->string('name', 30);
            $table->string('Avatar', 45);
            $table->text('message');
            $table->tinyInteger('priority')->default(1);
            $table->integer('timestamp')->default(0);
            $table->index('priority', 'chat_messages_priority_ix');
        });

        Schema::create('cometchat', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from')->unsigned();
            $table->integer('to')->unsigned();
            $table->text('message');
            $table->integer('sent')->unsigned()->default(0);
            $table->tinyInteger('read')->unsigned()->default(0);
            $table->tinyInteger('direction')->unsigned()->default(0);
            $table->index('to', 'cometchat_to_ix');
            $table->index('from', 'cometchat_from_ix');
            $table->index('direction', 'cometchat_direction_ix');
            $table->index('read', 'cometchat_read_ix');
            $table->index('sent', 'cometchat_sent_ix');
        });

        Schema::create('cometchat_announcements', function (Blueprint $table) {
            $table->increments('id');
            $table->text('announcement');
            $table->integer('time')->unsigned();
            $table->integer('to');
            $table->index('to', 'cometchat_announcements_to_ix');
            $table->index('time', 'cometchat_announcements_time_ix');
        });

        Schema::create('cometchat_chatroommessages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userid')->unsigned();
            $table->integer('chatroomid')->unsigned();
            $table->text('message');
            $table->integer('sent')->unsigned();
            $table->index('userid', 'cometchat_chatroommessages_userid_ix');
            $table->index('chatroomid', 'cometchat_chatroommessages_chatroomid_ix');
            $table->index('sent', 'cometchat_chatroommessages_sent_ix');
        });

        Schema::create('cometchat_chatrooms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->integer('lastactivity')->unsigned();
            $table->integer('createdby')->unsigned();
            $table->string('password', 255);
            $table->tinyInteger('type')->unsigned();
            $table->index('lastactivity', 'cometchat_chatrooms_lastactivity_ix');
            $table->index('createdby', 'cometchat_chatrooms_createdby_ix');
            $table->index('type', 'cometchat_chatrooms_type_ix');
        });

        Schema::create('cometchat_chatrooms_users', function (Blueprint $table) {
            $table->integer('userid')->unsigned()->primary();
            $table->integer('chatroomid')->unsigned();
            $table->integer('lastactivity')->unsigned();
            $table->index('chatroomid', 'cometchat_chatrooms_users_chatroomid_ix');
            $table->index('lastactivity', 'cometchat_chatrooms_users_lastactivity_ix');
        });

        Schema::create('cometchat_messages_old', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from')->unsigned();
            $table->integer('to')->unsigned();
            $table->text('message');
            $table->integer('sent')->unsigned()->default(0);
            $table->tinyInteger('read')->unsigned()->default(0);
            $table->tinyInteger('direction')->unsigned()->default(0);
            $table->index('to', 'cometchat_messages_old_to_ix');
            $table->index('from', 'cometchat_messages_old_from_ix');
            $table->index('direction', 'cometchat_messages_old_direction_ix');
            $table->index('read', 'cometchat_messages_old_read_ix');
            $table->index('sent', 'cometchat_messages_old_sent_ix');
        });

        Schema::create('cometchat_status', function (Blueprint $table) {
            $table->integer('userid')->unsigned()->primary();
            $table->text('message')->nullable();
            $table->enum('status', ['available', 'away', 'busy', 'invisible', 'offline'])->nullable()->default(null);
            $table->integer('typingto')->unsigned()->nullable()->default(null);
            $table->integer('typingtime')->unsigned()->nullable()->default(null);
            $table->index('typingto', 'cometchat_status_typingto_ix');
            $table->index('typingtime', 'cometchat_status_typingtime_ix');
        });

        Schema::create('cometchat_videochatsessions', function (Blueprint $table) {
            $table->string('username', 255)->primary();
            $table->string('identity', 255);
            $table->integer('timestamp')->unsigned()->nullable()->default(0);
            $table->index('username', 'cometchat_videochatsessions_username_ix');
            $table->index('identity', 'cometchat_videochatsessions_identity_ix');
            $table->index('timestamp', 'cometchat_videochatsessions_timestamp_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cometchat_videochatsessions');
        Schema::dropIfExists('cometchat_status');
        Schema::dropIfExists('cometchat_messages_old');
        Schema::dropIfExists('cometchat_chatrooms_users');
        Schema::dropIfExists('cometchat_chatrooms');
        Schema::dropIfExists('cometchat_chatroommessages');
        Schema::dropIfExists('cometchat_announcements');
        Schema::dropIfExists('cometchat');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('friendship');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('pm');
    }
};
