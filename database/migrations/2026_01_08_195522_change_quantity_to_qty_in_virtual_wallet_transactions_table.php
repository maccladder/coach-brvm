<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) ajouter la colonne qty si elle n'existe pas
        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_wallet_transactions', 'qty')) {
                $table->integer('qty')->nullable()->after('price');
            }
        });

        // 2) copier quantity -> qty
        if (Schema::hasColumn('virtual_wallet_transactions', 'quantity')) {
            DB::statement("UPDATE virtual_wallet_transactions SET qty = quantity WHERE qty IS NULL");
        }

        // 3) supprimer l'ancienne colonne quantity
        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('virtual_wallet_transactions', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }

    public function down(): void
    {
        // rollback (optionnel)
        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_wallet_transactions', 'quantity')) {
                $table->integer('quantity')->nullable()->after('price');
            }
        });

        if (Schema::hasColumn('virtual_wallet_transactions', 'qty')) {
            DB::statement("UPDATE virtual_wallet_transactions SET quantity = qty WHERE quantity IS NULL");
        }

        Schema::table('virtual_wallet_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('virtual_wallet_transactions', 'qty')) {
                $table->dropColumn('qty');
            }
        });
    }
};
