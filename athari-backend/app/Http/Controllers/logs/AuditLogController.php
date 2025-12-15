<?php

namespace App\Http\Controllers\logs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity; 
use Illuminate\Pagination\LengthAwarePaginator; // Pour les annotations

class AuditLogController extends Controller
{
    /**
     * Affiche une liste paginée des logs d'activité.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 1. Définir la requête de base
        $query = Activity::query()
            // Trier par le plus récent en premier
            ->latest();

        // 2. FILTRAGE PAR UTILISATEUR (Optionnel)
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->input('user_id'));
        }

        // 3. FILTRAGE PAR TYPE D'ACTIVITÉ (log_name - Optionnel)
        // Par exemple, 'auth.login', 'auth.logout', 'default'
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }

        // 4. FILTRAGE PAR RECHERCHE DE TEXTE
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('description', 'like', $searchTerm)
                  ->orWhere('log_name', 'like', $searchTerm);
            });
        }
        
        // 5. PAGINATION
        // Récupère les résultats avec pagination (par défaut 15 par page)
        $perPage = $request->input('per_page', 15);
        $logs = $query->paginate($perPage);

        // 6. Transformation et réponse
        // Nous incluons les relations 'causer' (l'utilisateur qui a fait l'action)
        // et 'subject' (l'objet sur lequel l'action a été faite)
        
        return response()->json([
            'data' => $logs->load('causer', 'subject'),
            'pagination' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }
}