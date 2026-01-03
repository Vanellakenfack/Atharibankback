<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Affiche la liste paginée des utilisateurs avec leurs rôles.
     */
    public function index(Request $request)
    {
        // Vérification des permissions
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $query = User::with('roles:id,name');

        // Recherche par nom ou email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        // Transformation des données
        $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'role' => $user->roles->first()?->name,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($users);
    }

    /**
     * Crée un nouvel utilisateur avec son rôle.
     */
    public function store(Request $request)
    {
        // Vérification des permissions
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['required', 'string', 'exists:roles,name'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Le rôle est obligatoire.',
            'role.exists' => 'Le rôle sélectionné n\'existe pas.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assigner le rôle
        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $validated['role'],
                'roles' => [$validated['role']],
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * Affiche un utilisateur spécifique.
     */
    public function show(Request $request, User $user)
    {
        // Vérification des permissions
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $user->load('roles:id,name', 'permissions:id,name');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name'),
            'role' => $user->roles->first()?->name,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Met à jour un utilisateur existant.
     */
    public function update(Request $request, User $user)
    {
        // Vérification des permissions
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        // Empêcher la modification de son propre rôle (sécurité)
        if ($request->user()->id === $user->id && $request->has('role')) {
            $currentRole = $request->user()->getRoleNames()->first();
            if ($request->role !== $currentRole) {
                return response()->json([
                    'message' => 'Vous ne pouvez pas modifier votre propre rôle'
                ], 403);
            }
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['sometimes', 'required', 'string', 'exists:roles,name'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'email est obligatoire.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.exists' => 'Le rôle sélectionné n\'existe pas.',
        ]);

        // Mise à jour des champs de base
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();

        // Mise à jour du rôle si fourni
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        $user->load('roles:id,name');

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Supprime un utilisateur.
     */
    public function destroy(Request $request, User $user)
    {
        // Vérification des permissions
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        // Empêcher l'auto-suppression
        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        // Empêcher la suppression d'un DG ou Admin par un non-DG
        if ($user->hasRole(['DG', 'Admin']) && !$request->user()->hasRole('DG')) {
            return response()->json([
                'message' => 'Seul le DG peut supprimer un compte DG ou Admin'
            ], 403);
        }

        // Supprimer les tokens de l'utilisateur
        $user->tokens()->delete();
        
        // Supprimer l'utilisateur
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }

    /**
     * Récupère tous les rôles disponibles.
     */
    public function getRoles(Request $request)
    {
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $roles = Role::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    /**
     * Récupère toutes les permissions disponibles.
     */
    public function getPermissions(Request $request)
    {
        if (!$request->user()->can('gerer roles et permissions')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $permissions = Permission::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($permissions);
    }

    /**
     * Synchronise les rôles d'un utilisateur.
     */
    public function syncRoles(Request $request, User $user)
    {
        if (!$request->user()->can('gerer utilisateurs')) {
            return response()->json(['message' => 'Action non autorisée'], 403);
        }

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($validated['roles']);

        return response()->json([
            'message' => 'Rôles mis à jour avec succès',
            'roles' => $user->getRoleNames(),
        ]);
    }

    /**
     * Récupère les informations de l'utilisateur connecté.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('roles:id,name');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
 * Crée un nouveau rôle.
 */
public function storeRole(Request $request)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        'permissions' => ['sometimes', 'array'],
        'permissions.*' => ['string', 'exists:permissions,name'],
    ], [
        'name.required' => 'Le nom du rôle est obligatoire.',
        'name.unique' => 'Ce nom de rôle existe déjà.',
    ]);

    $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

    // Assigner les permissions si fournies
    if (isset($validated['permissions'])) {
        $role->syncPermissions($validated['permissions']);
    }

    return response()->json([
        'message' => 'Rôle créé avec succès',
        'role' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'created_at' => $role->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $role->updated_at->format('Y-m-d H:i:s'),
        ],
    ], 201);
}

/**
 * Met à jour un rôle existant.
 */
public function updateRole(Request $request, Role $role)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    // Empêcher la modification des rôles systèmes (si nécessaire)
    $systemRoles = ['DG', 'Admin'];
    if (in_array($role->name, $systemRoles)) {
        return response()->json([
            'message' => 'Ce rôle système ne peut pas être modifié'
        ], 403);
    }

    $validated = $request->validate([
        'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
        'permissions' => ['sometimes', 'array'],
        'permissions.*' => ['string', 'exists:permissions,name'],
    ], [
        'name.required' => 'Le nom du rôle est obligatoire.',
        'name.unique' => 'Ce nom de rôle existe déjà.',
    ]);

    if (isset($validated['name'])) {
        $role->name = $validated['name'];
        $role->save();
    }

    // Synchroniser les permissions si fournies
    if (isset($validated['permissions'])) {
        $role->syncPermissions($validated['permissions']);
    }

    $role->load('permissions');

    return response()->json([
        'message' => 'Rôle mis à jour avec succès',
        'role' => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
            'created_at' => $role->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $role->updated_at->format('Y-m-d H:i:s'),
        ],
    ]);
}

