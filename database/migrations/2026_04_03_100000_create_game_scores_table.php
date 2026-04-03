<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('distance')->default(0);
            $table->unsignedInteger('coins')->default(0);
            $table->timestamp('created_at')->useCurrent();
            // Pas de updated_at : chaque partie est un nouvel enregistrement
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
