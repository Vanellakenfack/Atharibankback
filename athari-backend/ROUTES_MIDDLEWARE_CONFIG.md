# 📋 Configuration des Routes avec Middleware `check.agence.ouverte`

## ⚠️ Problème Résolu

Vous aviez ajouté `$this->middleware('check.agence.ouverte')` dans les **contrôleurs**, mais les routes avaient déjà `'agence.ouverte'`. Cela créait potentiellement des doublons.

**Solution:** Les middlewares sont maintenant **uniquement au niveau des routes** (meilleure pratique).

---

## ✅ Routes à Vérifier/Compléter dans `routes/api.php`

### 1️⃣ RetraitController
Actuellement dans les routes, vous devez avoir :
```php
Route::post('/retraits', [RetraitController::class, 'store'])
    ->middleware('auth:sanctum', 'check.agence.ouverte');

Route::get('/retraits/{id}/imprimer', [RetraitController::class, 'imprimerRecu'])
    ->middleware('auth:sanctum', 'check.agence.ouverte');
```

### 2️⃣ CaisseOperationController
Déjà partiellement configuré, vérifiez que vous avez :
```php
Route::post('/caisse/operation', [CaisseOperationController::class, 'store'])
    ->middleware('auth:sanctum', 'check.agence.ouverte');
```

### 3️⃣ OperationDiversController
Déjà bien configuré. Les routes utilisent `'agence.ouverte'` mais vous devez vérifier que c'est `'check.agence.ouverte'` :
```php
Route::prefix('operation-diverses')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [OperationDiversController::class, 'store'])
        ->middleware('check.agence.ouverte');
    
    Route::post('/mata-boost', [OperationDiversController::class, 'creerMataBoost'])
        ->middleware('permission:saisir od', 'check.agence.ouverte');
    
    // ... autres routes
});
```

### 4️⃣ CompteValidationController
À ajouter/vérifier :
```php
Route::prefix('comptes')->middleware('auth:sanctum')->group(function () {
    Route::post('{id}/valider', [CompteValidationController::class, 'valider'])
        ->middleware('check.agence.ouverte');
    
    Route::post('{id}/rejeter', [CompteValidationController::class, 'rejeter'])
        ->middleware('check.agence.ouverte');
});
```

### 5️⃣ DatContratController
Actuellement il faut ajouter le middleware :
```php
Route::prefix('dat')->middleware('auth:sanctum')->group(function () {
    Route::get('/contracts', [DatContratController::class, 'index']);
    
    Route::post('/contracts', [DatContratController::class, 'store'])
        ->middleware('can:saisir dat', 'check.agence.ouverte');
    
    Route::post('{id}/valider', [DatContratController::class, 'valider'])
        ->middleware('can:valider dat', 'check.agence.ouverte');
    
    Route::post('{id}/cloturer', [DatContratController::class, 'cloturer'])
        ->middleware('can:cloturer dat', 'check.agence.ouverte');
    
    Route::get('{id}/simulate', [DatContratController::class, 'simulate'])
        ->middleware('can:saisir dat', 'check.agence.ouverte');
});
```

---

## 🎯 Ordonnance Recommandée des Middlewares

```php
// Pour les routes qui créent des transactions/mouvements
Route::post('/endpoint', [Controller::class, 'store'])
    ->middleware(
        'auth:sanctum',           // 1. Authentification d'abord
        'check.agence.ouverte',   // 2. Vérifier agence ouverte
        'permission:...'          // 3. Puis permissions spécifiques
    );
```

---

## ⚡ Flux d'Exécution

Quand l'utilisateur appelle une route :

1. ✅ `auth:sanctum` — Vérifie que l'utilisateur est authentifié
2. ✅ `check.agence.ouverte` — Vérifie que l'agence a une journée ouverte + injecte `active_session`
3. ✅ `permission:...` — Vérifie les permissions spécifiques
4. ✅ **Contrôleur** — Crée l'entité (Retrait, OD, DAT, etc.)
5. ✅ **Trait `UsesDateComptable`** — Remplit automatiquement `date_comptable` & `jour_comptable_id`
6. ✅ **DB::transaction()** — Commite ou rollback tout ensemble

---

## 🔍 Vérification : Middleware `agence.ouverte` vs `check.agence.ouverte`

**Ancien nom:** `'agence.ouverte'`  
**Nouveau nom:** `'check.agence.ouverte'`

Vous devez **remplacer tous les `'agence.ouverte'` par `'check.agence.ouverte'`** dans `routes/api.php`.

---

## 📝 Script de Correction Rapide

Si vous avez beaucoup de routes avec `'agence.ouverte'`, vous pouvez faire une recherche-remplacement :

```bash
# Terminal PowerShell
(Get-Content routes/api.php) -replace "'agence\.ouverte'", "'check.agence.ouverte'" | Set-Content routes/api.php
```

Ou manuellement dans VS Code :
- Ctrl+H (Chercher/Remplacer)
- Chercher: `'agence.ouverte'`
- Remplacer par: `'check.agence.ouverte'`

---

## ✅ Checklist Final

- [ ] **RetraitController** routes ont `'check.agence.ouverte'`
- [ ] **CaisseOperationController** routes ont `'check.agence.ouverte'`
- [ ] **OperationDiversController** routes ont `'check.agence.ouverte'`
- [ ] **CompteValidationController** routes ont `'check.agence.ouverte'`
- [ ] **DatContratController** routes ont `'check.agence.ouverte'`
- [ ] Aucun middleware `'agence.ouverte'` restant dans les contrôleurs
- [ ] Tous les anciens `'agence.ouverte'` remplacés par `'check.agence.ouverte'` dans routes/api.php
- [ ] Test : Appeler une route avec agence fermée → 403 ✅
- [ ] Test : Appeler une route avec agence ouverte → Données créées avec `date_comptable` ✅

---

**Statut:** Configuration unifiée et sans doublons ✅
