<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Introduction de Bridge Bank Group Côte d'Ivoire (BBGC) au compartiment
 * principal de la BRVM le 24/09/2026 (48e société cotée).
 *
 * Idempotente : updateOrInsert sur le code, ne touche à aucune autre ligne.
 */
return new class extends Migration {
    public function up(): void
    {
        $existing = DB::table('societes')->where('code', 'BBGC')->exists();

        DB::table('societes')->updateOrInsert(
            ['code' => 'BBGC'],
            [
                'name'         => "BRIDGE BANK GROUP CÔTE D’IVOIRE",
                'sector'       => 'Banque',
                'country'      => 'CI',
                'is_listed'    => true,
                'listing_date' => '2026-09-24',
                'updated_at'   => now(),
            ] + ($existing ? [] : ['created_at' => now()])
        );
    }

    public function down(): void
    {
        // financial_reports est en cascadeOnDelete : on ne supprime pas
        // la société si des rapports y sont déjà rattachés.
        $societe = DB::table('societes')->where('code', 'BBGC')->first();

        if ($societe && !DB::table('financial_reports')->where('societe_id', $societe->id)->exists()) {
            DB::table('societes')->where('id', $societe->id)->delete();
        }
    }
};
