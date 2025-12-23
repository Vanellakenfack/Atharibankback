<?php

namespace App\Http\Controllers\Frais;

use App\Http\Controllers\Controller;
use App\Models\compte\frais\FraisApplique;
use App\Models\compte\Compte;
use App\Models\compte\frais\ParametrageFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FraisAppliqueController extends Controller
{
    public function index(Request $request)
    {
        $query = FraisApplique::with(['compte', 'parametrageFrais', 'compteProduit'])
            ->orderBy('date_application', 'desc');

        // Filtres
        if ($request->filled('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }
        
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->filled('date_debut')) {
            $query->where('date_application', '>=', $request->date_debut);
        }
        
        if ($request->filled('date_fin')) {
            $query->where('date_application', '<=', $request->date_fin);
        }
        
        if ($request->filled('type_frais')) {
            $query->whereHas('parametrageFrais', function ($q) use ($request) {
                $q->where('type_frais', $request->type_frais);
            });
        }

        $frais = $query->paginate(20);
        $comptes = Compte::actif()->get();
        $statuts = ['CALCULE', 'A_PRELEVER', 'PRELEVE', 'EN_ATTENTE', 'ANNULE', 'ECHEC'];
        
        return view('frais.appliques.index', compact('frais', 'comptes', 'statuts'));
    }

    public function show(FraisApplique $fraisApplique)
    {
        $fraisApplique->load([
            'compte',
            'parametrageFrais',
            'compteProduit',
            'compteClient',
            'mouvements'
        ]);
        
        return view('frais.appliques.show', compact('fraisApplique'));
    }

    public function appliquerFrais(Request $request)
    {
        $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'parametrage_frais_id' => 'required|exists:parametrage_frais,id',
            'montant' => 'nullable|numeric|min:0',
        ]);

        $compte = Compte::findOrFail($request->compte_id);
        $parametrage = ParametrageFrais::findOrFail($request->parametrage_frais_id);

        // Calculer le montant
        $montant = $request->montant ?? $parametrage->calculerMontant($compte->solde);

        // Créer le frais appliqué
        $frais = FraisApplique::create([
            'compte_id' => $compte->id,
            'parametrage_frais_id' => $parametrage->id,
            'date_application' => now(),
            'montant_calcule' => $montant,
            'statut' => 'A_PRELEVER',
            'compte_produit_id' => $parametrage->compte_produit_id,
            'compte_client_id' => $compte->plan_comptable_id,
            'base_calcul_valeur' => $compte->solde,
            'methode_calcul' => $parametrage->base_calcul,
        ]);

        // Appliquer le frais
        $frais->appliquer();

        return redirect()->route('frais-appliques.show', $frais)
            ->with('success', 'Frais appliqué avec succès.');
    }

    public function relancerAttente(FraisApplique $fraisApplique)
    {
        if ($fraisApplique->statut !== 'EN_ATTENTE') {
            return redirect()->back()
                ->with('error', 'Ce frais n\'est pas en attente.');
        }

        // Vérifier si le solde est maintenant suffisant
        $compte = $fraisApplique->compte;
        
        if ($compte->solde >= $fraisApplique->montant_calcule) {
            $fraisApplique->appliquer();
            $message = 'Frais prélevé avec succès.';
        } else {
            $message = 'Solde toujours insuffisant. Le frais reste en attente.';
        }

        return redirect()->back()->with('info', $message);
    }

    public function statistiques(Request $request)
    {
        $debut = $request->input('date_debut', now()->startOfMonth());
        $fin = $request->input('date_fin', now()->endOfMonth());

        $statistiques = DB::table('frais_appliques as fa')
            ->select(
                'pf.libelle_frais',
                'pf.type_frais',
                DB::raw('COUNT(fa.id) as nombre_frais'),
                DB::raw('SUM(fa.montant_calcule) as montant_total'),
                DB::raw('SUM(CASE WHEN fa.statut = "PRELEVE" THEN fa.montant_calcule ELSE 0 END) as montant_preleve'),
                DB::raw('SUM(CASE WHEN fa.statut = "EN_ATTENTE" THEN fa.montant_calcule ELSE 0 END) as montant_attente')
            )
            ->join('parametrage_frais as pf', 'fa.parametrage_frais_id', '=', 'pf.id')
            ->whereBetween('fa.date_application', [$debut, $fin])
            ->groupBy('pf.libelle_frais', 'pf.type_frais')
            ->orderBy('montant_total', 'desc')
            ->get();

        return view('frais.appliques.statistiques', compact('statistiques', 'debut', 'fin'));
    }
}