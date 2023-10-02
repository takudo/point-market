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
        Schema::create('trade_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_user_id')->constrained(table: 'users');
            $table->integer('seller_point_result');
            $table->foreignId('buyer_user_id')->constrained(table: 'users');
            $table->integer('buyer_point_result');
            $table->foreignId('item_id')->constrained(table: 'items');
            $table->integer('item_point');
            $table->datetime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_histories');
    }
};
