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
        Schema::create('client_bocs', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->date('boc_date')->nullable();
            $table->string('file_path');                // fichier uploadé
            $table->longText('summary_markdown')->nullable(); // texte IA interprété
            $table->string('audio_path')->nullable();        // MP3 généré via AiVoiceService
            $table->string('avatar_video_url')->nullable();  // URL vidéo avatar D-ID
            $table->string('status')->default('pending');    // pending / ready / error
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_bocs');
    }
};
