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
        Schema::create('wallet_connects', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 8, 2);
            $table->decimal('status');
            $table->decimal('id_verify');
            $table->decimal('pin_verify');
            $table->decimal('time_activated');
            $table->integer('code_unique')->unique();
            $table->unsignedBigInteger('payment_vendor_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_connecteds');
    }
};
