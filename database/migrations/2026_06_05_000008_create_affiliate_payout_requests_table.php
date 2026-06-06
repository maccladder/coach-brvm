<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_payout_requests', function (Blueprint $table) {
            $table->id();

            // FK vers affiliates (jamais vers users directement — étanchéité caisse apporteur)
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();

            $table->unsignedBigInteger('amount'); // en FCFA

            // pending | approved | paid | rejected | canceled
            $table->string('status', 20)->default('pending');

            // wave | orange_money | mtn | moov
            $table->string('payout_method', 30)->nullable();
            $table->string('payout_account', 80)->nullable(); // numéro de téléphone
            $table->string('reference', 120)->nullable();     // réf transaction / reçu admin
            $table->string('admin_note', 500)->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payout_requests');
    }
};
