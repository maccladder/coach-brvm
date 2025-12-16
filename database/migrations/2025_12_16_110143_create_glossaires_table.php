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
        Schema::create('glossaires', function (Blueprint $table) {
    $table->id();
    $table->string('lettre', 1);
    $table->string('terme');
    $table->text('definition');
    $table->timestamps();

    $table->index('lettre');
    $table->index('terme');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('glossaires');
    }
};
