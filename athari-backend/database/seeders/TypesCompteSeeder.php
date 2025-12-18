<?php

namespace Database\Seeders;

use App\Models\ChapitresComptable;
use App\Models\TypesCompte;
use Illuminate\Database\Seeder;

class TypesCompteSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les chapitres comptables
        $chapters = [
            ['code' => '371', 'name' => 'Comptes ordinaires', 'description' => 'Comptes courants et épargne'],
            ['code' => '372', 'name' => 'Comptes MATA BOOST', 'description' => 'Comptes de collecte MATA BOOST'],
            ['code' => '373', 'name' => 'Comptes de collecte', 'description' => 'Comptes de collecte journalière'],
            ['code' => '361', 'name' => 'DAT', 'description' => 'Dépôts à terme'],
        ];

        foreach ($chapters as $chapter) {
            ChapitresComptable::firstOrCreate(['code' => $chapter['code']], $chapter);
        }

        // Créer les types de comptes
        $accountTypes = [
            // MATA BOOST
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '372')->first()->id,
                'code' => 'MATA-VUE',
                'name' => 'MATA BOOST À Vue',
                'category' => 'mata_boost',
                'sub_category' => 'a_vue',
                'opening_fee' => 500,
                'monthly_commission' => 300, // ou 1000 si collecte >= 50000
                'withdrawal_fee' => 200,
                'sms_fee' => 200,
                'mata_boost_sections' => ['business', 'sante', 'scolarite', 'fete', 'fournitures', 'immobilier'],
            ],
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '372')->first()->id,
                'code' => 'MATA-BLOQUE',
                'name' => 'MATA BOOST Bloqué',
                'category' => 'mata_boost',
                'sub_category' => 'bloque',
                'opening_fee' => 500,
                'unblocking_fee' => 1500,
                'early_withdrawal_penalty_rate' => 3,
                'mata_boost_sections' => ['business', 'sante', 'scolarite', 'fete', 'fournitures', 'immobilier'],
            ],
            
            // Comptes Courants
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '371')->first()->id,
                'code' => 'CC-PART',
                'name' => 'Compte Courant Particuliers',
                'category' => 'courant',
                'sub_category' => 'particulier',
                'opening_fee' => 3500,
                'monthly_commission' => 2000,
                'sms_fee' => 200,
                'requires_checkbook' => true,
            ],
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '371')->first()->id,
                'code' => 'CC-ENT',
                'name' => 'Compte Courant Entreprises',
                'category' => 'courant',
                'sub_category' => 'entreprise',
                'opening_fee' => 10000,
                'monthly_commission' => 5000,
                'sms_fee' => 200,
                'requires_checkbook' => true,
            ],
            
            // Collecte Journalière
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '373')->first()->id,
                'code' => 'COLL-JOUR',
                'name' => 'Collecte Journalière',
                'category' => 'collecte',
                'sub_category' => 'a_vue',
                'opening_fee' => 0,
                'monthly_commission' => 1000,
            ],
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '373')->first()->id,
                'code' => 'COLL-BLOQUE',
                'name' => 'Collecte Bloquée',
                'category' => 'collecte',
                'sub_category' => 'bloque',
                'opening_fee' => 0,
                'unblocking_fee' => 1000,
                'early_withdrawal_penalty_rate' => 3,
            ],
            
            // Épargne
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '371')->first()->id,
                'code' => 'EP-FAMILY',
                'name' => 'Épargne Family',
                'category' => 'epargne',
                'sub_category' => 'family',
                'is_remunerated' => true,
            ],
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '371')->first()->id,
                'code' => 'EP-CLASS',
                'name' => 'Épargne Classique',
                'category' => 'epargne',
                'sub_category' => 'classique',
                'is_remunerated' => true,
            ],
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '371')->first()->id,
                'code' => 'EP-LOG',
                'name' => 'Épargne Logement',
                'category' => 'epargne',
                'sub_category' => 'logement',
                'is_remunerated' => false,
            ],
            
            // DAT
            [
                'accounting_chapter_id' => ChapitresComptable::where('code', '361')->first()->id,
                'code' => 'DAT',
                'name' => 'Dépôt à Terme',
                'category' => 'dat',
                'is_remunerated' => true,
                'early_withdrawal_penalty_rate' => 10,
            ],
        ];

        foreach ($accountTypes as $type) {
            TypesCompte::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}