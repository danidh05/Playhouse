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
        Schema::table('sale_items', function (Blueprint $table) {
            // Make product_id nullable since we could have either a product or an add-on
            $table->foreignId('product_id')->nullable()->change();
            
            // Add the add_on_id column
            $table->foreignId('add_on_id')->nullable()->after('product_id');
            
            // Add the foreign key constraint for add_on_id
            $table->foreign('add_on_id')->references('id')->on('add_ons');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['add_on_id']);
            
            // Drop the add_on_id column
            $table->dropColumn('add_on_id');
            
            // Make product_id required again
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
