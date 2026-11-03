<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Compteur anonyme des invités (play_start, lock_reached) : au plus un événement par
        // session et par type. session_hash = HMAC de l'id de session ; ni IP ni donnée personnelle.
        Schema::create('akwaba_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('session_hash', 64);
            $table->timestamp('created_at')->nullable();

            $table->unique(['type', 'session_hash']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akwaba_events');
    }
};
