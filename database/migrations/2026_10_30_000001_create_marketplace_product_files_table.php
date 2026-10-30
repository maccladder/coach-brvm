<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fichiers téléchargeables d'un produit (0..N). L'accès se vérifie au niveau du PRODUIT.
     */
    public function up(): void
    {
        Schema::create('marketplace_product_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('marketplace_products')
                ->cascadeOnDelete();
            // Ligne marketplace_assets d'origine (fichiers repris de l'ancien système à fichier unique)
            $table->foreignId('legacy_asset_id')
                ->nullable()
                ->constrained('marketplace_assets')
                ->nullOnDelete();
            $table->string('label');                          // "Windows — Installeur"
            $table->string('platform', 32)->nullable();       // windows|windows_portable|macos|linux|android|autre
            $table->string('original_name');
            $table->string('disk', 32)->default('local');     // public = ancien fichier pas encore déplacé
            $table->string('path');
            $table->unsignedBigInteger('size')->nullable();   // octets
            $table->string('mime')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_files');
    }
};
