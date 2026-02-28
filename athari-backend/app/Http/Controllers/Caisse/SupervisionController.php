<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CaisseDemandeValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupervisionController extends Controller
{
    /**
     * Liste des demandes en attente pour l'assistant
     */
   public function index()
{
    $user = auth()->user();
    
    // On ne récupère que les demandes qui correspondent au rôle de l'utilisateur connecté
    return CaisseDemandeValidation::where('statut', 'EN_ATTENTE')
        ->where(function($query) use ($user) {
            // Si l'utilisateur est Chef d'Agence, il voit les demandes 'Chef d'Agence'
            // Si c'est le DG, il voit les demandes 'Directeur Général', etc.
            $query->whereIn('role_destination', $user->getRoleNames()); 
        })
        ->with(['caissiere:id,name'])
        ->orderBy('created_at', 'desc')
        ->get();
}

public function approuver(Request $request, $id)
{
    $demande = CaisseDemandeValidation::findOrFail($id);

    if ($demande->statut !== 'EN_ATTENTE') {
        return response()->json(['message' => 'Cette demande a déjà été traitée.'], 400);
    }

    // Sécurité supplémentaire : Vérifier si le validateur a le droit de valider ce niveau
    if (!auth()->user()->hasRole($demande->role_destination)) {
        return response()->json(['message' => 'Vous n\'avez pas les droits pour valider ce palier.'], 403);
    }

    $code = $demande->genererCodeValidation();
    
    $demande->update([
        'statut' => 'APPROUVE',
        'assistant_id' => auth()->id(), // Idéalement, renommez ce champ 'validateur_id'
        'date_approbation' => now(),
        'code_validation' => $code
    ]);

    return response()->json([
        'success' => true,
        'message' => "Opération approuvée par le " . $demande->role_destination,
        'code' => $code 
    ]);
}

    /**
     * Rejet de la demande
     */
    public function rejeter(Request $request, $id)
    {
        $request->validate(['motif' => 'required|string']);

        $demande = CaisseDemandeValidation::findOrFail($id);
        $demande->update([
            'statut' => 'REJETE',
            'assistant_id' => auth()->id(),
            'motif_rejet' => $request->motif
        ]);

        return response()->json(['success' => true, 'message' => 'Opération rejetée.']);
    }
    
    /**
     * Récupérer uniquement le code_validation et date_approbation des demandes approuvées
     */
    public function codesApprobation()
    {
        return CaisseDemandeValidation::where('statut', 'APPROUVE')
            ->whereDate('date_approbation', now()->toDateString()) // Utiliser la date en string
            ->select('id', 'code_validation', 'date_approbation')
            ->orderBy('date_approbation', 'desc')
            ->get();
    }

    public function storeDemandeValidation(Request $request)
{
    try {
        // Validation adaptée à vos données avec les bons noms de tables
        $validated = $request->validate([
            'compte_id' => 'required|exists:comptes,id',
            'montant' => 'required|numeric|min:0',
            'motif' => 'nullable|string',
            'caisse_id' => 'required|exists:caisses,id',
            'agence_id' => 'required|exists:agencies,id', // Changé de agences à agencies
            'guichet_id' => 'required|exists:guichets,id',
            'role_destination' => 'required|string',
        ]);

        // Construire le payload_data à partir des données reçues
        $payload_data = [
            'compte_id' => $request->compte_id,
            'montant_brut' => $request->montant,
            'motif' => $request->motif,
            'caisse_id' => $request->caisse_id,
            'agence_id' => $request->agence_id,
            'guichet_id' => $request->guichet_id,
            'type_operation' => 'RETRAIT',
            'date_demande' => now()->toDateTimeString(),
        ];

        // Créer la demande de validation
        $demande = \App\Models\Caisse\CaisseDemandeValidation::create([
            'type_operation' => 'RETRAIT',
            'payload_data' => json_encode($payload_data),
            'montant' => $validated['montant'],
            'role_destination' => $validated['role_destination'],
            'caissiere_id' => auth()->id() ?? 7, // Utiliser l'utilisateur connecté ou valeur par défaut
            'role_destination' => $request->role_destination ,
            'statut' => 'EN_ATTENTE',
        ]);

        // Générer un code de validation unique
        $demande->genererCodeValidation();
        $demande->save();

        return response()->json([
            'success' => true,
            'message' => 'Demande de validation créée avec succès',
            'data' => [
                'id' => $demande->id,
                'code_validation' => $demande->code_validation,
                'statut' => $demande->statut,
                'type_operation' => $demande->type_operation,
                'montant' => $demande->montant,
                'created_at' => $demande->created_at,
                'role_destination' => $demande->role_destination, // Pour vérifier dans la réponse
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Erreur création demande validation: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erreur serveur: ' . $e->getMessage()
        ], 500);
    }
}

}