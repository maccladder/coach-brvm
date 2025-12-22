<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Societe;

class SocietesSeeder extends Seeder
{
    public function run(): void
    {
        // ⚠️ Reset total (OK si tu n'as pas encore de données liées)
        DB::table('societes')->truncate();

        $societes = [
            // --- CI (Côte d’Ivoire)
            ['code' => 'ABJC', 'name' => "SERVAIR ABIDJAN CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'BICC', 'name' => "BICI CÔTE D’IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'BNBC', 'name' => "BERNABÉ CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'CABC', 'name' => "SICABLE CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'CFAC', 'name' => "CFAO MOTORS CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'CIEC', 'name' => "CIE CÔTE D’IVOIRE", 'sector' => 'Énergie', 'country' => 'CI'],
            ['code' => 'ECOC', 'name' => "ECOBANK CÔTE D’IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'FTSC', 'name' => "FILTISAC CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'NEIC', 'name' => "NEI-CEDA CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'NTLC', 'name' => "NESTLÉ CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'ORAC', 'name' => "ORANGE CÔTE D’IVOIRE", 'sector' => 'Télécoms', 'country' => 'CI'],
            ['code' => 'PALC', 'name' => "PALM CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'PRSC', 'name' => "TRACTAFRIC MOTORS CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SAFC', 'name' => "SAFCA - ALIOS FINANCE CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SDCC', 'name' => "SODECI CÔTE D’IVOIRE", 'sector' => 'Eau', 'country' => 'CI'],
            ['code' => 'SDSC', 'name' => "AFRICA GLOBAL LOGISTICS CÔTE D’IVOIRE (ex Bolloré)", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SEMC', 'name' => "CROWN SIEM CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SGBC', 'name' => "SOCIÉTÉ GÉNÉRALE CÔTE D’IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'SHEC', 'name' => "VIVO ENERGY CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SIBC', 'name' => "SOCIÉTÉ IVOIRIENNE DE BANQUE CÔTE D’IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'SICC', 'name' => "SICOR CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SIVC', 'name' => "ERIUM CÔTE D’IVOIRE (ex Air Liquide)", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SLBC', 'name' => "SOLIBRA CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SMBC', 'name' => "SMB CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SOGC', 'name' => "SOGB CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SPHC', 'name' => "SAPH CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'STAC', 'name' => "SETAO CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'STBC', 'name' => "SITAB CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'SCRC', 'name' => "SUCRIVOIRE CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'TTLC', 'name' => "TOTALENERGIES MARKETING CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'UNLC', 'name' => "UNILEVER CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'UNXC', 'name' => "UNIWAX CÔTE D’IVOIRE", 'sector' => null, 'country' => 'CI'],
            ['code' => 'NSBC', 'name' => "NSIA BANQUE COTE D'IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],


            // --- BJ (Bénin)
            ['code' => 'BICB', 'name' => "B.I.C.I.C. BÉNIN (BICB)", 'sector' => 'Banque', 'country' => 'BJ'],
            ['code' => 'BOAB', 'name' => "BANK OF AFRICA BÉNIN", 'sector' => 'Banque', 'country' => 'BJ'],
            ['code' => 'LNBB', 'name' => "LOTERIE NATIONALE DU BÉNIN", 'sector' => null, 'country' => 'BJ'],

            // --- BF (Burkina Faso)
            ['code' => 'BOABF', 'name' => "BANK OF AFRICA BURKINA FASO", 'sector' => 'Banque', 'country' => 'BF'],
            ['code' => 'CBIBF', 'name' => "CORIS BANK INTERNATIONAL BURKINA FASO", 'sector' => 'Banque', 'country' => 'BF'],
            ['code' => 'ONTBF', 'name' => "ONATEL BURKINA FASO", 'sector' => 'Télécoms', 'country' => 'BF'],

            // --- ML (Mali)
            ['code' => 'BOAM', 'name' => "BANK OF AFRICA MALI", 'sector' => 'Banque', 'country' => 'ML'],

            // --- NE (Niger)
            ['code' => 'BOAN', 'name' => "BANK OF AFRICA NIGER", 'sector' => 'Banque', 'country' => 'NE'],

            // --- SN (Sénégal)
            ['code' => 'BOAS', 'name' => "BANK OF AFRICA SÉNÉGAL", 'sector' => 'Banque', 'country' => 'SN'],
            ['code' => 'SNTS', 'name' => "SONATEL SÉNÉGAL", 'sector' => 'Télécoms', 'country' => 'SN'],
            ['code' => 'TTLS', 'name' => "TOTALENERGIES SÉNÉGAL", 'sector' => null, 'country' => 'SN'],

            // --- TG (Togo)
            ['code' => 'ETIT', 'name' => "ECOBANK TRANSNATIONAL INCORPORATED (TOGO)", 'sector' => 'Banque', 'country' => 'TG'],
            ['code' => 'ORGT', 'name' => "ORAGROUP TOGO", 'sector' => 'Banque', 'country' => 'TG'],

            // --- (Côte d'Ivoire) BOA CI
            ['code' => 'BOAC', 'name' => "BANK OF AFRICA CÔTE D’IVOIRE", 'sector' => 'Banque', 'country' => 'CI'],
        ];

        foreach ($societes as $s) {
            Societe::create($s);
        }
    }
}
