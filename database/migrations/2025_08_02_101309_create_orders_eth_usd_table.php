<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders_eth_usd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['buy', 'sell'])->index();
            $table->decimal('amount', 18, 8);
            $table->decimal('price', 18, 8);
            $table->decimal('leverage', 5, 2)->default(1);
            $table->tinyInteger('status')->default(0)->index(); // 0=open, 1=partially, 2=filled, 3=cancelled
            $table->decimal('filled_amount', 18, 4)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'price', 'status']);
            $table->index(['status', 'opened_at', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_eth_usd');
    }
};
