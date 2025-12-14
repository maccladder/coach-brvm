<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brvm_dividendes', function (Blueprint $table) {
            $table->id();

            $table->string('ticker', 10)->index();
            $table->string('societe', 255)->nullable();

            $table->decimal('dividende_net', 12, 2)->nullable();  // Montant net
            $table->date('date_paiement')->nullable();            // Date du dernier paiement
            $table->decimal('rendement_net', 8, 2)->nullable();   // %
            $table->decimal('per', 10, 2)->nullable();            // PER

            $table->date('boc_date_reference')->nullable();       // ex: 2025-12-02
            $table->string('source_boc', 255)->nullable();         // storage path (public)

            $table->timestamps();

            $table->unique(['ticker']); // 1 ligne par société (version simple)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brvm_dividendes');
    }
};
