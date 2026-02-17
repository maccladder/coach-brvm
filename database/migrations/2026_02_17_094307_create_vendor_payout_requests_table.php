<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_payout_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedBigInteger('amount'); // en FCFA
            $table->string('status', 20)->default('pending'); // pending|approved|paid|rejected|canceled

            $table->string('payout_method', 30)->nullable();  // wave|orange|mtn|moov|bank
            $table->string('payout_account', 80)->nullable(); // numéro / RIB etc.
            $table->string('reference', 120)->nullable();     // ref transaction
            $table->string('admin_note', 500)->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payout_requests');
    }
};
