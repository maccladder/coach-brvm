<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('daily_summaries', function (Blueprint $t) {
            $t->id();
            $t->date('for_date')->unique();
            $t->text('summary_markdown');    // résumé en markdown
            $t->json('signals')->nullable(); // ex: {"UNI":"watch"}
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('daily_summaries');
    }
};
