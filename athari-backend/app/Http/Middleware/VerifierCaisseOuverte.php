<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SessionAgence\CaisseSession;
use Illuminate\Support\Facades\Auth;

class VerifierCaisseOuverte
{
    /**
     * Gère la vérification de l'état de la caisse avant une transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Récupérer l'utilisateur connecté
        $utilisateur = Auth::user();

        // 2. Chercher si cet utilisateur a une session de caisse OUVERTE
        $sessionCaisse = CaisseSession::where('caissier_id', $utilisateur->id)
            ->where('statut', 'OU')
            ->first();

        // 3. Si aucune session ouverte n'est trouvée
        if (!$sessionCaisse) {
            return response()->json([
                'statut' => 'erreur_securite',
                'code' => 'CAISSE_FERMEE',
                'message' => 'Action refusée : Votre session de caisse est fermée ou inexistante.',
                'instruction' => 'Veuillez procéder à l\'ouverture de votre caisse via le menu "Gestion des Sessions" avant de tenter une opération.'
            ], 403); // 403 Forbidden
        }

        /** * 4. OPTIMAL : Injecter la session dans la requête.
         * Cela permet d'accéder à $request->caisse_active dans vos contrôleurs
         * sans refaire une requête SQL.
         */
        $request->merge(['caisse_active' => $sessionCaisse]);

        return $next($request);
    }
}