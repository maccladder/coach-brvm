<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une ligne par tentative de paiement Mobile Money (checkout personnalisé).
        // Le paiement "métier" (payments, cauris_achats, donations…) reste la source de vérité.
        Schema::create('mobile_money_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('item_key', 120);
            $table->string('reference')->unique();
            $table->unsignedInteger('amount');
            $table->string('operator', 20);
            $table->string('phone', 20);
            // pending | send_otp | success | failed | expired | replaced
            $table->string('status', 20)->default('pending');
            $table->string('paystack_status', 30)->nullable();
            $table->text('display_text')->nullable();
            $table->text('action_url')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('message')->nullable();
            // true seulement si Paystack confirme que rien n'a été débité
            $table->boolean('not_debited')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'item_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_money_checkouts');
    }
};
