<?php

// database/migrations/xxxx_xx_xx_create_boc_stocks_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('boc_stocks', function (Blueprint $table) {
            $table->id();

            // IMPORTANT: adapte le nom si ta table BOC s'appelle autrement
            $table->unsignedBigInteger('client_boc_id'); // ou daily_boc_id etc.

            $table->date('boc_date');
            $table->string('ticker', 20);
            $table->string('name', 120)->nullable();

            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('change', 8, 3)->nullable(); // ex: -1.450 (%)

            $table->timestamps();

            $table->unique(['client_boc_id', 'ticker']);
            $table->index(['ticker', 'boc_date']);

            $table->foreign('client_boc_id')
                ->references('id')
                ->on('client_bocs') // adapte si ta table diffère
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boc_stocks');
    }
};
