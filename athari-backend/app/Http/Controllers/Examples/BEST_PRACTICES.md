# 📋 Bonnes Pratiques : Injection Automatique de `date_comptable`

## Vue d'ensemble

Chaque transaction/mouvement comptable doit être lié à une **journée comptable ouverte**. Pour cela :

1. **Middleware `check.agence.ouverte`** — Vérifie que l'agence a une journée ouverte et l'injecte dans la requête
2. **Trait `UsesDateComptable`** — Remplit automatiquement `date_comptable` et `jour_comptable_id` lors de la création d'une entité
3. **DB::transaction()** — Assure l'atomicité de toutes les opérations

---

## Pattern de Contrôleur

```php
class MonController extends Controller
{
    public function __construct()
    {
        // ✅ Appliquer le middleware
        $this->middleware('check.agence.ouverte');
    }

    public function store(Request $request)
    {
        try {
            $agenceId = auth()->user()->agence_id;
            
            // Optionnel : vérifier manuellement la session (middleware le fait déjà)
            $session = ComptabiliteService::getActiveSessionOrFail($agenceId);

            // ✅ Transaction atomique
            $entite = DB::transaction(function () use ($request, $agenceId) {
                // Le trait remplira automatiquement date_comptable & jour_comptable_id
                return MonModele::create([
                    'montant'    => $request->montant,
                    'compte_id'  => $request->compte_id,
                    'agence_id'  => $agenceId,
                    // date_comptable et jour_comptable_id : ❌ PAS BESOIN D'LES FOURNIR
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $entite
            ], 201);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
```

---

## Checklist d'Implémentation

- [ ] Ajouter `use App\Models\Concerns\UsesDateComptable;` au modèle
- [ ] Ajouter `use UsesDateComptable;` aux traits du modèle
- [ ] Ajouter `$this->middleware('check.agence.ouverte');` dans le `__construct()`
- [ ] Wrapper toutes les écritures DB dans `DB::transaction()`
- [ ] **NE PAS** fournir `date_comptable` et `jour_comptable_id` manuellement — le trait les remplit
- [ ] Ajouter `agence_id` à la requête si le modèle le nécessite

---

## Contrôleurs à Mettre à Jour

Voici les contrôleurs transactionnels prioritaires :

1. **RetraitController** — Crée `CaisseTransaction` + `TransactionTier`
2. **CaisseOperationController** — Crée transactions digitales
3. **CompteValidationController** — Écritures comptables
4. **OperationDiversController** — OD + mouvements comptables
5. **DatContratController** — Contrats DAT + intérêts

---

## Erreurs Courantes

❌ **Ne pas faire :**
```php
// ❌ Erreur : fournir date_comptable manuellement
MouvementComptable::create([
    'date_comptable' => now()->toDateString(), // ❌ Le trait l'ignore !
    'jour_comptable_id' => 5,                  // ❌ Le trait l'ignore !
]);
```

✅ **Faire :**
```php
// ✅ Correct : laisser le trait faire le job
MouvementComptable::create([
    'montant' => 1000,
    'compte_id' => 5,
    'agence_id' => auth()->user()->agence_id,
    // date_comptable & jour_comptable_id : remplis par le trait
]);
```

---

## Routes avec Middleware

```php
// routes/api.php

// ✅ Appliquer le middleware à une route
Route::post('/versements', [VersementController::class, 'store'])
    ->middleware('check.agence.ouverte');

// ✅ Ou appliquer à un groupe
Route::middleware('check.agence.ouverte')->group(function () {
    Route::post('/versements', [VersementController::class, 'store']);
    Route::post('/retraits', [RetraitController::class, 'store']);
    Route::post('/mouvements', [MouvementController::class, 'store']);
});
```

---

## Test : Vérifier que date_comptable est remplie

```bash
# Créer un versement
curl -X POST http://localhost:8000/api/versements \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "montant": 10000,
    "compte_id": 5,
    "tiers": {"nom_complet": "Jean", "type_piece": "CIN", "numero_piece": "123"}
  }'

# Réponse attendue :
{
  "success": true,
  "data": {
    "reference": "VRS-20260206101234-abc123",
    "montant": 10000,
    "date_comptable": "2026-02-06",    ✅ Rempli auto !
    "jour_comptable_id": 42            ✅ Rempli auto !
  }
}
```

---

## Résumé

| Aspect | Action |
|--------|--------|
| **Modèle** | Ajouter trait `UsesDateComptable` |
| **Contrôleur** | Ajouter middleware `check.agence.ouverte` |
| **Create** | `DB::transaction()` + laisser le trait remplir `date_comptable` |
| **Erreur** | Exception levée si pas de journée ouverte → rollback auto |
| **Avantage** | Aucune date comptable orpheline, atomicité garantie |
