<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('book_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_no');
            $table->string('title')->nullable();
            $table->longText('content');
            $table->timestamps();

            $table->unique(['book_id', 'page_no']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('book_pages');
    }
};

