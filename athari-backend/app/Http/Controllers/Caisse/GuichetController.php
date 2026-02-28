<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Agency; // Assurez-vous que le modèle s'appelle Agence (table 'agencies')
use Illuminate\Http\Request;
use App\Models\Caisse\Guichet;
use App\Models\SessionAgence\AgenceSession;


class GuichetController extends Controller
{
    /**
     * Affiche la liste des guichets avec leurs agences respectives.
     */
  public function index()
{
    $guichets = Guichet::all();
    
    // Retourne du JSON au lieu d'une vue Blade
    return response()->json([
        'success' => true,
        'data' => $guichets
    ]);
}

    /**
     * Formulaire de création d'un guichet.
     */
    public function create()
    {
        // Nécessaire pour remplir le menu déroulant des agences
        $agences = Agency::all();
        return view('caisse.guichets.create', compact('agences'));
    }

    /**
     * Enregistre un nouveau guichet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'agence_id'    => 'required|exists:agencies,id', // Référence à la table agencies
            'code_guichet' => 'required|string|unique:guichets,code_guichet', // Doit être unique
            'nom_guichet'  => 'required|string|max:255',
            'est_actif'    => 'boolean'
        ]);

        Guichet::create($validated);

        return redirect()->route('caisse.guichets.index')
            ->with('success', 'Le guichet a été créé avec succès.');
    }

    /**
     * Affiche les détails d'un guichet et ses caisses liées.
     */
    public function show(Guichet $guichet)
    {
        // On charge les caisses liées au guichet
        $guichet->load('caisses');
        return view('caisse.guichets.show', compact('guichet'));
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Guichet $guichet)
    {
        $agences = Agency::all();
        return view('caisse.guichets.edit', compact('guichet', 'agences'));
    }

    /**
     * Met à jour le guichet.
     */
    public function update(Request $request, Guichet $guichet)
    {
        $validated = $request->validate([
            'agence_id'    => 'required|exists:agencies,id',
            'code_guichet' => 'required|string|unique:guichets,code_guichet,' . $guichet->id,
            'nom_guichet'  => 'required|string|max:255',
            'est_actif'    => 'boolean'
        ]);

        $guichet->update($validated);

        return redirect()->route('caisse.guichets.index')
            ->with('success', 'Guichet mis à jour avec succès.');
    }

    /**
     * Supprime un guichet. 
     * Note: La migration prévoit un onDelete('cascade').
     */
    public function destroy(Guichet $guichet)
    {
        $guichet->delete();
        return redirect()->route('caisse.guichets.index')
            ->with('success', 'Guichet supprimé.');
    }


    /**
     * Alternative: Récupérer les guichets par ID d'agence
     * GET /api/guichets/agence/{agenceId}
     */
    public function getGuichetsDisponibles($agenceSessionId)
    {
        try {
            // 1. Récupérer la session agence
            $sessionAgence = AgenceSession::where('id', $agenceSessionId)
                ->where('statut' , 'OU') // seulement les sessions non fermées
                ->first();
            
            if (!$sessionAgence) {
                return response()->json([
                    'statut' => 'error',
                    'message' => 'Session agence non trouvée ou déjà fermée'
                ], 404);
            }
            
            // 2. Récupérer l'agence_id
            $agenceId = $sessionAgence->agence_id;
            
            // 3. Récupérer les guichets de CETTE agence seulement
            $guichets = Guichet::where('agence_id', $agenceId)
                ->where('est_actif', true)
                ->get();
            
            return response()->json([
                'statut' => 'success',
                'message' => 'Guichets disponibles récupérés',
                'data' => $guichets,
                'agence' => [
                    'id' => $agenceId,
                    'session_id' => $sessionAgence->id
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'statut' => 'error',
                'message' => 'Erreur lors de la récupération des guichets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
}