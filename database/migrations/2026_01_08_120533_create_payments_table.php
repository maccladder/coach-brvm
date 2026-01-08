<?php

// database/migrations/xxxx_xx_xx_create_payments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // transaction id CinetPay (unique)
            $table->string('transaction_id')->unique();

            // ce que le user paie réellement (1000, 2000...)
            $table->unsignedInteger('amount_paid');

            // ce qu’il reçoit en virtuel (100000, 200000...)
            $table->unsignedInteger('amount_virtual');

            // wallet_topup
            $table->string('purpose')->default('wallet_topup');

            // statut cinetpay: PENDING / ACCEPTED / REFUSED
            $table->string('status')->default('PENDING');

            // éviter double crédit
            $table->timestamp('credited_at')->nullable();

            // infos utiles
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
