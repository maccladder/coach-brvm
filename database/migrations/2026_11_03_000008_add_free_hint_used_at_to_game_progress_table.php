<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Indice gratuit d'un jeu (Akwaba : un seul par joueur, pour toujours). Date de première
        // utilisation ; null = encore disponible. Posée une seule fois (UPDATE … WHERE IS NULL).
        Schema::table('game_progress', function (Blueprint $table) {
            $table->timestamp('free_hint_used_at')->nullable()->after('data');
        });
    }

    public function down(): void
    {
        Schema::table('game_progress', function (Blueprint $table) {
            $table->dropColumn('free_hint_used_at');
        });
    }
};
