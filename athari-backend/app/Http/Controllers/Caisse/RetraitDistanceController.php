<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Services\CaisseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Caisse\CaisseTransaction;
use Exception;

class RetraitDistanceController extends Controller
{
    protected $caisseService;

    public function __construct(CaisseService $caisseService)
    {
        $this->caisseService = $caisseService;
    }

    /**
     * Étape 1 : Le Caissier/Gestionnaire soumet la demande avec les images
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id'           => 'required|exists:comptes,id',
            'montant_brut'        => 'required|numeric|min:100',
            'gestionnaire_id'     => 'required|exists:gestionnaires,id',
            'pj_demande_retrait'  => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
            'pj_procuration'      => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'numero_bordereau'    => 'required|string',
            'type_bordereau'      => 'required|string',
            'bordereau_retrait'     => 'required|image|mimes:jpeg,png,jpg|max:2048',
            //'billetage'           => 'required|array', // Le détail des coupures
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->only([
                'compte_id', 'montant_brut', 'gestionnaire_id', 
                'numero_bordereau', 'type_bordereau',
                'pj_demande_retrait', 'pj_procuration','bordereau_retrait'
            ]);

            $billetage = $request->input('billetage');

            $transaction = $this->caisseService->initierRetraitDistance($data);

            return response()->json([
                'success' => true,
                'message' => 'Demande de retrait à distance transmise au Chef d\'Agence.',
                'data'    => $transaction
            ], 201);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Étape 2 : Le Chef d'Agence valide la demande
     */
   public function approuver(Request $request, $id)
{
    try {
        $result = $this->caisseService->approuverChefAgence($id);

        return response()->json([
            'success' => true,
            'message' => 'Demande approuvée. Le code de validation a été généré.',
            // On renvoie 'transaction' au lieu de 'data' pour correspondre au front
            'transaction' => $result['transaction'], 
            // On s'assure que le code est aussi disponible ICI
            'code_validation' => $result['code_validation'] 
        ]);
    } catch (Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}


/**
 * Étape 3 : La caissière saisit le code et effectue le décaissement réel
 */

public function confirmer(Request $request, $id)
{
    $request->validate([
        'code_validation' => 'required|string|size:6',
    ]);

    try {
        // Appelle le service pour faire la compta et le décaissement
        $transaction = $this->caisseService->confirmerRetraitCaissiere(
            $id, 
            $request->code_validation
        );

        return response()->json([
            'success' => true,
            'message' => 'Décaissement effectué avec succès.',
            'data'    => $transaction
        ]);
    } catch (Exception $e) {
        // Renvoie l'erreur métier (ex: "Code incorrect") avec un code 400
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}

    /**
     * Étape 2 (Alternative) : Le Chef d'Agence rejette la demande
     */
    public function rejeter(Request $request, $id)
    {
        $request->validate(['motif' => 'required|string|min:5']);

        try {
            $transaction = $this->caisseService->rejeterRetraitDistance($id, $request->motif);

            return response()->json([
                'success' => true,
                'message' => 'Retrait rejeté.',
                'data'    => $transaction
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function listeApprouvees()
{
    // On récupère les transactions approuvées par le CA mais pas encore décaissées
    $transactions = CaisseTransaction::with(['compte.client'])
        ->where('statut_workflow', 'APPROUVE_CA')
        ->where('is_retrait_distance', true)
       
        ->get();

    return response()->json([
        'success' => true,
        'data'    => $transactions
    ]);
}

    /**
     * Liste des retraits en attente pour le tableau de bord du CA
     */
    public function enAttente()
    {
        $transactions = CaisseTransaction::with(['compte.client', 'gestionnaire'])
            ->where('statut_workflow', 'EN_ATTENTE_CA')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $transactions]);
    }

    /**
 * Liste des retraits rejetés par le Chef d'Agence (Historique)
 */
public function listeRejetees()
{
    // On récupère les transactions marquées comme REJETE_CA
    $transactions = CaisseTransaction::with(['compte.client', 'gestionnaire'])
        ->where('statut', 'ANNULE') // Assure-toi que c'est le nom exact du statut dans ton Service
        ->where('is_retrait_distance', true)
        ->orderBy('updated_at', 'desc')
        ->limit(50) // On limite pour éviter de charger des années de données
        ->get();

    return response()->json([
        'success' => true,
        'data'    => $transactions
    ]);
}
}