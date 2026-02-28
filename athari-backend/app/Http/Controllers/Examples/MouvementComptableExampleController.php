<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Models\Compte\MouvementComptable;
use App\Services\ComptabiliteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Exemple : Contrôleur de Mouvement Comptable
 * 
 * Le trait UsesDateComptable remplit automatiquement la date_comptable
 * et la clé étrangère vers la journée comptable.
 */
class MouvementComptableExampleController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.agence.ouverte');
    }

    /**
     * Créer un mouvement comptable
     * 
     * POST /api/mouvements-comptables
     */
    public function store(Request $request)
    {
        try {
            $agenceId = auth()->user()->agence_id;
            $session = ComptabiliteService::getActiveSessionOrFail($agenceId);

            $mouvement = DB::transaction(function () use ($request, $agenceId) {
                return MouvementComptable::create([
                    'compte_id'         => $request->compte_id,
                    'date_mouvement'    => $request->date_mouvement ?? now()->toDateString(),
                    'date_valeur'       => $request->date_valeur ?? now()->toDateString(),
                    'libelle_mouvement' => $request->libelle,
                    'montant_debit'     => $request->montant_debit,
                    'montant_credit'    => $request->montant_credit,
                    'description'       => $request->description,
                    'agence_id'         => $agenceId,
                    // ✅ date_comptable et jour_comptable_id seront remplis par le trait
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id'                => $mouvement->id,
                    'date_comptable'    => $mouvement->date_comptable, // ✅ Auto-rempli
                    'jour_comptable_id' => $mouvement->jour_comptable_id, // ✅ Auto-rempli
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
