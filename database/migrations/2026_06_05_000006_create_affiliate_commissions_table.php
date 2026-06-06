<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();

            // UNIQUE(payment_id) garantit l'idempotence — une seule commission par paiement
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();

            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();

            // Type de produit acheté
            $table->enum('product_type', ['pack_brvm', 'course', 'marketplace']);
            $table->unsignedBigInteger('product_id')->nullable(); // null pour pack_brvm

            // Montants (en FCFA)
            $table->unsignedBigInteger('base_amount');      // prix de référence avant remise
            $table->decimal('commission_rate', 5, 4);        // ex. 0.1000
            $table->unsignedBigInteger('commission_amount'); // base_amount × commission_rate
            $table->unsignedBigInteger('discount_amount');   // base_amount × discount_rate

            // Cycle de vie : pending → validated → paid | cancelled
            $table->enum('status', ['pending', 'validated', 'paid', 'cancelled'])->default('pending');

            $table->timestamp('validated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
            $table->index(['status', 'created_at']); // pour la commande de validation quotidienne
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
