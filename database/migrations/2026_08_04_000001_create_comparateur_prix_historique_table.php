<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparateur_prix_historique', function (Blueprint $table) {
            $table->id();
            $table->string('id_produit');
            $table->string('site');
            $table->unsignedInteger('prix');
            $table->date('date');
            $table->timestamps();

            $table->unique(['id_produit', 'site', 'date']);
            $table->index(['id_produit', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparateur_prix_historique');
    }
};
