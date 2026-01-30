<?php

namespace App\Http\Controllers\Sessions;

use App\Http\Controllers\Controller;
use App\Services\SessionBancaireService;
use Illuminate\Http\Request;
use App\Models\SessionAgence\AgenceSession; // Vérifiez votre chemin de modèle
use App\Models\SessionAgence\GuichetSession; // Vérifiez votre chemin de modèle
use App\Models\SessionAgence\BilanJournalierAgence;
use Illuminate\Support\Facades\DB;
use App\Models\SessionAgence\CaisseSession;
use Exception;

class SessionAgenceController extends Controller
{
    protected $sessionService;

    public function __construct(SessionBancaireService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * ÉTAPE 1 & 2 : OUVERTURE DE LA JOURNÉE ET DE L'AGENCE
     * POST /api/sessions/ouvrir-agence
     */
    public function ouvrirAgence(Request $request)
    {
        $request->validate([
            'agence_id' => 'required|exists:agencies,id',
            'date_comptable' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Ouvrir le jour comptable
                $jour = $this->sessionService->ouvrirJourneeComptable(
                    $request->agence_id,
                    $request->date_comptable,
                    auth()->id()
                );

                // 2. Ouvrir la session agence liée
                $session = $this->sessionService->ouvrirAgenceSession(
                    $request->agence_id,
                    $jour->id,
                    auth()->id()
                );

                return response()->json([
                    'statut' => 'success',
                    'message' => 'Journée comptable et Agence ouvertes avec succès',
                    'data' => [
                        'jour_comptable_id' => $jour->id,
                        'agence_session_id' => $session->id,
                        'date_comptable' => $jour->date_du_jour
                    ]
                ], 201);
            });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * ÉTAPE 3 : OUVERTURE DU GUICHET
     */
    public function ouvrirGuichet(Request $request)
    {
        $request->validate([
            'agence_session_id' => 'required|exists:agence_sessions,id',
            'guichet_id' => 'required|exists:guichets,id',
        ]);

        try {
            $guichet = $this->sessionService->ouvrirGuichetSession(
                $request->agence_session_id,
                $request->guichet_id
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'Guichet ouvert avec succès',
                'data' => [
                    'guichet_session_id' => $guichet->id,
                    'code_guichet' => $guichet->code_guichet
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * ÉTAPE 4 : OUVERTURE DE LA CAISSE
     */
    public function ouvrirCaisse(Request $request)
    {
        $request->validate([
            'guichet_session_id' => 'required|exists:guichet_sessions,id',
            'caisse_id'          => 'required|exists:caisses,id',
            'billetage'          => 'required|array', 
            'solde_ouverture'        => 'required|numeric'
        ]);

        try {
            // Le service gère l'ajustage (Saisi vs Billetage vs Informatique)
            $caisse = $this->sessionService->ouvrirCaisseSession(
                $request->guichet_session_id,
                auth()->id(),
                $request->caisse_id,
                (float)$request->solde_ouverture,
                $request->billetage
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'La caisse est ajustée et ouverte avec succès.',
                'data' => [
                    'caisse_session_id' => $caisse->id,
                    'code_caisse' => $caisse->code_caisse,
                    'statut' => 'OU'
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * FERMETURE DE LA CAISSE
     * POST /api/sessions/fermer-caisse
     */
    public function fermerCaisse(Request $request) {
        $request->validate([
            'caisse_session_id' => 'required|exists:caisse_sessions,id',
            'solde_fermeture'   => 'required|numeric|min:0',
            'billetage'         => 'required|array'
        ]);

        try {
            $session = $this->sessionService->fermerCaisseSession(
                $request->caisse_session_id, 
                (float)$request->solde_fermeture,
                $request->billetage
            );

            activity('caisse')
                ->performedOn($session)
                ->log("Clôture de caisse effectuée par le caissier ID: " . auth()->id());

            return response()->json([
                'statut' => 'success',
                'message' => 'Caisse fermée avec succès et solde de coffre mis à jour.'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
  /**
     * POST /api/sessions/fermer-guichet
     */
    public function fermerGuichet(Request $request)
    {
        $request->validate([
            'guichet_session_id' => 'required|exists:guichet_sessions,id',
        ]);

        try {
            $guichetSession = $this->sessionService->fermerGuichetSession(
                $request->guichet_session_id
            );

            activity('session')
                ->performedOn($guichetSession)
                ->log("Clôture du guichet effectuée par l'utilisateur ID: " . auth()->id());

            return response()->json([
                'statut' => 'success',
                'message' => 'Le guichet a été clôturé avec succès.',
                'data' => [
                    'guichet_session_id' => $guichetSession->id,
                    'statut' => 'FE' // Statut Fermé
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
    /**
     * BILAN DE CLÔTURE (Visualisation avant TFJ)
     * GET /api/sessions/bilan-caisse/{id}
     */
    public function getBilanCaisse($id)
    {
        try {
            $bilan = $this->sessionService->genererBilanCaisse($id);
            return response()->json(['statut' => 'success', 'data' => $bilan]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
public function executerTraitementFinJournee(Request $request) 
    {
        $request->validate([
            'agence_session_id' => 'required|exists:agence_sessions,id',
            'jour_comptable_id' => 'required|exists:jours_comptables,id'
        ]);

        try {
            $this->sessionService->traiterBilanFinJournee(
                $request->agence_session_id, 
                $request->jour_comptable_id
            );

            return response()->json([
                'statut' => 'success',
                'message' => 'Traitement des bilans (TFJ) effectué avec succès. Vous pouvez maintenant consulter le bilan global avant clôture.'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getEtatAgence($agenceSessionId)
    {
        try {
            // 1. Compter les guichets encore ouverts pour cette session d'agence
            // On suppose que guichet_sessions a une colonne agence_session_id et un statut
            $guichetsOuverts = GuichetSession::where('agence_session_id', $agenceSessionId)
                ->where('statut', 'OUVERT') // ou selon votre logique de statut
                ->count();

            // 2. Récupérer les infos de la session
            $session = AgenceSession::findOrFail($agenceSessionId);

            return response()->json([
                'statut' => 'success',
                'guichets_ouverts' => $guichetsOuverts,
                'statut_agence' => $session->statut, // ex: 'OUVERT'
                'date_comptable' => $session->date_comptable,
                'can_close' => $guichetsOuverts === 0
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur lors de la récupération de l\'état : ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * TRAITEMENT DE FIN DE JOURNÉE (TFJ)
     * POST /api/sessions/fermer-agence
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

            return response()->json([
                'statut' => 'success',
                'message' => 'Traitement de fin de journée terminé. Agence et Journée clôturées.'
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**

     */
    public function reouvrirCaisse(Request $request)
    {
        $request->validate([
            'caisse_session_id' => 'required|exists:caisse_sessions,id'
        ]);

        try {
            $caisse = $this->sessionService->reouvrirCaisseSession($request->caisse_session_id);

            activity('caisse')
                ->performedOn($caisse)
                ->log("Réouverture exceptionnelle de la session caisse ID: {$caisse->id}");

            return response()->json([
                'statut' => 'success',
                'message' => 'La caisse est à nouveau disponible (Statut RE)',
                'data' => ['statut' => 'RE']
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // App\Http\Controllers\Sessions\SessionAgenceController.php

public function imprimerBrouillard($jourId)
{
    $bilan = BilanJournalierAgence::with('jourComptable')
                ->where('jour_comptable_id', $jourId)
                ->firstOrFail();

    // On prépare les données pour la vue PDF
    $data = [
        'bilan' => $bilan,
        'agence' => 'Agence Centrale Athari',
        'edite_le' => now()->format('d/m/Y H:i'),
        'caisses' => $bilan->resume_caisses // Le JSON casté en array
    ];

    $pdf = \PDF::loadView('reports.brouillard_agence', $data);
    
    return $pdf->download("Brouillard_{$bilan->date_comptable->format('d_m_Y')}.pdf");
}

/**
 * Récupérer la session active du caissier connecté
 * GET /api/caisse/session-active
 */
public function getSessionActive()
{
    try {
        $session = CaisseSession::where('caissier_id', auth()->id())
            ->whereIn('statut', ['OU', 'RE']) // Ouvert ou Réouvert
            ->with(['caisse']) // Charger les infos de la caisse si besoin
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune session active trouvée.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $session
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
}