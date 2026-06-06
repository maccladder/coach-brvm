<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Code apporteur capturé au moment de l'initiation du paiement
            $table->string('affiliate_code', 20)->nullable()->index()->after('meta');
            // Prix de référence avant remise (pour calcul commission = base_amount × rate)
            $table->unsignedBigInteger('affiliate_base_amount')->nullable()->after('affiliate_code');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['affiliate_code']);
            $table->dropColumn(['affiliate_code', 'affiliate_base_amount']);
        });
    }
};
