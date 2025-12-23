<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\compte\frais\ParametrageFrais;
use App\Models\compte\TypeCompte;
use App\Models\chapitre\PlanComptable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParametrageFraisController extends Controller
{
    public function index()
    {
        $frais = ParametrageFrais::with(['typeCompte', 'compteProduit', 'compteAttente'])
            ->orderBy('libelle_frais')
            ->paginate(20);
            
        return view('frais.parametrage.index', compact('frais'));
    }

    public function create()
    {
        $typesComptes = TypeCompte::actif()->get();
        $plansComptables = PlanComptable::actif()->get();
        $comptesProduits = PlanComptable::where('type', 'PRODUIT')->actif()->get();
        $comptesAttente = PlanComptable::where('code_chapitre', 'like', '471%')->actif()->get();
        
        return view('frais.parametrage.create', compact('typesComptes', 'plansComptables', 'comptesProduits', 'comptesAttente'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_frais' => 'required|unique:parametrage_frais|max:50',
            'libelle_frais' => 'required|max:255',
            'type_frais' => 'required|in:OUVERTURE,TENUE_COMPTE,SMS,RETRAIT,COLLECTE,DEBLOCAGE,PENALITE,INTERET,AUTRE',
            'base_calcul' => 'required|in:FIXE,POURCENTAGE_SOLDE,POURCENTAGE_VERSEMENT,POURCENTAGE_RETRAIT,SEUIL_COLLECTE,NON_APPLICABLE',
            'montant_fixe' => 'nullable|numeric|min:0',
            'taux_pourcentage' => 'nullable|numeric|min:0|max:100',
            'periodicite' => 'required|in:PONCTUEL,QUOTIDIEN,HEBDOMADAIRE,MENSUEL,TRIMESTRIEL,ANNUEL,PAR_OPERATION',
            'jour_prelevement' => 'nullable|integer|min:1|max:31',
            'compte_produit_id' => 'required|exists:plan_comptable,id',
            'compte_attente_id' => 'nullable|exists:plan_comptable,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $frais = ParametrageFrais::create([
            'type_compte_id' => $request->type_compte_id,
            'plan_comptable_id' => $request->plan_comptable_id,
            'code_frais' => $request->code_frais,
            'libelle_frais' => $request->libelle_frais,
            'description' => $request->description,
            'type_frais' => $request->type_frais,
            'base_calcul' => $request->base_calcul,
            'montant_fixe' => $request->montant_fixe,
            'taux_pourcentage' => $request->taux_pourcentage,
            'seuil_minimum' => $request->seuil_minimum,
            'montant_seuil_atteint' => $request->montant_seuil_atteint,
            'montant_seuil_non_atteint' => $request->montant_seuil_non_atteint,
            'periodicite' => $request->periodicite,
            'jour_prelevement' => $request->jour_prelevement,
            'heure_prelevement' => $request->heure_prelevement,
            'prelevement_si_debiteur' => $request->boolean('prelevement_si_debiteur'),
            'bloquer_operation' => $request->boolean('bloquer_operation'),
            'solde_minimum_operation' => $request->solde_minimum_operation,
            'necessite_autorisation' => $request->boolean('necessite_autorisation'),
            'compte_produit_id' => $request->compte_produit_id,
            'compte_attente_id' => $request->compte_attente_id,
            'regles_speciales' => $request->regles_speciales ? json_decode($request->regles_speciales, true) : null,
            'etat' => $request->etat ?? 'ACTIF',
        ]);

        return redirect()->route('parametrage-frais.index')
            ->with('success', 'Paramétrage de frais créé avec succès.');
    }

    public function edit(ParametrageFrais $parametrageFrai)
    {
        $typesComptes = TypeCompte::actif()->get();
        $plansComptables = PlanComptable::actif()->get();
        $comptesProduits = PlanComptable::where('type', 'PRODUIT')->actif()->get();
        $comptesAttente = PlanComptable::where('code_chapitre', 'like', '471%')->actif()->get();
        
        return view('frais.parametrage.edit', compact('parametrageFrai', 'typesComptes', 'plansComptables', 'comptesProduits', 'comptesAttente'));
    }

    public function update(Request $request, ParametrageFrais $parametrageFrai)
    {
        $validator = Validator::make($request->all(), [
            'code_frais' => 'required|max:50|unique:parametrage_frais,code_frais,' . $parametrageFrai->id,
            'libelle_frais' => 'required|max:255',
            'type_frais' => 'required|in:OUVERTURE,TENUE_COMPTE,SMS,RETRAIT,COLLECTE,DEBLOCAGE,PENALITE,INTERET,AUTRE',
            'base_calcul' => 'required|in:FIXE,POURCENTAGE_SOLDE,POURCENTAGE_VERSEMENT,POURCENTAGE_RETRAIT,SEUIL_COLLECTE,NON_APPLICABLE',
            'montant_fixe' => 'nullable|numeric|min:0',
            'taux_pourcentage' => 'nullable|numeric|min:0|max:100',
            'compte_produit_id' => 'required|exists:plan_comptable,id',
            'compte_attente_id' => 'nullable|exists:plan_comptable,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $parametrageFrai->update([
            'type_compte_id' => $request->type_compte_id,
            'plan_comptable_id' => $request->plan_comptable_id,
            'code_frais' => $request->code_frais,
            'libelle_frais' => $request->libelle_frais,
            'description' => $request->description,
            'type_frais' => $request->type_frais,
            'base_calcul' => $request->base_calcul,
            'montant_fixe' => $request->montant_fixe,
            'taux_pourcentage' => $request->taux_pourcentage,
            'seuil_minimum' => $request->seuil_minimum,
            'montant_seuil_atteint' => $request->montant_seuil_atteint,
            'montant_seuil_non_atteint' => $request->montant_seuil_non_atteint,
            'periodicite' => $request->periodicite,
            'jour_prelevement' => $request->jour_prelevement,
            'heure_prelevement' => $request->heure_prelevement,
            'prelevement_si_debiteur' => $request->boolean('prelevement_si_debiteur'),
            'bloquer_operation' => $request->boolean('bloquer_operation'),
            'solde_minimum_operation' => $request->solde_minimum_operation,
            'necessite_autorisation' => $request->boolean('necessite_autorisation'),
            'compte_produit_id' => $request->compte_produit_id,
            'compte_attente_id' => $request->compte_attente_id,
            'regles_speciales' => $request->regles_speciales ? json_decode($request->regles_speciales, true) : null,
            'etat' => $request->etat ?? 'ACTIF',
        ]);

        return redirect()->route('parametrage-frais.index')
            ->with('success', 'Paramétrage de frais mis à jour avec succès.');
    }

    public function destroy(ParametrageFrais $parametrageFrai)
    {
        if ($parametrageFrai->fraisAppliques()->exists()) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer ce paramétrage car des frais ont déjà été appliqués.');
        }

        $parametrageFrai->delete();

        return redirect()->route('parametrage-frais.index')
            ->with('success', 'Paramétrage de frais supprimé avec succès.');
    }

    public function show(ParametrageFrais $parametrageFrai)
    {
        $parametrageFrai->load(['typeCompte', 'planComptable', 'compteProduit', 'compteAttente', 'fraisAppliques']);
        return view('frais.parametrage.show', compact('parametrageFrai'));
    }
}