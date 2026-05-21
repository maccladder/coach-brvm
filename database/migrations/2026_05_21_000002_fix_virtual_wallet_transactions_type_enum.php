<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Enum final : 7 valeurs (3 d'origine + 3 déjà utilisées en code + 1 nouveau)
    // SQLite stocke les enums en TEXT — cette migration est un no-op sur SQLite
    // mais documente le contrat et prépare une future migration MySQL/Postgres.

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE virtual_wallet_transactions
            MODIFY COLUMN type ENUM(
                'topup',
                'buy',
                'sell',
                'topup_admin',
                'transfer_in',
                'transfer_out',
                'bonus'
            ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE virtual_wallet_transactions
            MODIFY COLUMN type ENUM(
                'topup',
                'buy',
                'sell'
            ) NOT NULL");
    }
};
