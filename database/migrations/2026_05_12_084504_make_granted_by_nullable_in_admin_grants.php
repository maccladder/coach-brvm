<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Étape 1 : supprimer la contrainte FK existante
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->dropForeign(['granted_by']);
        });

        // Étape 2 : passer la colonne en nullable
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->unsignedBigInteger('granted_by')->nullable()->change();
        });

        // Étape 3 : recréer la FK avec nullOnDelete
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->foreign('granted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Étape 1 : supprimer la FK nullable
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->dropForeign(['granted_by']);
        });

        // Étape 2 : repasser en non-nullable
        // ⚠️ Plantera si des lignes ont granted_by = null au moment du rollback
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->unsignedBigInteger('granted_by')->nullable(false)->change();
        });

        // Étape 3 : recréer la FK d'origine (sans nullOnDelete)
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->foreign('granted_by')
                ->references('id')
                ->on('users');
        });
    }
};
