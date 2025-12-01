<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('financial_statements', function (Blueprint $t) {
            $t->id();
            $t->string('issuer')->index();       // ex: "UNIWAX CI"
            $t->string('period');                // ex: "FY2024", "H1-2025"
            $t->string('statement_type');        // income|balance|cashflow
            $t->string('file_path');             // pdf/xlsx/csv
            $t->json('extracted_metrics')->nullable();
            $t->date('published_at')->nullable();
            $t->timestamps();
            $t->unique(['issuer','period','statement_type']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('financial_statements');
    }
};
