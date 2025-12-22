<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinancialReport;
use App\Models\Societe;

class FinancialReports2025Seeder extends Seeder
{
    public function run(): void
    {
        $year = 2025;
        $periods = FinancialReport::PERIODS;

        // On prend toutes les sociétés "cotées" (à adapter si tu as un champ is_listed)
        $societes = Societe::query()
            ->orderBy('name') // adapte si ton champ s'appelle autrement (ex: libelle)
            ->get(['id']);

        foreach ($societes as $societe) {
            foreach ($periods as $period) {
                FinancialReport::updateOrCreate(
                    [
                        'societe_id' => $societe->id,
                        'year' => $year,
                        'period' => $period,
                    ],
                    [
                        'status' => 'pending',
                        'file_path' => null,
                        'published_at' => null,
                        'uploaded_by' => null,
                    ]
                );
            }
        }
    }
}
