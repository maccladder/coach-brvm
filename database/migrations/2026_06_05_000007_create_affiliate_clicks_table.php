<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();

            $table->string('ip', 45)->nullable();        // IPv4 ou IPv6
            $table->text('user_agent')->nullable();

            // Pas de updated_at — un clic ne se modifie pas
            $table->timestamp('created_at')->useCurrent();

            $table->index(['affiliate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
