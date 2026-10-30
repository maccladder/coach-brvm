<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Validation admin des fichiers exécutables ajoutés par un vendeur sur un produit publié.
     * pending/rejected : invisibles pour les acheteurs. replaces_file_id : remplacement d'un
     * fichier existant, appliqué seulement à l'approbation (l'ancien reste servi d'ici là).
     */
    public function up(): void
    {
        Schema::table('marketplace_product_files', function (Blueprint $table) {
            $table->string('status', 16)->default('approved')->after('download_count'); // pending|approved|rejected
            $table->foreignId('replaces_file_id')
                ->nullable()
                ->after('status')
                ->constrained('marketplace_product_files')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('replaces_file_id');
            $table->string('review_note', 500)->nullable()->after('reviewed_at');

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_product_files', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'status']);
            $table->dropConstrainedForeignId('replaces_file_id');
            $table->dropColumn(['status', 'reviewed_at', 'review_note']);
        });
    }
};
