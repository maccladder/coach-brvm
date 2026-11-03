<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Solde de crédits consommables par joueur et par jeu (ex. indices d'Akwaba).
        // Chaque mouvement est tracé dans game_credit_ledger.
        Schema::create('game_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_slug', 60);
            $table->string('kind', 30); // hints
            $table->unsignedInteger('balance')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'game_slug', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_credits');
    }
};
