<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('sell_order_id')->constrained('orders')->onDelete('cascade');
            $table->string('symbol');
            $table->decimal('price', 20, 8);
            $table->decimal('amount', 20, 8);
            $table->decimal('usd_volume', 20, 8);
            $table->decimal('commission', 20, 8);
            $table->timestamps();

            $table->index('symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
