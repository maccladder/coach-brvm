<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add year column (default 0 pour permettre le backfill avant la contrainte)
        Schema::table('brvm_dividendes', function (Blueprint $table) {
            $table->smallInteger('year')->default(0)->after('ticker');
        });

        // 2. Backfill depuis date_paiement (SQLite: strftime, MySQL/MariaDB: YEAR())
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("UPDATE brvm_dividendes SET year = CAST(strftime('%Y', date_paiement) AS INTEGER) WHERE date_paiement IS NOT NULL AND date_paiement != ''");
        } else {
            DB::statement("UPDATE brvm_dividendes SET year = YEAR(date_paiement) WHERE date_paiement IS NOT NULL");
        }
        // Lignes sans date → 2025 par défaut
        DB::statement("UPDATE brvm_dividendes SET year = 2025 WHERE year = 0 OR year IS NULL");

        // 3. Remplacer unique(ticker) par unique(ticker, year)
        Schema::table('brvm_dividendes', function (Blueprint $table) {
            $table->dropUnique(['ticker']);
            $table->unique(['ticker', 'year']);
        });
    }

    public function down(): void
    {
        Schema::table('brvm_dividendes', function (Blueprint $table) {
            $table->dropUnique(['ticker', 'year']);
        });

        // On ne peut pas restaurer l'unicité ticker seul si plusieurs années existent
        Schema::table('brvm_dividendes', function (Blueprint $table) {
            $table->dropColumn('year');
        });
    }
};
