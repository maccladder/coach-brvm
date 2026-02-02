<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
    $table->id();

    $table->foreignId('category_id')
        ->nullable()
        ->constrained('marketplace_categories')
        ->nullOnDelete();

    $table->string('title');
    $table->string('slug')->unique();
    $table->string('type')->default('book'); // video|book|software
    $table->text('description')->nullable();

    $table->unsignedInteger('price')->default(0); // FCFA
    $table->string('cover_image_path')->nullable();

    $table->string('status')->default('draft'); // draft|published
    $table->boolean('is_featured')->default(false);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
