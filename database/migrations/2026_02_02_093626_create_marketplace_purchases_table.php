<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketplace_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();

            $table->unsignedInteger('amount')->default(0); // FCFA
            $table->string('currency', 10)->default('XOF');

            // utile pour CinetPay / Wallet
            $table->string('provider')->nullable(); // cinetpay|wallet|manual
            $table->string('provider_ref')->nullable(); // transaction id etc

            $table->string('status')->default('paid'); // paid|pending|failed|refunded
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'product_id']); // 1 seul achat par produit (simple)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_purchases');
    }
};
