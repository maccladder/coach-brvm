<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Societe;

class SocietesSeeder extends Seeder
{
    public function run(): void
    {
        $societes = [

            // =========================
            // 🇨🇮 CÔTE D’IVOIRE
            // =========================
            ['code' => 'SODECI', 'name' => 'SODECI', 'sector' => 'Eau', 'country' => 'CI'],
            ['code' => 'CIE', 'name' => 'Compagnie Ivoirienne d’Électricité', 'sector' => 'Énergie', 'country' => 'CI'],
            ['code' => 'BICICI', 'name' => 'BICICI', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'SGBCI', 'name' => 'Société Générale Côte d’Ivoire', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'SIB', 'name' => 'Société Ivoirienne de Banque', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'BOA_CI', 'name' => 'Bank of Africa Côte d’Ivoire', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'NSIA_CI', 'name' => 'NSIA Banque Côte d’Ivoire', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'ECOBANK_CI', 'name' => 'Ecobank Côte d’Ivoire', 'sector' => 'Banque', 'country' => 'CI'],
            ['code' => 'TOTAL_CI', 'name' => 'TotalEnergies Marketing Côte d’Ivoire', 'sector' => 'Pétrole', 'country' => 'CI'],
            ['code' => 'VIVO_CI', 'name' => 'Vivo Energy Côte d’Ivoire', 'sector' => 'Pétrole', 'country' => 'CI'],
            ['code' => 'SERVAIR_ABJ', 'name' => 'Servair Abidjan', 'sector' => 'Services', 'country' => 'CI'],
            ['code' => 'SICABLE', 'name' => 'SICABLE Côte d’Ivoire', 'sector' => 'Industrie', 'country' => 'CI'],
            ['code' => 'SETAO', 'name' => 'SETAO Côte d’Ivoire', 'sector' => 'Immobilier', 'country' => 'CI'],
            ['code' => 'PALMCI', 'name' => 'Palm Côte d’Ivoire', 'sector' => 'Agro-industrie', 'country' => 'CI'],
            ['code' => 'SAPH', 'name' => 'Société Africaine de Plantations d’Hévéas', 'sector' => 'Agro-industrie', 'country' => 'CI'],
            ['code' => 'SUCRIVOIRE', 'name' => 'Sucrivoire', 'sector' => 'Agro-industrie', 'country' => 'CI'],
            ['code' => 'SMB_CI', 'name' => 'SMB Côte d’Ivoire', 'sector' => 'Industrie', 'country' => 'CI'],

            // =========================
            // 🇸🇳 SÉNÉGAL
            // =========================
            ['code' => 'SONATEL', 'name' => 'Sonatel', 'sector' => 'Télécoms', 'country' => 'SN'],
            ['code' => 'CBAO', 'name' => 'CBAO Attijariwafa Bank', 'sector' => 'Banque', 'country' => 'SN'],
            ['code' => 'BOA_SN', 'name' => 'Bank of Africa Sénégal', 'sector' => 'Banque', 'country' => 'SN'],
            ['code' => 'TOTAL_SN', 'name' => 'TotalEnergies Sénégal', 'sector' => 'Pétrole', 'country' => 'SN'],

            // =========================
            // 🇧🇫 BURKINA FASO
            // =========================
            ['code' => 'ONATEL_BF', 'name' => 'Onatel Burkina Faso', 'sector' => 'Télécoms', 'country' => 'BF'],
            ['code' => 'BOA_BF', 'name' => 'Bank of Africa Burkina Faso', 'sector' => 'Banque', 'country' => 'BF'],
            ['code' => 'SOPAFER_B', 'name' => 'SOPAFER-B', 'sector' => 'Transport', 'country' => 'BF'],

            // =========================
            // 🇧🇯 BÉNIN
            // =========================
            ['code' => 'BOA_BJ', 'name' => 'Bank of Africa Bénin', 'sector' => 'Banque', 'country' => 'BJ'],

            // =========================
            // 🇲🇱 MALI
            // =========================
            ['code' => 'BOA_ML', 'name' => 'Bank of Africa Mali', 'sector' => 'Banque', 'country' => 'ML'],

            // =========================
            // 🇹🇬 TOGO
            // =========================
            ['code' => 'BOA_TG', 'name' => 'Bank of Africa Togo', 'sector' => 'Banque', 'country' => 'TG'],
            ['code' => 'NSIA_TG', 'name' => 'NSIA Banque Togo', 'sector' => 'Banque', 'country' => 'TG'],

            // =========================
            // 🇳🇪 NIGER
            // =========================
            ['code' => 'BOA_NE', 'name' => 'Bank of Africa Niger', 'sector' => 'Banque', 'country' => 'NE'],

            // =========================
            // 🇬🇼 GUINÉE-BISSAU
            // =========================
            ['code' => 'BOA_GW', 'name' => 'Bank of Africa Guinée-Bissau', 'sector' => 'Banque', 'country' => 'GW'],
        ];

        foreach ($societes as $societe) {
            Societe::updateOrCreate(
                ['code' => $societe['code']],
                $societe
            );
        }
    }
}
