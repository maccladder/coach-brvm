<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Montant encaissé hors Paystack (ex. paiement WhatsApp) pour une attribution manuelle.
        // Facultatif : null pour les attributions existantes et les cadeaux.
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->unsignedInteger('amount_received')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('admin_grants', function (Blueprint $table) {
            $table->dropColumn('amount_received');
        });
    }
};
