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
        return CaisseDemandeValidation::where('statut', 'EN_ATTENTE')
            ->with(['caissiere:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Approbation de la demande et génération du code
     */
    public function approuver(Request $request, $id)
    {
        $demande = CaisseDemandeValidation::findOrFail($id);

        if ($demande->statut !== 'EN_ATTENTE') {
            return response()->json(['message' => 'Cette demande a déjà été traitée.'], 400);
        }

        // Génération du code via la méthode du modèle
        $code = $demande->genererCodeValidation();
        
        $demande->update([
            'statut' => 'APPROUVE',
            'assistant_id' => auth()->id(),
            'date_approbation' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Opération approuvée. Veuillez communiquer ce code à la caissière.',
            'code' => $code // L'assistant voit ce code et le donne à la caissière
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
}