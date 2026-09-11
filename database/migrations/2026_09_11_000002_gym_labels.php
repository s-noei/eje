<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The army page became the gym: rename its menu/title strings. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('trans_strings')->whereIn('phrase', ['army_bar_title', 'myplaces_army', 'title_army'])->update(['trans_en' => 'Gym']);
        DB::table('trans_strings')->where('phrase', 'army_title')->update(['trans_en' => 'Go to the gym']);
        DB::table('trans_strings')->where('phrase', 'army_back')->update(['trans_en' => 'Back to the gym']);
    }

    public function down(): void
    {
        DB::table('trans_strings')->whereIn('phrase', ['army_bar_title', 'myplaces_army', 'title_army'])->update(['trans_en' => 'Army']);
        DB::table('trans_strings')->where('phrase', 'army_title')->update(['trans_en' => 'Go to army page']);
        DB::table('trans_strings')->where('phrase', 'army_back')->update(['trans_en' => 'Back to army page']);
    }
};
