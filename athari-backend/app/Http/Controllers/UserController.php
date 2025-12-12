<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    // --- CRUD standard ---
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));

        $users = User::query()
            ->with(['roles:name', 'permissions:name'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6'],

            // Optionnel (Spatie)
            'roles' => ['sometimes','array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],

            'permissions' => ['sometimes','array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // cast "hashed" ou Hash::make si pas de cast
            ]);

            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            if (!empty($data['permissions'])) {
                $user->syncPermissions($data['permissions']);
            }

            $user->load(['roles:name', 'permissions:name']);

            return response()->json([
                'message' => 'Utilisateur créé',
                'user' => $user,
            ], 201);
        });
    }

    public function show(User $user)
    {
        $user->load(['roles:name', 'permissions:name']);

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['sometimes','nullable','string','min:6'],

            // Optionnel : si présent -> sync
            'roles' => ['sometimes','array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],

            'permissions' => ['sometimes','array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        return DB::transaction(function () use ($user, $data) {
            if (array_key_exists('name', $data)) $user->name = $data['name'];
            if (array_key_exists('email', $data)) $user->email = $data['email'];

            if (array_key_exists('password', $data) && $data['password']) {
                $user->password = $data['password'];
            }

            $user->save();

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            if (array_key_exists('permissions', $data)) {
                $user->syncPermissions($data['permissions'] ?? []);
            }

            $user->load(['roles:name', 'permissions:name']);

            return response()->json([
                'message' => 'Utilisateur mis à jour',
                'user' => $user,
            ]);
        });
    }

    public function destroy(User $user)
    {
        // sécurité basique : empêcher suppression de soi-même
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    // --- Optionnel: endpoints dédiés rôles/permissions ---

    public function syncRoles(Request $request, User $user)
    {
        $data = $request->validate([
            'roles' => ['required','array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user->syncRoles($data['roles']);
        $user->load(['roles:name']);

        return response()->json([
            'message' => 'Rôles synchronisés',
            'user' => $user,
        ]);
    }

    public function syncPermissions(Request $request, User $user)
    {
        $data = $request->validate([
            'permissions' => ['required','array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $user->syncPermissions($data['permissions']);
        $user->load(['permissions:name']);

        return response()->json([
            'message' => 'Permissions synchronisées',
            'user' => $user,
        ]);
    }
}