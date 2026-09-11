<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Legacy travel.php references citizens.travel_due, which the dump schema lacks. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('citizens', 'travel_due')) {
            Schema::table('citizens', function (Blueprint $table) {
                $table->integer('travel_due')->default(0)->after('occDue');
            });
        }
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table) {
            $table->dropColumn('travel_due');
        });
    }
};
