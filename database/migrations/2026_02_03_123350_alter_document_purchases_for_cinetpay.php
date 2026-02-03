<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_purchases', function (Blueprint $table) {

            // 1) currency / provider / provider_ref / status / paid_at
            $table->string('currency', 10)->default('XOF')->after('amount');

            // tu avais payment_method : on peut le garder, mais on normalise aussi
            $table->string('provider', 50)->default('cinetpay')->after('currency');

            $table->string('provider_ref')->nullable()->after('provider');
            $table->index('provider_ref');

            $table->string('status', 30)->default('pending')->after('provider_ref');
            $table->index('status');

            $table->timestamp('paid_at')->nullable()->after('status');
        });

        // 2) IMPORTANT : supprimer l'unique actuel (user_id, document_id)
        // car sinon un user ne peut jamais refaire un achat si ça échoue/pending
        Schema::table('document_purchases', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'document_id']);
        });

        // 3) Option : remettre une contrainte plus saine
        // -> On empêche uniquement d'avoir 2 paiements "paid" du même document par le même user.
        // Laravel/MySQL ne supportent pas tous un index unique conditionnel.
        // Donc on met un unique simple sur provider_ref (transaction id).
        Schema::table('document_purchases', function (Blueprint $table) {
            $table->unique('provider_ref');
        });
    }

    public function down(): void
    {
        // 1) retirer le unique provider_ref
        Schema::table('document_purchases', function (Blueprint $table) {
            $table->dropUnique(['provider_ref']);
        });

        // 2) retirer les nouvelles colonnes + indexes
        Schema::table('document_purchases', function (Blueprint $table) {
            $table->dropIndex(['provider_ref']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'currency',
                'provider',
                'provider_ref',
                'status',
                'paid_at',
            ]);
        });

        // 3) remettre l'unique initial (comme avant)
        Schema::table('document_purchases', function (Blueprint $table) {
            $table->unique(['user_id', 'document_id']);
        });
    }
};
