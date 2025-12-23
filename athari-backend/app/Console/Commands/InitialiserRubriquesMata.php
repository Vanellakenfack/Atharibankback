<?php
// app/Console/Commands/InitialiserRubriquesMata.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\compte\Compte;
use App\Services\Frais\GestionRubriqueMataService;

class InitialiserRubriquesMata extends Command
{
    protected $signature = 'mata:initialiser-rubriques {compteId? : ID du compte}';
    protected $description = 'Initialiser les mouvements pour les rubriques MATA d\'un compte';
    
    public function handle(GestionRubriqueMataService $service)
    {
        $compteId = $this->argument('compteId');
        
        if ($compteId) {
            $compte = Compte::with('typeCompte')->find($compteId);
            
            if (!$compte) {
                $this->error('Compte non trouvé');
                return 1;
            }
            
            if (!$compte->typeCompte->est_mata) {
                $this->error('Ce compte n\'est pas un compte MATA');
                return 1;
            }
            
            $this->initialiserCompte($compte, $service);
        } else {
            // Initialiser tous les comptes MATA
            $comptes = Compte::whereHas('typeCompte', function($q) {
                $q->where('est_mata', true);
            })->get();
            
            $this->info('Initialisation de ' . $comptes->count() . ' comptes MATA');
            
            foreach ($comptes as $compte) {
                $this->initialiserCompte($compte, $service);
            }
        }
        
        $this->info('✅ Initialisation terminée');
        return 0;
    }
    
    private function initialiserCompte($compte, $service)
    {
        $this->line('Traitement du compte ' . $compte->numero_compte . '...');
        
        try {
            // Si le compte a un solde, le répartir sur les rubriques
            if ($compte->solde > 0) {
                $rubriques = json_decode($compte->rubriques_mata, true) ?? [];
                
                if (!empty($rubriques)) {
                    // Créer un mouvement initial pour chaque rubrique avec 0
                    foreach ($rubriques as $rubrique) {
                        \App\Models\frais\MouvementRubriqueMata::create([
                            'compte_id' => $compte->id,
                            'rubrique' => $rubrique,
                            'montant' => 0,
                            'solde_rubrique' => 0,
                            'solde_global' => $compte->solde,
                            'type_mouvement' => 'versement',
                            'description' => 'Initialisation rubrique'
                        ]);
                    }
                    
                    $this->info('   ✅ Rubriques initialisées pour ' . $compte->numero_compte);
                }
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erreur: ' . $e->getMessage());
        }
    }
}