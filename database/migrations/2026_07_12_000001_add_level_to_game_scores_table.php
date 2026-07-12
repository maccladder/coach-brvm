<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            // Niveau atteint (jeux à progression par niveaux, ex. GRI-GRI).
            // Nullable : les jeux existants n'utilisent pas ce champ.
            $table->unsignedInteger('level')->nullable()->after('correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
