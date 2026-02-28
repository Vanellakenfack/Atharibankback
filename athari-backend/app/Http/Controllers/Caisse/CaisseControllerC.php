<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Caisse\Caisse;
use App\Models\Caisse\Guichet;
use App\Models\SessionAgence\CaisseSession;
use App\Models\SessionAgence\GuichetSession;
use Illuminate\Support\Facades\Log; 


class CaisseControllerC extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $caisses = Caisse::all();

        // Retourne du JSON pour Postman
        return response()->json([
            'success' => true,
            'data' => $caisses
        ], 200);
    }

    public function create()
    {
        $guichets = Guichet::where('est_actif', true)->get();
        return view('caisse.caisses.create', compact('guichets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guichet_id' => 'required|exists:guichets,id',
            'code_caisse' => 'required|unique:caisses,code_caisse',
            'libelle' => 'required|string|max:255',
            'solde_actuel' => 'numeric',
            'plafond_max' => 'nullable|numeric',
        ]);

        Caisse::create($validated);

        return redirect()->route('caisse.caisses.index')->with('success', 'Caisse créée avec succès.');
    }


    /**
     * Récupère les caisses disponibles pour une session guichet
     * GET /api/sessions/caisses/disponibles/{guichetSessionId}
     */
    public function getCaissesDisponiblesParGuichet ($guichetSessionId)
    {
        try {
            // 1. Vérifier la session guichet
            $guichetSession = GuichetSession::where('id', $guichetSessionId)
                ->where('statut', 'OU')
                ->firstOrFail();
            
            // 2. Récupérer les caisses du guichet (relation directe)
            // CORRECTION: utiliser 'est_active' au lieu de 'est_actif'
            $caisses = Caisse::where('guichet_id', $guichetSession->guichet_id)
                ->where('est_active', true) // CORRECTION ICI
                ->get(['id', 'code_caisse', 'libelle', 'solde_actuel', 'plafond_max']);
            
            // 3. Formater la réponse
            $formattedCaisses = $caisses->map(function($caisse) {
                return [
                    'id' => $caisse->id,
                    'code_caisse' => $caisse->code_caisse,
                    'libelle' => $caisse->libelle,
                    'solde_actuel' => (float) $caisse->solde_actuel,
                    'plafond_max' => (float) $caisse->plafond_max,
                    'pourcentage_utilisation' => $caisse->plafond_max > 0 
                        ? round(($caisse->solde_actuel / $caisse->plafond_max) * 100, 2)
                        : 0
                ];
            });
            
            return response()->json([
                'statut' => 'success',
                'message' => 'Caisses disponibles pour cette session guichet',
                'session_guichet_id' => $guichetSessionId,
                'guichet_id' => $guichetSession->guichet_id,
                'caisses' => $formattedCaisses,
                'total' => $caisses->count()
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Session guichet non trouvée ou fermée'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Erreur: ' . $e->getMessage());
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur serveur: ' . $e->getMessage() // Ajout du message d'erreur pour debug
            ], 500);
        }
    }
}