<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stagiaire_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action');           // login, logout, approve_product, view_product, ...
            $table->string('label')->nullable(); // description lisible ex: "A approuvé le produit #12 — Abidjan Run"
            $table->string('route')->nullable(); // nom de la route Laravel
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stagiaire_logs');
    }
};
