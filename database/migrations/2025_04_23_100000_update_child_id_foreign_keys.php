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
        // Update play_sessions table
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropForeign(['child_id']); // remove existing constraint
            $table->foreign('child_id')
                  ->references('id')->on('children')
                  ->onDelete('cascade'); // re-add with cascade
        });

        // Update complaints table
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['child_id']); // remove existing constraint
            $table->foreign('child_id')
                  ->references('id')->on('children')
                  ->onDelete('cascade'); // re-add with cascade
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert play_sessions table
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropForeign(['child_id']);
            $table->foreign('child_id')
                  ->references('id')->on('children'); // revert to default (restrict)
        });

        // Revert complaints table
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeign(['child_id']);
            $table->foreign('child_id')
                  ->references('id')->on('children'); // revert to default (restrict)
        });
    }
}; 