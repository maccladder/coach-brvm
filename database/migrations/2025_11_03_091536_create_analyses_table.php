<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('analyses', function (Blueprint $t) {
            $t->id();
            $t->date('as_of_date')->index();     // date d’analyse
            $t->string('title');
            $t->text('notes')->nullable();
            $t->string('file_path')->nullable(); // chemin du fichier uploadé
            $t->json('tags')->nullable();        // ex: ["BRVM","SOGB"]
            $t->timestamps();
            $t->unique(['as_of_date','title']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('analyses');
    }
};
