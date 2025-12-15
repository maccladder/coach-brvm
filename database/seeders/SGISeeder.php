<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SGI;
use Illuminate\Support\Str;

class SGISeeder extends Seeder
{
    public function run(): void
    {
        $sourceUrl = 'https://www.brvm.org/fr/intervenants/sgi/tous';

        $items = [
            // =========================
            // BURKINA FASO
            // =========================
            [
                'country' => 'Burkina Faso',
                'name' => "Société Africaine d'Ingénierie et d'Intermédiation Financières (SA2IF)",
                'city' => 'Ouagadougou',
                'address' => 'Ouaga Kossyam 10010 Ouaga 2000, Extension sud non loin de Salitas',
                'po_box' => '05 BV 30130 Ouagadougou 05',
                'email' => 'sa2if@sa2if.com',
                'phone' => '(226) 75 20 01 01 / 64 36 99 99',
                'phone2' => null,
                'website' => null,
            ],
            [
                'country' => 'Burkina Faso',
                'name' => 'SGI CORIS BOURSE S.A',
                'city' => 'Ouagadougou',
                'address' => '1242 Avenue Dr Kwame Nkrumah, Immeuble Coris Bank International, 01',
                'po_box' => '6585 Ouagadougou 01',
                'email' => 'corisbourse@corisbourse.com',
                'phone' => '(226) 50 33 14 85 / 50 72 73',
                'phone2' => '(226) 50 33 14 83',
                'website' => null,
            ],
            [
                'country' => 'Burkina Faso',
                'name' => 'SGI SBIF',
                'city' => 'Ouagadougou',
                'address' => 'Avenue John Kennedy, 01',
                'po_box' => '53 94 Ouagadougou 01',
                'email' => 'sbif@fasonet.bf',
                'phone' => '(226) 25 33 04 91/92',
                'phone2' => '(226) 25 33 04 90',
                'website' => null,
            ],
            [
                'country' => 'Burkina Faso',
                'name' => 'Image finances Internationales',
                'city' => 'Ouagadougou',
                'address' => 'Ouaga 2000, coté Est de la mosquée KANAZOE',
                'po_box' => '05 BV 30130 Ouagadougou 05 - BF',
                'email' => 'contact@image-finance.com',
                'phone' => '+226 25 66 78 78',
                'phone2' => '+226 04 00 00 46',
                'website' => 'www.image-finance.com',
            ],

            // =========================
            // CÔTE D’IVOIRE
            // =========================
            [
                'country' => "Côte d'Ivoire",
                'name' => 'SOCIETE GENERALE CAPITAL SECURITIES WEST AFRICA',
                'city' => 'Abidjan',
                'address' => 'Bd Hassan II, Immeuble Ivoire Trade Center - Cocody – Abidjan',
                'po_box' => null,
                'email' => 'filiale.sogebourse@socgen.com',
                'phone' => '(225) 27 20 20 12 65',
                'phone2' => '(225) 27 20 20 14 89',
                'website' => 'https://societegenerale.africa/fr/des-services-sur-mesure/une-expertise-marches-financiers/gestion-intermediation/',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'ATLANTIQUE FINANCE',
                'city' => 'Abidjan',
                'address' => '15, Av. Joseph Anoma, Abidjan-Plateau, 10ème étage',
                'po_box' => '04 BP 1036 Abidjan 04',
                'email' => 'atlantiquefinance@banqueatlantique.net',
                'phone' => '27 20 31 21 21',
                'phone2' => '27 20 31 21 23',
                'website' => 'https://www.atlantiquefinance.net/',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'ATTIJARI SECURITIES WEST AFRICA',
                'city' => 'Abidjan',
                'address' => 'Rue Gourgas - Tour Immeuble Alpha 2000 3ème étage',
                'po_box' => '1300 Abidjan',
                'email' => 'aswa@sib.ci',
                'phone' => '(225) 27 20 21 98 26',
                'phone2' => null,
                'website' => 'www.sib.ci',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'BOA CAPITAL SECURITIES',
                'city' => 'Abidjan',
                'address' => 'Plateau - Boulevard de la République, Avenue Crozet, Immeuble XL au 2ème étage.',
                'po_box' => '01 BP 4854 Abidjan 01',
                'email' => 'info@boacapital.com',
                'phone' => '(225) 27 20 30 21 22',
                'phone2' => '(225) 27 20 32 04 68',
                'website' => 'www.boacapital.com',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'BRIDGE SECURITIES',
                'city' => 'Abidjan',
                'address' => 'Immeuble THE ONE, Cocody 33, Rue de la Cannebière',
                'po_box' => '01 BP 13002 Abidjan 01',
                'email' => 'info@bridge-securities.com',
                'phone' => '(225) 05 85 74 98 98',
                'phone2' => '05 74 80 80 31',
                'website' => 'www.bridge-securities.com',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'BSIC CAPITAL SA',
                'city' => 'Abidjan',
                'address' => 'Avenue Noguès, Immeuble Broadway Center 3ème étage Plateau',
                'po_box' => "01 BP 7032 Abidjan 01 Côte d'Ivoire",
                'email' => 'bsic.capital@bsicbank.com',
                'phone' => '+225 27 20 31 71 11',
                'phone2' => '+225 27 20 31 71 12',
                'website' => 'https://bsiccapital.com/',
            ],

            [
    'country' => "Côte d'Ivoire",
    'name' => 'SGI BICI BOURSE',
    'city' => 'Abidjan',
    'address' => "1er étage de l'agence BICICI_AGHIEN, Cocody II Plateau, Boulevard Latrille, Carrefour Duncan",
    'po_box' => '01 BP 1298 Abidjan',
    'email' => 'bicibourse@africa.bnpparibas.com',
    'phone' => '(225) 27 20 20 16 68',
    'phone2' => '(225) 27 20 21 47 22',
    'website' => null,
],

            [
                'country' => "Côte d'Ivoire",
                'name' => 'GEK CAPITAL',
                'city' => 'Abidjan',
                'address' => 'Abidjan, Cocody, Riviera Golf, Cité Riviera Beach, Villa Emeraude',
                'po_box' => '17 BP 41 Abidjan 17',
                'email' => 'info@gekcapital.com',
                'phone' => '+225 27 22 22 43 60',
                'phone2' => null,
                'website' => 'www.gekcapital.com',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'MATHA SECURITIES',
                'city' => 'Abidjan',
                'address' => 'Immeuble TROPIQUE 3 – 3ème étage Boulevard de la République – Plateau',
                'po_box' => "01 BP 10762 Abidjan 01 – Côte d'Ivoire",
                'email' => null,
                'phone' => '+225 27 20 24 30 30',
                'phone2' => null,
                'website' => 'https://mathasecurities.com/',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'NSIA FINANCE',
                'city' => 'Abidjan',
                'address' => '8 et 10, Avenue Joseph Anoma, Tour BIAO 14ème étage',
                'po_box' => '18 BP 2294 Abidjan 18',
                'email' => 'nsiafinance@nsiafinance.com',
                'phone' => '27 20 20 06 53',
                'phone2' => null,
                'website' => 'https://www.nsiafinance.com/',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'SGI EDC Investment Corporation',
                'city' => 'Abidjan',
                'address' => 'Avenue Houdaïlle, 2ème étage Immeuble Ecobank, 01',
                'po_box' => '4107 Abidjan 01',
                'email' => 'eic@ecobank.com',
                'phone' => '(225) 27 20 21 10 44',
                'phone2' => '(225) 27 20 21 10 46',
                'website' => null,
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'SGI HUDSON & Cie',
                'city' => 'Abidjan',
                'address' => "24 Boulevard Clozel, Avenue Lamblin, Immeuble Le 24, 4e étage",
                'po_box' => "18 BP 2294 Abidjan 18 COTE D'IVOIRE",
                'email' => 'info@hudson-cie.com',
                'phone' => '(225) 27 20 31 55 00',
                'phone2' => '(225) 27 20 33 22 24',
                'website' => 'http://www.hudson-cie.com',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'SGI MAC AFRICAN',
                'city' => 'Abidjan',
                'address' => "COCODY Riviera M'BADON à 300 mètres de l’Ambassade de Chine",
                'po_box' => null,
                'email' => 'macafrican@macafrican.com',
                'phone' => '(225) 07 68 311 125',
                'phone2' => '27 22 46 28 92',
                'website' => 'www.macafricansgi.com',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'ORAGROUP SECURITIES',
                'city' => 'Abidjan',
                'address' => 'COCODY MERMOZ, RUE JEANNE GERVAIS, LOT 7B ET 8',
                'po_box' => '08 BPM 701 ABIDJAN 08',
                'email' => 'contactOGS@orabank.net',
                'phone' => '(00225) 07 88 77 15 69',
                'phone2' => null,
                'website' => 'www.oragroupsecurities.net',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'PHOENIX CAPITAL MANAGEMENT',
                'city' => 'Abidjan',
                'address' => "Cocody Riviera 4 Golf, Abidjan Face à l'annexe de l'Ambassade des USA (Ivoire Golf Club)",
                'po_box' => '01 BP 12686 Abidjan 01',
                'email' => 'cms@phoenixafrica.com',
                'phone' => '(225) 27 22 59 85 80',
                'phone2' => null,
                'website' => 'https://phoenixafricaholding.com/pcm/',
            ],
            [
                'country' => "Côte d'Ivoire",
                'name' => 'SIRIUS CAPITAL',
                'city' => 'Abidjan',
                'address' => "ABIDJAN - PLATEAU - CÔTE D'IVOIRE, Rue Jesse Owens Sis Immeuble Sciam",
                'po_box' => '12916 Abidjan 01',
                'email' => 'contact@sirius.ci',
                'phone' => '(225) 27 20 24 24 65',
                'phone2' => '(225) 27 20 24 24 74',
                'website' => 'www.sirius.ci',
            ],

            // =========================
            // BENIN
            // =========================
            [
                'country' => 'Bénin',
                'name' => 'United Capital for Africa SA',
                'city' => 'Cotonou',
                'address' => "Av. proche, rue en face de l’eglise Saint Michel allant vers Caboma, immeuble Comète lot 202 parcelle « d »",
                'po_box' => '001 BP 8690 RP',
                'email' => 'uca@ucasgi.com',
                'phone' => '(229) 21 31 00 21',
                'phone2' => '61 18 18 00',
                'website' => 'www.ucasgi.com',
            ],
            [
                'country' => 'Bénin',
                'name' => 'SGI AFRICABOURSE',
                'city' => 'Cotonou',
                'address' => "Avenue Steinmetz en face de DHL ex- Air Gabon",
                'po_box' => '6002 COTONOU BENIN',
                'email' => 'africabourse@africabourse.com',
                'phone' => '(229) 21 31 88 35/36',
                'phone2' => '(229) 21 31 14 54',
                'website' => 'www.africabourse.com',
            ],

            // =========================
            // SENEGAL
            // =========================
            [
                'country' => 'Sénégal',
                'name' => 'ABCO Bourse',
                'city' => 'Dakar',
                'address' => 'Résidence Moumtazz, Avenue Cheikh Anta Diop, Mermoz - Face 2ème Porte',
                'po_box' => '6956 Dakar – Etoile',
                'email' => 'contact@abcobourse.com',
                'phone' => '(+221) 33 822 68 00',
                'phone2' => null,
                'website' => 'www.abcobourse.com',
            ],
            [
                'country' => 'Sénégal',
                'name' => 'EVEREST FINANCE',
                'city' => 'Dakar',
                'address' => 'Immeuble Platinum - 4ème étage, 18 Bd de la république',
                'po_box' => 'BP 11659',
                'email' => 'all@everestfin.com',
                'phone' => '(221) 33 82 28 700',
                'phone2' => null,
                'website' => 'www.everestfin.com',
            ],
            [
                'country' => 'Sénégal',
                'name' => 'FINANCE GESTION INTERMEDIATION',
                'city' => 'Dakar',
                'address' => 'Point E, Immeuble EPI, 1er étage, Boulevard du Sud x Rue 1',
                'po_box' => 'BP 25672 Dakar, Sénégal',
                'email' => 'contact@fgi-bourse.com',
                'phone' => '(+221) 33 867 60 42',
                'phone2' => '(221) 78 152 05 05',
                'website' => 'www.fgi-bourse.com',
            ],
            [
                'country' => 'Sénégal',
                'name' => 'Invictus Capital & Finance',
                'city' => 'Dakar',
                'address' => 'Immeuble El Hadji Rey TALL AMAR Lot N°46, 21 Av. Lamine Gueye x Dodds, 12ème étage',
                'po_box' => 'BP 15 498 Dakar, Sénégal',
                'email' => 'contact@invictuscapfin.com',
                'phone' => '(221) 33 864 58 58',
                'phone2' => '(221) 33 820 02 25',
                'website' => 'www.invictuscapfin.com',
            ],
            [
                'country' => 'Sénégal',
                'name' => 'SGI CGF BOURSE',
                'city' => 'Dakar',
                'address' => 'Km 6, Av Cheick Anta Diop, Immeuble El Hadji Serigne Bassirou Mbacké',
                'po_box' => null,
                'email' => 'cgfbourse@cgfbourse.com',
                'phone' => '(221) 33 864 97 97',
                'phone2' => '(221) 33 824 03 34',
                'website' => null,
            ],
            [
                'country' => 'Sénégal',
                'name' => 'SGI IMPAXIS SECURITIES SA',
                'city' => 'Dakar',
                'address' => 'Sacré Coeur 3 Extension VDN n° 10 077',
                'po_box' => '45545 DAKAR Sénégal',
                'email' => 'Impaxis.securities@impaxis-securities.com',
                'phone' => '(221) 33 869 31 40/47',
                'phone2' => '(221) 33 864 53 41',
                'website' => null,
            ],

            // =========================
            // MALI
            // =========================
            [
                'country' => 'Mali',
                'name' => "Compagnie d’Ingénierie Financière et d’Assistance en Bourse",
                'city' => 'Bamako',
                'address' => 'Immeuble SANLAM 3ième étage, Boulevard du 22 Octobre 1946, Quartier du Fleuve',
                'po_box' => null,
                'email' => 'info@cifabourse.com',
                'phone' => '00223 20 23 50 20',
                'phone2' => null,
                'website' => 'www.cifabourse.com',
            ],
            [
                'country' => 'Mali',
                'name' => 'GLOBAL CAPITAL',
                'city' => 'Bamako',
                'address' => 'Bamako ACI 2000 Rue 239 Parcelle 2355 1er étage',
                'po_box' => 'BP E3407',
                'email' => 'contact@sgiglobalcapital.com',
                'phone' => '+223 44 90 59 74',
                'phone2' => '44 90 59 75',
                'website' => 'www.sgiglobalcapital.com',
            ],
            [
                'country' => 'Mali',
                'name' => 'SGI MALI S.A',
                'city' => null,
                'address' => 'Immeuble Ali Baba (ABKII) 2ème Etage, Bureau 209-210',
                'po_box' => null,
                'email' => 'SGI@SGImali.com',
                'phone' => '(223) 20 29 41 19',
                'phone2' => '(223) 20 29 29 75',
                'website' => null,
            ],

            // =========================
            // NIGER
            // =========================
            [
                'country' => 'Niger',
                'name' => 'SGI NIGER S.A',
                'city' => 'Niamey',
                'address' => '258 B Rue du Grand Hôtel',
                'po_box' => '10812 Niamey',
                'email' => 'sginiger@sginiger.com',
                'phone' => '(227) 20 73 78 18',
                'phone2' => '(227) 20 73 78 16',
                'website' => 'www.sginiger.com',
            ],

            // =========================
            // TOGO
            // =========================
            [
                'country' => 'Togo',
                'name' => 'SGI TOGO S.A',
                'city' => 'Lomé',
                'address' => 'PLACE VAN VOLLENHOVEN, UTB Centrale 3ème Etage',
                'po_box' => '2312 LOME',
                'email' => 'sgi-togo@ids.tg',
                'phone' => '(228) 22 22 31 45',
                'phone2' => '(228) 22 22 31 47',
                'website' => null,
            ],
        ];

        foreach ($items as $data) {

            // Website clean
            if (!empty($data['website'])) {
                if (!Str::startsWith($data['website'], ['http://', 'https://'])) {
                    $data['website'] = 'https://' . $data['website'];
                }
            }

            // source fields
            $data['source_name'] = 'BRVM';
            $data['source_url']  = $sourceUrl;
            $data['is_active']   = true;

            // slug unique
            $data['slug'] = Str::slug($data['name'] . '-' . $data['country']);

            SGI::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
