<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CreditApplication;
use App\Models\Avis;

class AARController extends Controller
{
    /**
     * 📌 Liste des demandes visibles par l’AAR
     */
    public function index()
    {
        $applications = CreditApplication::with('avis.user')
            ->where('statut', 'SOUMIS')
            ->get();

        return response()->json($applications);
    }

    /**
     * 📌 L’AAR donne son avis
     * ➜ le dossier est envoyé au chef d’agence
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'opinion' => 'required|in:approuve,rejete,en_attente',
            'commentaire' => 'nullable|string',
        ]);

        $application = CreditApplication::findOrFail($id);

        // 1️⃣ Enregistrer l’avis
        $avis = Avis::create([
            'credit_application_id' => $application->id,
            'user_id' => auth()->id(),
            'opinion' => $request->opinion,
            'commentaire' => $request->commentaire,
        ]);

        // 2️⃣ Envoyer le dossier au chef d’agence
        $application->update([
            'statut' => 'EN_ATTENTE_CHEF_AGENCE'
        ]);

        return response()->json([
            'message' => 'Avis AAR enregistré. Dossier transmis au chef d’agence.',
            'avis' => $avis,
        ]);
    }

    /**
     * 📌 Dossiers visibles par le chef d’agence
     */
    public function applicationsForChief()
    {
        $applications = CreditApplication::with('avis.user')
            ->where('statut', 'EN_ATTENTE_CHEF_AGENCE')
            ->get();

        return response()->json($applications);
    }
}
