<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MOVIS CI (SVOC), radiée de la BRVM en 2025, reste présente dans l'historique
 * boc_stocks. On l'enregistre comme non cotée (is_listed = false) pour que les
 * vues "marché actuel" (radar, performances) l'excluent, sans toucher à ses
 * données historiques.
 *
 * Idempotente : updateOrInsert sur le code.
 */
return new class extends Migration {
    public function up(): void
    {
        $existing = DB::table('societes')->where('code', 'SVOC')->exists();

        DB::table('societes')->updateOrInsert(
            ['code' => 'SVOC'],
            [
                'name'       => "MOVIS CÔTE D’IVOIRE",
                'country'    => 'CI',
                'is_listed'  => false,
                'updated_at' => now(),
            ] + ($existing ? [] : ['created_at' => now()])
        );
    }

    public function down(): void
    {
        $societe = DB::table('societes')->where('code', 'SVOC')->first();

        if ($societe && !DB::table('financial_reports')->where('societe_id', $societe->id)->exists()) {
            DB::table('societes')->where('id', $societe->id)->delete();
        }
    }
};
