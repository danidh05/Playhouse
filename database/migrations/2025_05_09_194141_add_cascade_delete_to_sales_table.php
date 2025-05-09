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
        // First, clean up any orphaned records
        // Set play_session_id to NULL for sales where the referenced play_session no longer exists
        $orphanedSales = DB::table('sales as s')
            ->leftJoin('play_sessions as ps', 's.play_session_id', '=', 'ps.id')
            ->whereNotNull('s.play_session_id')
            ->whereNull('ps.id')
            ->select('s.id')
            ->get();

        foreach ($orphanedSales as $sale) {
            DB::table('sales')
                ->where('id', $sale->id)
                ->update(['play_session_id' => null]);
        }

        // Now add the foreign key with cascade delete
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('play_session_id')
                  ->references('id')
                  ->on('play_sessions')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop the foreign key
            try {
                $table->dropForeign(['play_session_id']);
            } catch (\Exception $e) {
                // Ignore error if the key doesn't exist
            }
        });
    }
};
