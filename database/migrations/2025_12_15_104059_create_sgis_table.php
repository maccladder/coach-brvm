<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sgis', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('name');                 // ex: "BOA CAPITAL SECURITIES"
            $table->string('slug')->unique();       // ex: "boa-capital-securities"

            // Localisation
            $table->string('country')->index();     // ex: "Côte d'Ivoire"
            $table->string('city')->nullable()->index();
            $table->text('address')->nullable();    // adresse complète
            $table->string('po_box')->nullable();   // BP / 01 BP etc

            // Contacts
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('website')->nullable();

            // Source (officiel)
            $table->string('source_name')->default('BRVM');
            $table->string('source_url')->nullable();

            // Gestion interne
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sgis');
    }
};
