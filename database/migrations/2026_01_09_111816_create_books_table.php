<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_free')->default(true);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('estimated_minutes')->default(5);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('books');
    }
};
