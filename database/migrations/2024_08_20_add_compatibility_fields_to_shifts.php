<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('cashier_id');
            $table->timestamp('starting_time')->nullable()->after('notes');
            $table->timestamp('ending_time')->nullable()->after('starting_time');
        });

        // Copy data from cashier_id to user_id
        DB::statement('UPDATE shifts SET user_id = cashier_id WHERE user_id IS NULL');
        
        // Copy data from opened_at to starting_time and closed_at to ending_time
        DB::statement('UPDATE shifts SET starting_time = opened_at WHERE starting_time IS NULL');
        DB::statement('UPDATE shifts SET ending_time = closed_at WHERE ending_time IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'starting_time', 'ending_time']);
        });
    }
}; 