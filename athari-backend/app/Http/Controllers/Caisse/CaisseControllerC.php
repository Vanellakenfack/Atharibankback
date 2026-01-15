<?php

namespace App\Http\Controllers\Caisse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Caisse\Caisse;
use App\Models\Caisse\Guichet;


class CaisseControllerC extends Controller
{
    /**
     * Display a listing of the resource.
     */
  public function index()
{
    $caisses = Caisse::all();

    // Retourne du JSON pour Postman
    return response()->json([
        'success' => true,
        'data' => $caisses
    ], 200);
}

    public function create()
    {
        $guichets = Guichet::where('est_actif', true)->get();
        return view('caisse.caisses.create', compact('guichets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'guichet_id' => 'required|exists:guichets,id',
            'code_caisse' => 'required|unique:caisses,code_caisse',
            'libelle' => 'required|string|max:255',
            'solde_actuel' => 'numeric',
            'plafond_max' => 'nullable|numeric',
        ]);

        Caisse::create($validated);

        return redirect()->route('caisse.caisses.index')->with('success', 'Caisse créée avec succès.');
    }
}
