<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('societes', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('code', 20)->unique();      // ex: SONATEL, SODECI
            $table->string('name');                    // Nom complet
            $table->string('sector')->nullable();      // Télécoms, Eau, Banque...
            $table->string('country', 50)->nullable(); // CI, SN, BF...

            // Infos utiles futures
            $table->boolean('is_listed')->default(true);
            $table->date('listing_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('societes');
    }
};