/**
 * Supprime un rôle.
 */
public function destroyRole(Request $request, Role $role)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    // Empêcher la suppression des rôles systèmes
    $systemRoles = ['DG', 'Admin'];
    if (in_array($role->name, $systemRoles)) {
        return response()->json([
            'message' => 'Ce rôle système ne peut pas être supprimé'
        ], 403);
    }

    // Vérifier si des utilisateurs utilisent ce rôle
    $userCount = $role->users()->count();
    if ($userCount > 0) {
        return response()->json([
            'message' => "Ce rôle est utilisé par $userCount utilisateur(s). Réassignez-les avant de supprimer le rôle.",
        ], 400);
    }

    $role->delete();

    return response()->json([
        'message' => 'Rôle supprimé avec succès'
    ]);
}

/**
 * Synchronise les permissions d'un rôle.
 */
public function syncRolePermissions(Request $request, Role $role)
{
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $validated = $request->validate([
        'permissions' => ['required', 'array'],
        'permissions.*' => ['string', 'exists:permissions,name'],
    ]);

    $role->syncPermissions($validated['permissions']);

    return response()->json([
        'message' => 'Permissions synchronisées avec succès',
        'permissions' => $role->permissions->pluck('name'),
    ]);
}

/**
 * Crée une nouvelle permission.
 */
public function storePermission(Request $request)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
        'description' => ['nullable', 'string', 'max:500'],
    ], [
        'name.required' => 'Le nom de la permission est obligatoire.',
        'name.unique' => 'Cette permission existe déjà.',
    ]);

    $permission = Permission::create([
        'name' => $validated['name'],
        'guard_name' => 'web',
        'description' => $validated['description'] ?? null,
    ]);

    return response()->json([
        'message' => 'Permission créée avec succès',
        'permission' => [
            'id' => $permission->id,
            'name' => $permission->name,
            'description' => $permission->description,
            'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $permission->updated_at->format('Y-m-d H:i:s'),
        ],
    ], 201);
}

/**
 * Met à jour une permission existante.
 */
public function updatePermission(Request $request, Permission $permission)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    $validated = $request->validate([
        'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('permissions')->ignore($permission->id)],
        'description' => ['nullable', 'string', 'max:500'],
    ], [
        'name.required' => 'Le nom de la permission est obligatoire.',
        'name.unique' => 'Cette permission existe déjà.',
    ]);

    if (isset($validated['name'])) {
        $permission->name = $validated['name'];
    }
    
    if (isset($validated['description'])) {
        $permission->description = $validated['description'];
    }
    
    $permission->save();

    return response()->json([
        'message' => 'Permission mise à jour avec succès',
        'permission' => [
            'id' => $permission->id,
            'name' => $permission->name,
            'description' => $permission->description,
            'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $permission->updated_at->format('Y-m-d H:i:s'),
        ],
    ]);
}

/**
 * Supprime une permission.
 */
public function destroyPermission(Request $request, Permission $permission)
{
    // Vérification des permissions
    if (!$request->user()->can('gerer roles et permissions')) {
        return response()->json(['message' => 'Action non autorisée'], 403);
    }

    // Vérifier si la permission est utilisée par des rôles
    $roleCount = $permission->roles()->count();
    if ($roleCount > 0) {
        return response()->json([
            'message' => "Cette permission est utilisée par $roleCount rôle(s). Retirez-la d'abord des rôles.",
        ], 400);
    }

    $permission->delete();

    return response()->json([
        'message' => 'Permission supprimée avec succès'
    ]);
}
}