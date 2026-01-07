<?php

namespace App\Http\Controllers;

use App\Models\client\Client; // Assurez-vous que vos modèles existent
use App\Models\Compte\Compte; 
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            return response()->json([
                'success' => true,
                'total_clients' => Client::count(), // Compte le nombre total de clients
                'total_comptes' => Compte::count(), // Compte le nombre total de comptes
                'actifs_en_direct' => 12,           // À adapter selon votre logique
                'performance' => 98.5               // À adapter selon votre logique
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}