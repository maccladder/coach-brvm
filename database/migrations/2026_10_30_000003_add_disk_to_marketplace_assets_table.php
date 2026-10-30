<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Disque du fichier de l'asset : 'public' pour l'existant, 'local' (privé) pour les
     * fichiers payants uploadés ou déplacés par marketplace:move-files-private.
     */
    public function up(): void
    {
        Schema::table('marketplace_assets', function (Blueprint $table) {
            $table->string('disk', 32)->default('public')->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_assets', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
