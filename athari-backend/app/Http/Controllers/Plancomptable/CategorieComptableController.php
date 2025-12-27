<?php
namespace App\Http\Controllers\Plancomptable;

use App\Http\Controllers\Controller;
use App\Models\chapitre\CategorieComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Plancomptable\UpdateCategorieRequest;
class CategorieComptableController extends Controller
{
    /**
     * Liste les rubriques pour le formulaire de création de compte.
     */
    public function index(): JsonResponse
    {
        $categories = CategorieComptable::orderBy('code')->get();
        
        return response()->json([
            'status' => 'success',
            'data'   => $categories
        ]);
    }
    /**
 * Met à jour une catégorie.
 */
public function update(UpdateCategorieRequest $request, $id)
{
    $categorie = CategorieComptable::findOrFail($id);
    $categorie->update($request->validated());
    
    return response()->json(['status' => 'success', 'data' => $categorie]);
}
    /**
     * Crée une nouvelle catégorie (ex: Classe 7 ou Rubrique 721).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'        => 'required|unique:categories_comptables,code',
            'libelle'     => 'required|string',
            'niveau'      => 'required|integer|in:1,2',
            'type_compte' => 'required|in:ACTIF,PASSIF,CHARGE,PRODUIT',
            'parent_id'   => 'nullable|exists:categories_comptables,id'
        ]);

        $categorie = CategorieComptable::create($validated);

        return response()->json($categorie, 201);
    }
}