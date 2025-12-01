<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_bocs', function (Blueprint $table) {
            // noms alignés avec le contrôleur + modèle
            $table->string('original_filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->longText('interpreted_markdown')->nullable();
            // $table->string('avatar_video_url')->nullable();
            $table->string('audio_path')->nullable();
        });
    }

    public function down(): void
    {
        // Sur sqlite, le dropColumn peut être limité,
        // mais on met quand même le code standard.
        Schema::table('client_bocs', function (Blueprint $table) {
            $table->dropColumn([
                'original_filename',
                'stored_path',
                'interpreted_markdown',
                'avatar_video_url',
                'audio_path',
            ]);
        });
    }
};
