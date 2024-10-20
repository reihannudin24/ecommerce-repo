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
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->unsignedBigInteger('status');
            $table->decimal('price', 8, 2);
            $table->decimal('total', 8, 2);
            $table->decimal('after_disc', 8, 2);
            $table->decimal('fee_shipping',8, 2 );
            $table->decimal('distance_shipping',8, 2 );
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
