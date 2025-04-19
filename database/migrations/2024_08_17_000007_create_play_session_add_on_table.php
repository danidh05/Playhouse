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
        Schema::create('add_on_play_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('play_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('add_on_id')->constrained()->onDelete('cascade');
            $table->integer('qty');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('add_on_play_session');
    }
}; 