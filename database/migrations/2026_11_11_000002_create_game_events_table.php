<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Compteur anonyme de l'entonnoir des jeux à histoire (Le Pacte ; Akwaba garde
        // akwaba_events) : au plus un événement par jeu, session et type. session_hash = HMAC de
        // l'id de session, browser = catégorie déduite du User-Agent (non conservé) ; ni IP ni
        // donnée personnelle.
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->string('game_slug', 60);
            $table->string('type', 30);
            $table->string('session_hash', 64);
            $table->string('browser', 20)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['game_slug', 'type', 'session_hash']);
            $table->index(['game_slug', 'type', 'created_at']);
            $table->index(['game_slug', 'browser', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_events');
    }
};
