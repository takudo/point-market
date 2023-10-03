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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->enum('status', ['not_on_sale', 'on_sale', 'sold'])->default('not_on_sale'); // 未出品, 出品中, 売約済
            $table->integer('selling_price_point');
            $table->foreignId('seller_user_id')->constrained(table: 'users'); // 売り主
            $table->foreignId('buyer_user_id')->nullable()->constrained(table: 'users'); // 買い手。売約時に値が保存される
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
