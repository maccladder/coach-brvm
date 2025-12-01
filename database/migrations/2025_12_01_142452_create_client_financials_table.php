<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('client_financials', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();          // "Etats financiers 2024 - SONATEL"
    $table->string('company')->nullable();        // Nom de l'entreprise
    $table->string('period')->nullable();         // Ex: "Exercice 2024"
    $table->date('financial_date')->nullable();   // Optionnel

    $table->string('original_filename')->nullable();
    $table->string('stored_path')->nullable();    // chemin du fichier uploadé

    $table->longText('interpreted_markdown')->nullable(); // Analyse IA
    $table->string('avatar_video_url')->nullable();       // URL vidéo D-ID
    $table->string('audio_path')->nullable();             // mp3 TTS

    // Paiement
    $table->integer('amount')->default(0);
    $table->string('status')->default('pending');         // pending / paid / failed
    $table->uuid('transaction_id')->unique();             // pour CinetPay

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_financials');
    }
};
