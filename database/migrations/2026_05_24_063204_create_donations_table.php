<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('donor_name');
            $table->string('donor_email');
            $table->unsignedInteger('amount');
            $table->string('currency', 10)->default('XOF');
            $table->string('status', 20)->default('PENDING'); // PENDING, ACCEPTED, FAILED
            $table->json('meta')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
