<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cauris_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('pack');
            $table->unsignedInteger('prix');
            $table->unsignedBigInteger('cauris');
            $table->string('status')->default('PENDING');
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cauris_achats');
    }
};
