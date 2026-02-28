<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\ComptabiliteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckAgenceOuverte
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Récupérer l'utilisateur authentifié
        $user = auth()->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2. Récupérer les agences assignées à cet utilisateur
        $userAgenciesIds = $user->agencies()->pluck('agencies.id')->map(function ($v) { 
            return (int) $v; 
        })->toArray();
        
        $isMultiAgency = count($userAgenciesIds) > 1;
        $isPowerUser = $user->hasAnyRole(['DG', 'Admin', 'Superviseur']);

        // 3. Récupérer l'agence demandée (Support de agency_id ET agence_id pour la flexibilité)
        $requestedAgencyId = $request->input('agency_id') 
                          ?? $request->input('agence_id')
                          ?? $request->route('agency_id')
                          ?? $request->route('agence_id')
                          ?? $request->query('agency_id')
                          ?? $request->json('agency_id')
                          ?? $request->get('agency_id');

        // Normaliser en int si fourni
        if ($requestedAgencyId !== null && is_numeric($requestedAgencyId)) {
            $requestedAgencyId = (int) $requestedAgencyId;
        }

        // 4. Si pas trouvé direct, essayer de déduire via client_id
        if (! $requestedAgencyId && ($clientId = $request->input('client_id') ?? $request->route('client_id') ?? $request->json('client_id'))) {
            try {
                $client = \App\Models\client\Client::findOrFail($clientId);
                $requestedAgencyId = (int) $client->agence_id;
            } catch (\Exception $e) {
                Log::warning('Client lookup failed in CheckAgenceOuverte', ['client_id' => $clientId, 'error' => $e->getMessage()]);
            }
        }

        // 5. Déterminer l'agence finale à utiliser
        $agenceId = null;

        // Power users (doivent fournir un ID)
       /* if ($isPowerUser) {
            if ($requestedAgencyId) {
                $agenceId = $requestedAgencyId;
            } else {
                return response()->json([
                    'error' => 'Agency ID required',
                    'message' => 'Power users must specify agency_id or provide client_id to deduce agency',
                    'hint' => 'Send agency_id in request body or query parameter'
                ], 400);
            }
        }*/
        // Utilisateurs multi-agences
        if ($isMultiAgency) {
            if ($requestedAgencyId) {
                if (! in_array($requestedAgencyId, $userAgenciesIds)) {
                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'You do not have access to this agency',
                        'requested_agency_id' => $requestedAgencyId,
                        'allowed_agencies' => $userAgenciesIds
                    ], 403);
                }
                $agenceId = $requestedAgencyId;
            } else {
                $primary = $user->getPrimaryAgency();
                $agenceId = $primary?->id ?? ($userAgenciesIds[0] ?? null);
            }
        }
        // Utilisateur mono-agence
        else {
            $userAgencyId = $userAgenciesIds[0] ?? null;
            if ($requestedAgencyId !== null && $requestedAgencyId !== $userAgencyId) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You do not have access to this agency',
                    'your_agency_id' => $userAgencyId,
                    'requested_agency_id' => $requestedAgencyId
                ], 403);
            }
            $agenceId = $userAgencyId;
        }

        if (! $agenceId) {
            return response()->json([
                'error' => 'No agency found',
                'message' => 'Could not determine or access the requested agency'
            ], 403);
        }

        // 6. Vérification de la session comptable et injection des données
        try {
            $session = ComptabiliteService::getSessionActive($agenceId);

            // Double merge pour supporter agency_id (Front) et agence_id (Back/DB)
            $request->merge([
                'active_session' => $session, 
                'agence_id' => $agenceId,
                'agency_id' => $agenceId
            ]);

            $request->attributes->set('date_comptable', $session->jourComptable->date_du_jour);

            return $next($request);

        } catch (\Exception $e) {
            Log::warning('Agence session not active or other error in CheckAgenceOuverte', [
                'agence_id' => $agenceId, 
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}