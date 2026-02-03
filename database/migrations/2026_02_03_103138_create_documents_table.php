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
        Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->enum('type', [
        'market_study',
        'business_plan',
        'funding_dossier'
    ]);
    $table->unsignedBigInteger('sector_id')->nullable();
    $table->string('country')->nullable();
    $table->integer('price');
    $table->text('description')->nullable();
    $table->string('file_path');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
