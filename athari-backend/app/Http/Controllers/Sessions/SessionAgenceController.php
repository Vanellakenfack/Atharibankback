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
use App\Models\Agency;
use App\Models\Caisse\Guichet;
use App\Models\Caisse\Caisse;
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
    if (!auth()->check()) {
        return response()->json(['statut' => 'error', 'message' => 'Session expirée.'], 401);
    }

    $request->validate([
        'agence_id' => 'required|exists:agencies,id',
        'date_comptable' => 'required|date',
    ]);

    $dateSaisie = \Carbon\Carbon::parse($request->date_comptable)->startOfDay();
    $aujourdhui = \Carbon\Carbon::today();
    $user = auth()->user();

    $estAutoriseRattrapage = $user->hasRole("DG") /*|| $user->hasRole('Chef Comptable')*/;

    if ($dateSaisie->lt($aujourdhui) && !$estAutoriseRattrapage) {
        return response()->json([
            'statut' => 'error',
            'message' => "Accès refusé : Seul le dg  peut ouvrir une journée en rattrapage."
        ], 403);
    }

    try {
        // ... (Vérification session existante inchangée)

        // CORRECTION ICI : On ajoute $dateSaisie dans le 'use' de la transaction
        return DB::transaction(function () use ($request, $estAutoriseRattrapage, $dateSaisie) {
            
            $jour = $this->sessionService->ouvrirJourneeComptable(
                $request->agence_id,
                $request->date_comptable, // On passe la string brute, le service fera le reste
                auth()->id(),
                $estAutoriseRattrapage
            );

            $session = $this->sessionService->ouvrirAgenceSession(
                $request->agence_id,
                $jour->id,
                auth()->id()
            );

            return response()->json([
                'statut' => 'success',
                // Utilisation de $dateSaisie ici nécessite qu'elle soit dans le 'use'
                'message' => 'Journée ' . ($estAutoriseRattrapage && $dateSaisie->lt(\Carbon\Carbon::today()) ? 'de rattrapage ' : '') . 'ouverte avec succès',
                'data' => [
                    'jours_comptable_id' => $jour->id,
                    'agence_session_id' => $session->id,
                    'date_comptable' => $jour->date_du_jour
                ]
            ], 201);
        });
    } catch (Exception $e) {
        return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 422);
    }
}
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
/**
     * TRAITEMENT DE FIN DE JOURNÉE (TFJ)
     * Cette méthode fait le lien entre le Frontend et le Service
     */
   public function executerTraitementFinJournee(Request $request) 
{
    try {
        $agenceSessionId = $request->input('agence_session_id');
        $session = AgenceSession::with('agence')->findOrFail($agenceSessionId);

        $jourComptableId = $request->input('jours_comptable_id');
        if (!$jourComptableId) {
            $jourActif = \App\Models\SessionAgence\JourComptable::where('agence_id', $session->agence_id)
                ->where('statut', 'OUVERT')
                ->first();
            
            if (!$jourActif) throw new Exception("Aucune journée comptable ouverte.");
            $jourComptableId = $jourActif->id;
        }

        // 1. Calcul et enregistrement des agrégats (La fonction qu'on a modifiée ensemble)
        $this->sessionService->traiterBilanFinJournee($agenceSessionId, $jourComptableId);

        // 2. Récupération des données fraîchement calculées pour la vue
        $bilan = DB::table('bilan_journalier_agences')
                    ->where('jours_comptable_id', $jourComptableId)
                    ->first();

        // On formate les données des caisses pour la vue
        $caisses = json_decode($bilan->resume_caisses, true);

        // Si vous utilisez DomPDF ou Snappy pour le PDF :
        /*
        $pdf = PDF::loadView('votre_vue_bilan', [
            'bilan'    => $bilan,
            'agence'   => $session->agence->nom,
            'caisses'  => $caisses,
            'edite_le' => now()->format('d/m/Y H:i')
        ]);
        return $pdf->download('Brouillard_Caisse_'.$bilan->date_comptable.'.pdf');
        */

        return response()->json([
            'statut' => 'success',
            'message' => 'Bilan consolidé généré avec succès.',
            'data' => $bilan
        ]);

    } catch (Exception $e) {
        return response()->json([
            'statut' => 'error',
            'message' => 'Erreur TFJ : ' . $e->getMessage()
        ], 422);
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
                ->where('jours_comptable_id', $jourId)
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




/**
 * GET /api/sessions/agence/active
 * Récupère UNIQUEMENT la session AGENCE (peu importe son statut)
 */
public function getAgenceActive()
{
    try {
        $session = AgenceSession::latest()->first();
        
        if (!$session) {
            return response()->json([
                'statut' => 'success',
                'session' => null
            ]);
        }
        
        $agence = Agency::find($session->agence_id);
        
        return response()->json([
            'statut' => 'success',
            'session' => [
                'id' => $session->id,
                'agence_id' => $session->agence_id,
                'nom_agence' => $agence->nom ?? 'Agence Principale',
                'date_comptable' => $session->date_comptable,
                'jour_comptable_id' => $session->jours_comptable_id, // <--- AJOUTER CETTE LIGNE
                'statut' => $session->statut
            ]
        ]);
    } catch (Exception $e) {
        return response()->json(['statut' => 'error', 'message' => $e->getMessage()], 500);
    }
}



/**
     * Récupère la session de guichet active
     * GET /api/sessions/guichet/active
     */
    public function getGuichetActive()
    {
        try {
            // 1. On récupère la dernière session d'agence
            $sessionAgence = AgenceSession::latest()->first();
            
            if (!$sessionAgence) {
                return response()->json([
                    'statut' => 'success', 
                    'session' => null, 
                    'message' => 'Aucune session agence trouvée'
                ]);
            }
            
            // 2. On cherche la session guichet liée
            $session = GuichetSession::where('agence_session_id', $sessionAgence->id)
                ->latest()
                ->first();
            
            if (!$session) {
                return response()->json([
                    'statut' => 'success', 
                    'session' => null, 
                    'message' => 'Aucune session guichet trouvée'
                ]);
            }
            
            // 3. Récupération des infos du guichet
            $guichet = Guichet::find($session->guichet_id);
            
            return response()->json([
                'statut' => 'success',
                'session' => [
                    'id' => $session->id,
                    'guichet_id' => $session->guichet_id,
                    'agence_session_id' => $session->agence_session_id,
                    'nom' => $guichet->nom_guichet ?? null,
                    'code' => $guichet->code_guichet ?? null,
                    'statut' => $session->statut,
                    'created_at' => $session->created_at
                ],
                'message' => 'Session guichet récupérée'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'statut' => 'error', 
                'message' => 'Erreur lors de la récupération du guichet: ' . $e->getMessage()
            ], 500);
        }
    }/**
 * GET /api/sessions/caisse/active
 * Récupère UNIQUEMENT la session CAISSE (peu importe son statut)
 */
public function getCaisseActive()
{
    try {
        // 1. Récupérer la toute dernière session agence (Idéalement ajouter ->where('statut', 'OU'))
        $sessionAgence = AgenceSession::latest()->first();
        
        if (!$sessionAgence) {
            return response()->json([
                'statut' => 'success',
                'session' => null,
                'message' => 'Aucune session agence trouvée'
            ]);
        }
        
        // 2. CORRECTION : On cherche la session guichet liée à l'ID de la session agence
        // Tu utilisais $sessionAgence->agence_session_id qui n'existe probablement pas sur ce modèle
        $sessionGuichet = GuichetSession::where('agence_session_id', $sessionAgence->id)
            ->latest()
            ->first();
        
        if (!$sessionGuichet) {
            return response()->json([
                'statut' => 'success',
                'session' => null,
                'message' => 'Aucune session guichet trouvée'
            ]);
        }
        
        // 3. CORRECTION : On cherche la session caisse liée à l'ID de la SESSION guichet
        // Note : On utilise $sessionGuichet->id (la clé primaire de la table guichet_sessions)
        $session = CaisseSession::where('guichet_session_id', $sessionGuichet->id)
            ->with(['caisse']) // Eager loading pour éviter de refaire un find() plus bas
            ->latest()
            ->first();
        
        if (!$session) {
            return response()->json([
                'statut' => 'success',
                'session' => null,
                'message' => 'Aucune session caisse trouvée'
            ]);
        }
        
        return response()->json([
            'statut' => 'success',
            'session' => [
                'id' => $session->id,
                'caisse_id' => $session->caisse_id,
                'guichet_session_id' => $session->guichet_session_id,
                'caissier_id' => $session->caissier_id,
                'libelle' => $session->caisse->libelle ?? null,
                'code' => $session->caisse->code_caisse ?? null,
                'solde_ouverture' => $session->solde_ouverture,
                'solde_actuel' => $session->caisse->solde_actuel ?? 0,
                'statut' => $session->statut,
                'created_at' => $session->created_at
            ],
            'message' => 'Session caisse récupérée'
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'statut' => 'error',
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}


}