<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Historique des mouvements de game_credits. payment_reference unique : une référence
        // Paystack ne crédite qu'une seule fois, même si webhook et retour navigateur se croisent.
        Schema::create('game_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_slug', 60);
            $table->string('kind', 30);
            $table->integer('delta');               // +5 achat, -1 utilisation, ±N admin
            $table->string('reason', 20);           // purchase | use | admin
            $table->string('payment_reference', 120)->nullable()->unique();
            $table->unsignedInteger('amount')->nullable(); // XOF payés (achats)
            $table->string('note', 255)->nullable();       // motif admin
            $table->timestamps();

            $table->index(['user_id', 'game_slug', 'kind']);
            $table->index(['game_slug', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_credit_ledger');
    }
};
