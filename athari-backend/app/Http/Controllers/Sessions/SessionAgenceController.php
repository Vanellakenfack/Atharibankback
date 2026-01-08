<?php

namespace App\Http\Controllers\Sessions;

use App\Http\Controllers\Controller;
use App\Services\SessionBancaireService;
use App\Models\SessionAgence\AgenceSession; // Import du modèle dans le nouveau dossier
use Illuminate\Http\Request;
use Exception;

class SessionAgenceController extends Controller
{
    protected $sessionService;

    public function __construct(SessionBancaireService $sessionService)
    {
        $this->sessionService = $sessionService;

       
    }

    /**
     * Étape 1 & 2 : Ouverture de la Journée et de l'Agence
     */
    public function ouvrirAgence(Request $request)
    {
        $request->validate([
            'agence_id' => 'required|exists:agencies,id',
            'date_comptable' => 'required|date',
        ]);

        try {
            // Appel au service pour créer le jour comptable et la session agence
            $jour = $this->sessionService->ouvrirJourneeComptable(
                $request->agence_id,
                $request->date_comptable,
                auth()->id()
            );

            $session = $this->sessionService->ouvrirAgenceSession(
                $request->agence_id,
                $jour->id,
                auth()->id()
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'Journée comptable et Agence ouvertes avec succès',
                'data' => [
                    'journee_id' => $jour->id,
                    'session_agence_id' => $session->id,
                    'date_comptable' => $jour->date_du_jour
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Étape 3 : Ouverture du Guichet
     */
    public function ouvrirGuichet(Request $request)
    {
        $request->validate([
            'agence_session_id' => 'required|exists:agence_sessions,id',
            'code_guichet' => 'required|integer',
        ]);

        try {
            $guichet = $this->sessionService->ouvrirGuichetSession(
                $request->agence_session_id,
                $request->code_guichet
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'Guichet ouvert avec succès',
                'guichet_session_id' => $guichet->id
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Étape 4 : Ouverture de la Caisse
     */
    public function ouvrirCaisse(Request $request)
{
    $request->validate([
        'guichet_session_id' => 'required',
        'code_caisse' => 'required',
        'billetage' => 'required|array', 
        'solde_saisi' => 'required|numeric'
    ]);

    // 1. Récupérer le solde de clôture (Solde Informatique)
    $soldeInformatique = (float) $this->sessionService->getDernierSoldeFermeture($request->code_caisse);

    // 2. Calculer le montant total du billetage avec forçage de type
    $montantBillete = 0;
    foreach ($request->billetage as $coupure => $quantite) {
        $montantBillete += ((int)$coupure * (int)$quantite);
    }

    $soldeSaisi = (float) $request->solde_saisi;

    // 3. Vérification de l'Ajustage (La règle d'or)
    // On compare les montants castés en float pour éviter les erreurs de type
    if (abs($montantBillete - $soldeSaisi) > 0.01) {
        return response()->json([
            'message' => 'Le billetage n’est pas correct. Reprendre le billetage.',
            'debug' => [
                'calcul_billetage' => $montantBillete,
                'solde_saisi' => $soldeSaisi
            ]
        ], 422);
    }

    if (abs($soldeSaisi - $soldeInformatique) > 0.01) {
        return response()->json([
            'message' => 'Erreur : Le solde saisi est différent du solde informatique.',
            'debug' => [
                'solde_saisi' => $soldeSaisi,
                'attendu_systeme' => $soldeInformatique
            ]
        ], 422);
    }

    // 4. Si tout est OK, on procède à l'ouverture
    try {
        $caisse = $this->sessionService->ouvrirCaisseSession(
            $request->guichet_session_id,
            auth()->id(),
            $request->code_caisse,        // Argument 3 : CELUI QUI MANQUAIT !
            $soldeSaisi,
            $request->billetage
        );

        return response()->json([
            'statut' => 'success',
            'message' => 'La caisse est ouverte',
            'data' => ['caisse_id' => $caisse->id, 'statut' => 'OU']
        ]);
    } catch (Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}


/**
 * POST /api/sessions-agence/reouvrir-caisse
 */
public function reouvrirCaisse(Request $request)
    {
        $request->validate([
            'caisse_session_id' => 'required|exists:caisse_sessions,id'
        ]);

        try {
            $caisse = $this->sessionService->reouvrirCaisseSession($request->caisse_session_id);

            // Audit Log Spatie
            activity('caisse')
                ->performedOn($caisse)
                ->causedBy(auth()->user())
                ->log("Réouverture de la caisse session ID: {$caisse->id}");

            return response()->json([
                'statut' => 'success',
                'message' => 'La caisse est rouverte (Statut RE)',
                'data' => ['statut' => 'RE']
            ]);

        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur lors de la réouverture : ' . $e->getMessage()
            ], 422);
        }
    }
/**
 * FERMETURE DE LA CAISSE
 */
public function fermerCaisse(Request $request) {
    $request->validate([
        'caisse_session_id' => 'required|exists:caisse_sessions,id',
        'solde_fermeture' => 'required|numeric|min:0'
    ]);

    try {
        $caisse = $this->sessionService->fermerCaisseSession(
            $request->caisse_session_id, 
            $request->solde_fermeture
        );

        // LOG MANUEL (Optionnel si vous avez mis LogsActivity dans le modèle)
        activity('clôture')
            ->performedOn($caisse)
            ->log("Le caissier " . auth()->user()->name . " a fermé sa caisse avec " . $request->solde_fermeture);

        return response()->json(['message' => 'Caisse fermée avec succès']);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}

/**
 * FERMETURE DU GUICHET
 * Accessible uniquement par le Chef d'Agence ou l'Admin
 */
public function fermerGuichet(Request $request)
{
    $request->validate([
        'guichet_session_id' => 'required|exists:guichet_sessions,id',
    ]);

    try {
       

        $guichet = $this->sessionService->fermerGuichetSession($request->guichet_session_id);

        // Log d'audit avec Spatie Log
        activity('session_guichet')
            ->performedOn($guichet)
            ->causedBy(auth()->user())
            ->log("Fermeture du guichet code: " . $guichet->code_guichet);

        return response()->json([
            'statut' => 'success',
            'message' => 'Guichet fermé avec succès.'
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'statut' => 'error',
            'message' => $e->getMessage()
        ], 422);
    }
}

/**
 * TRAITEMENT DE FIN DE JOURNÉE (TFJ)
 */
public function fermerAgence(Request $request) {
    $request->validate([
        'agence_session_id' => 'required|exists:agence_sessions,id',
        'jour_comptable_id' => 'required|exists:jours_comptables,id'
    ]);

    try {
        $this->sessionService->fermerAgenceEtJournee(
            $request->agence_session_id, 
            $request->jour_comptable_id
        );

        return response()->json(['message' => 'Traitement de fin de journée terminé. Agence clôturée.']);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
}