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
    Schema::create('virtual_wallet_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->enum('type', ['topup', 'buy', 'sell']);
        $table->string('ticker')->nullable();
        $table->string('name')->nullable();
        $table->decimal('price', 15, 2)->nullable();
        $table->integer('quantity')->nullable();

        $table->decimal('amount', 15, 2); // + ou -
        $table->json('meta')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_wallet_transactions');
    }
};
