<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            // Nombre de bonnes réponses (jeux de quiz type "Questions pour un vrai môgô").
            // Nullable : les jeux existants (Abidjan Run) n'utilisent pas ce champ.
            $table->unsignedInteger('correct_answers')->nullable()->after('coins');
        });
    }

    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table) {
            $table->dropColumn('correct_answers');
        });
    }
};
