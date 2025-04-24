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
        // Create the sale_items table first
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
        
        // Modify the sales table
        Schema::table('sales', function (Blueprint $table) {
            // Add new columns first
            $table->decimal('total_amount', 10, 2)->after('user_id')->default(0);
            $table->foreignId('child_id')->nullable()->after('payment_method');
            $table->foreignId('play_session_id')->nullable()->after('child_id');
        });
        
        // Drop foreign key constraint first, then drop columns
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'quantity', 'unit_price', 'total_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the old columns to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('product_id')->after('shift_id')->nullable();
            $table->integer('quantity')->after('product_id')->default(1);
            $table->decimal('unit_price', 10, 2)->after('quantity')->default(0);
            $table->decimal('total_price', 10, 2)->after('unit_price')->default(0);
            
            // Add foreign key constraint back
            $table->foreign('product_id')->references('id')->on('products');
            
            // Drop new columns
            $table->dropColumn(['total_amount', 'child_id', 'play_session_id']);
        });

        // Drop the sale_items table
        Schema::dropIfExists('sale_items');
    }
};
