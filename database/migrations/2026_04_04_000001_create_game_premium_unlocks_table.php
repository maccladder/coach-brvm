<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_premium_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->string('feature')->default('premium_chars'); // extensible
            $table->string('paystack_ref')->unique();
            $table->unsignedInteger('amount')->default(0); // en XOF
            $table->timestamp('unlocked_at');

            $table->unique(['user_id', 'product_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_premium_unlocks');
    }
};
