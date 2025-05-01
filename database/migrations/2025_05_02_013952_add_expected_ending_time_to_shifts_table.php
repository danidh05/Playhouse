<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // First check if the column doesn't already exist to avoid errors
            if (!Schema::hasColumn('shifts', 'expected_ending_time')) {
                $table->dateTime('expected_ending_time')->nullable()->after('ending_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // First check if the column exists before trying to drop it
            if (Schema::hasColumn('shifts', 'expected_ending_time')) {
                $table->dropColumn('expected_ending_time');
            }
        });
    }
};