<?php

namespace Database\Seeders;

use App\Models\BrvmDividende;
use Illuminate\Database\Seeder;

class Dividendes2026Seeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['ticker' => 'BOABF', 'societe' => 'BANK OF AFRICA BURKINA FASO',  'dividende_net' => 397.00, 'rendement_net' => 7.16, 'date_paiement' => '2026-04-23'],
            ['ticker' => 'BOAC',  'societe' => 'BANK OF AFRICA CÔTE D\'IVOIRE', 'dividende_net' => 594.50, 'rendement_net' => 7.09, 'date_paiement' => '2026-05-06'],
            ['ticker' => 'BOAB',  'societe' => 'BANK OF AFRICA BENIN',          'dividende_net' => 585.00, 'rendement_net' => 7.33, 'date_paiement' => '2026-05-15'],
            ['ticker' => 'ECOC',  'societe' => 'ECOBANK COTE D\'IVOIRE',        'dividende_net' => 781.00, 'rendement_net' => 4.97, 'date_paiement' => '2026-05-22'],
            ['ticker' => 'SNTS',  'societe' => 'SONATEL',                       'dividende_net' => 1740.00,'rendement_net' => 6.13, 'date_paiement' => '2026-05-25'],
            ['ticker' => 'BOAS',  'societe' => 'BANK OF AFRICA SENEGAL',        'dividende_net' => 450.00, 'rendement_net' => 6.83, 'date_paiement' => '2026-06-01'],
            ['ticker' => 'BOAM',  'societe' => 'BANK OF AFRICA MALI',           'dividende_net' => 305.00, 'rendement_net' => 6.51, 'date_paiement' => '2026-06-03'],
            ['ticker' => 'ONTBF', 'societe' => 'ONATEL',                        'dividende_net' => 145.30, 'rendement_net' => 5.19, 'date_paiement' => '2026-06-15'],
            ['ticker' => 'CBIBF', 'societe' => 'CORIS BANK INTERNATIONAL',      'dividende_net' => 900.00, 'rendement_net' => 6.00, 'date_paiement' => '2026-07-07'],
            ['ticker' => 'PALC',  'societe' => 'PALMCI',                        'dividende_net' => 441.80, 'rendement_net' => 5.48, 'date_paiement' => null],
            ['ticker' => 'SPHC',  'societe' => 'SAPH CI',                       'dividende_net' => 430.30, 'rendement_net' => 6.06, 'date_paiement' => null],
            ['ticker' => 'ORAC',  'societe' => 'ORANGE COTE D\'IVOIRE',         'dividende_net' => 704.00, 'rendement_net' => 4.60, 'date_paiement' => null],
            ['ticker' => 'CABC',  'societe' => 'SICABLE CI',                    'dividende_net' => 152.00, 'rendement_net' => 4.66, 'date_paiement' => null],
            ['ticker' => 'TTLC',  'societe' => 'TOTAL CI',                      'dividende_net' => 139.80, 'rendement_net' => 4.85, 'date_paiement' => null],
            ['ticker' => 'SGBC',  'societe' => 'SOCIETE GENERALE CI',           'dividende_net' => 2293.30,'rendement_net' => 6.95, 'date_paiement' => null],
            ['ticker' => 'ETIT',  'societe' => 'ETI TG',                        'dividende_net' => 0.90,   'rendement_net' => 3.21, 'date_paiement' => null],
        ];

        foreach ($rows as $row) {
            BrvmDividende::updateOrCreate(
                ['ticker' => $row['ticker'], 'year' => 2026],
                array_merge($row, ['year' => 2026])
            );
        }
    }
}
