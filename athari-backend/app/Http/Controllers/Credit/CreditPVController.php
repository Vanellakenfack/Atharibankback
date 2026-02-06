<?php

namespace App\Http\Controllers\Credit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Credit\CreditPV;
use App\Models\Credit\CreditApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditPVController extends Controller
{
    /**
     * Liste tous les PVs avec leurs relations
     */
    public function index()
    {
        $pvs = CreditPV::with(['creditApplication.user', 'generateur'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $pvs
        ]);
    }

    /**
     * Afficher un seul PV
     */
    public function show($id)
    {
        $pv = CreditPV::with(['creditApplication.user', 'generateur'])->find($id);
        if (!$pv) {
            return response()->json([
                'status' => 'error',
                'message' => 'PV non trouvé'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pv
        ]);
    }

    /**
     * Télécharger le PDF du PV avec les vraies données de la base
     */
    public function downloadPDF($id)
    {
        try {
            // 1. On récupère le PV avec toutes les relations nécessaires
            // On inclut 'avisCredits.user' pour avoir le nom des gens qui ont voté
            $pv = CreditPV::with([
                'creditApplication.user', 
                'creditApplication.avisCredits.user', 
                'generateur'
            ])->findOrFail($id);

            // 2. Préparation des données pour la vue
            $data = [
                'pv' => $pv,
                'application' => $pv->creditApplication,
                // On récupère les avis réels triés pour l'historique
                'avisReels' => $pv->creditApplication->avisCredits()->with('user')->orderBy('created_at', 'asc')->get(),
                'date_generation' => now()->format('d/m/Y H:i')
            ];

            // 3. Génération du PDF
            $pdf = Pdf::loadView('pdf.credit_pv', $data);

            // Optionnel: Configuration du format
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('PV_' . $pv->numero_pv . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la génération du PV : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Création manuelle d'un PV
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credit_application_id' => 'required|exists:credit_applications,id',
            'numero_pv' => 'required|string|unique:credit_pvs,numero_pv',
            'date_pv' => 'required|date',
            'lieu_pv' => 'required|string',
            'montant_approuvee' => 'required|numeric',
            'resume_decision' => 'required|string',
            'duree_approuvee' => 'nullable|integer',
            'nom_garantie' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $pv = CreditPV::create([
            'credit_application_id' => $request->credit_application_id,
            'numero_pv' => $request->numero_pv,
            'date_pv' => $request->date_pv,
            'lieu_pv' => $request->lieu_pv,
            'montant_approuvee' => $request->montant_approuvee,
            'resume_decision' => $request->resume_decision,
            'duree_approuvee' => $request->duree_approuvee,
            'nom_garantie' => $request->nom_garantie,
            'genere_par' => Auth::id(),
            'statut' => 'GENERE'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $pv
        ], 201);
    }

    /**
     * Suppression d'un PV
     */
    public function destroy($id)
    {
        $pv = CreditPV::find($id);
        if (!$pv) {
            return response()->json([
                'status' => 'error',
                'message' => 'PV non trouvé'
            ], 404);
        }

        $pv->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'PV supprimé avec succès'
        ]);
    }
}