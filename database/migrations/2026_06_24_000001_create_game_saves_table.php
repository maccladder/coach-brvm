<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('solde')->default(0);
            $table->unsignedInteger('tables_actives')->default(2);
            $table->unsignedInteger('nb_serveuses')->default(1);
            $table->unsignedBigInteger('cout_table')->default(2000);
            $table->unsignedBigInteger('cout_serveuse')->default(3000);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_saves');
    }
};
