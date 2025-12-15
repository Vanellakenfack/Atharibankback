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
}