<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();

            // si ta table sociétés s'appelle "societes" et la PK "id"
            $table->foreignId('societe_id')->constrained('societes')->cascadeOnDelete();

            $table->unsignedSmallInteger('year'); // 2025, 2026...
            $table->string('period', 2);          // Q1, S1, Q3, FY

            $table->string('status', 20)->default('pending'); // pending|published|not_published
            $table->string('file_path')->nullable();          // storage path
            $table->date('published_at')->nullable();         // date de publication (si connue)

            $table->unsignedBigInteger('uploaded_by')->nullable(); // admin id si tu veux
            $table->timestamps();

            // Empêche doublon: 1 société, 1 année, 1 période = 1 ligne
            $table->unique(['societe_id', 'year', 'period'], 'fr_unique_societe_year_period');
            $table->index(['year', 'period']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
