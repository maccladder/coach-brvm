<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            // Contenu HTML complet du jeu (tout-en-un, servi via iframe srcdoc)
            $table->longText('game_html')->nullable()->after('pages_count');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn('game_html');
        });
    }
};
