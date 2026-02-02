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
        Schema::create('marketplace_assets', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')
        ->constrained('marketplace_products')
        ->cascadeOnDelete();

    $table->string('kind')->default('file'); // file|link
    $table->string('path')->nullable();      // storage path
    $table->text('url')->nullable();         // url (cloudflare/youtube/etc)
    $table->string('label')->nullable();     // "PDF", "Pack", "Module 1"
    $table->boolean('is_downloadable')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_assets');
    }
};
