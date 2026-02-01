<?php
use App\Models\Avis;
use App\Models\CreditApplication;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    public function donnerAvisAAR(Request $request, $id)
    {
        $request->validate([
            'opinion' => 'required|in:approuve,rejete,en_attente',
            'commentaire' => 'nullable|string'
        ]);

        $credit = CreditApplication::findOrFail($id);

        // 1️⃣ Enregistrer l’avis AAR
        Avis::create([
            'credit_application_id' => $credit->id,
            'user_id' => auth()->id(),
            'role' => 'AAR',
            'opinion' => $request->opinion,
            'commentaire' => $request->commentaire,
        ]);

        // 2️⃣ Envoyer le dossier au chef d’agence
        $credit->update([
            'statut' => 'EN_ATTENTE_CHEF_AGENCE'
        ]);

        return response()->json([
            'message' => 'Avis AAR enregistré et dossier transmis au chef d’agence'
        ]);
    }
}
