<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_coupons', function (Blueprint $table) {
            $table->id();
            $table->date('date_coupon');
            $table->enum('niveau', ['sur', 'equilibre', 'jackpot']);
            $table->json('selections');
            $table->decimal('cote_totale', 8, 2);
            $table->text('analyse')->nullable();
            $table->string('lien_betclic')->nullable();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->enum('resultat', ['en_attente', 'gagne', 'perdu'])->default('en_attente');
            $table->json('details_resultat')->nullable();
            $table->unsignedInteger('vues')->default(0);
            $table->timestamps();

            $table->unique(['date_coupon', 'niveau']);
            $table->index(['statut', 'date_coupon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_coupons');
    }
};
