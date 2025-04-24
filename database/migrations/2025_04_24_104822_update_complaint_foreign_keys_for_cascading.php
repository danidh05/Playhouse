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
        Schema::table('complaints', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['child_id']);
            
            // Add the new foreign key with onDelete('set null')
            $table->foreign('child_id')
                  ->references('id')
                  ->on('children')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['child_id']);
            
            // Restore the original foreign key without onDelete behavior
            $table->foreign('child_id')
                  ->references('id')
                  ->on('children');
        });
    }
};
