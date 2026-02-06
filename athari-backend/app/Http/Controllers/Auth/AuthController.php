<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * Gère la connexion de l'utilisateur et la génération du token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            // device_name est obligatoire pour la traçabilité des tokens Sanctum
            'device_name' => 'required|string', 
        ]);

        // 1. Tente d'authentifier l'utilisateur
        if (! Auth::attempt($request->only('email', 'password'))) {
            
            // --- LOG EN CAS D'ÉCHEC ---
            // CORRECTION : Utilisez activity() et mettez la description dans la méthode log()
            activity()
                ->log('Tentative de connexion échouée', 'auth.failed'); // Le 2e argument est l'événement

            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }
        
        $user = Auth::user();

        // --- LOG EN CAS DE SUCCÈS ---
        // CORRECTION : Supprimez ActivityLogger::info() et commencez par activity()
        activity()
            ->performedOn($user) // Associe le log à l'utilisateur qui se connecte
            ->withProperty('ip_address', $request->ip()) // Ajoute l'adresse IP du client
            ->withProperty('user_agent', $request->header('User-Agent')) // Ajoute l'agent utilisateur
            ->log("Connexion réussie depuis l'appareil : " . $request->device_name, 'auth.login'); 
        // -----------------------------
            // --- Dans votre méthode login() ---

$user = Auth::user();

// Force la lecture directe en base de données via la relation Eloquent
// On évite getRoleNames() qui s'appuie sur le cache et les guards
$role = $user->roles()->first()?->name; // Récupération via la relation directe

$abilities = [];

// Le switch fonctionnera maintenant car $role contiendra "DG" ou "Admin"
switch ($role) {
    case 'DG':
    case 'Admin':
        $abilities = ['*'];
        break;
    // ... reste de vos cases ...
    default:
        $abilities = ['read:only'];
}

        // Récupération du rôle principal de l'utilisateur (assumant l'utilisation de spatie/laravel-permission)
        $abilities = [];

        // --- 2. DÉFINITION DES CAPACITÉS (ABILITIES) BASÉE SUR LE RÔLE ACL ---
        switch ($role) {
            case 'DG':
            case 'Admin': // Rôle Admin ajouté pour l'accès complet
                $abilities = ['*']; // Accès illimité pour les tokens
                break;
                
            case 'Chef Comptable':
            case 'Chef d\'Agence (CA)':
            case 'Assistant Juridique (AJ)':
                // Rôles Web avec validation et accès aux logs
                $abilities = ['access:web', 'validate:core-banking', 'audit:logs'];
                break;
                
            case 'Assistant Comptable (AC)':
            case 'Caissière':
            case 'Agent de Crédit (AC)':
                // Rôles Web d'entrée de données ou de simple consultation
                $abilities = ['access:web', 'entry:data']; 
                break;
                
            case 'Collecteur':
                // Rôle Mobile (App très restreinte)
                $abilities = ['access:mobile', 'entry:caisse-mobile'];
                break;
                
            default:
                // Rôle par défaut/inconnu : accès en lecture seule
                $abilities = ['read:only'];
        }

        // 3. Génération du token Sanctum avec les capacités définies
        $token = $user->createToken($request->device_name, $abilities);

        // 4. Réponse au client
        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'refreshToken' => $token->plainTextToken, // Return same token as refresh token for Sanctum
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'abilities' => $abilities, // Pour que le client sache ce que le token peut faire
            ],
        ]);
    }

    /**
     * Déconnexion sécurisée : révoque le token utilisé pour la requête actuelle.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // --- LOG DE DÉCONNEXION ---
        // CORRECTION : Supprimez ActivityLogger::info() et commencez par activity()
        activity()
            ->performedOn($user)
            ->withProperty('ip_address', $request->ip())
            ->log('Déconnexion réussie', 'auth.logout');
        // --------------------------

        // Supprime le token spécifique utilisé pour la requête, assurant la révocation immédiate
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * Rafraîchir le token d'accès en utilisant le refresh token.
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refreshToken' => 'required|string',
        ]);

        // Extract the token part from the plain text token (format: id|token)
        $tokenParts = explode('|', $request->refreshToken);
        if (count($tokenParts) !== 2) {
            \Log::info('Refresh token invalid format', ['token' => $request->refreshToken]);
            return response()->json(['message' => 'Refresh token invalide'], 401);
        }
        $tokenValue = $tokenParts[1];

        // Hash the token value to match Sanctum's storage
        $hashedToken = hash('sha256', $tokenValue);

        \Log::info('Refresh attempt', [
            'plain_token' => $request->refreshToken,
            'token_value' => $tokenValue,
            'hashed_token' => $hashedToken
        ]);

        // Find the token in the database
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($hashedToken);

        if (!$accessToken) {
            \Log::info('Token not found in database', ['hashed_token' => $hashedToken]);
            return response()->json(['message' => 'Refresh token invalide'], 401);
        }

        \Log::info('Token found', ['token_id' => $accessToken->id, 'user_id' => $accessToken->tokenable_id]);

        $user = $accessToken->tokenable;

        // Delete the old token
        $accessToken->delete();

        // Récupération du rôle principal de l'utilisateur
        $role = $user->roles()->first()?->name;

        // Définition des capacités basées sur le rôle
        $abilities = [];
        switch ($role) {
            case 'DG':
            case 'Admin':
                $abilities = ['*'];
                break;
            case 'Chef Comptable':
            case 'Chef d\'Agence (CA)':
            case 'Assistant Juridique (AJ)':
                $abilities = ['access:web', 'validate:core-banking', 'audit:logs'];
                break;
            case 'Assistant Comptable (AC)':
            case 'Caissière':
            case 'Agent de Crédit (AC)':
                $abilities = ['access:web', 'entry:data'];
                break;
            case 'Collecteur':
                $abilities = ['access:mobile', 'entry:caisse-mobile'];
                break;
            default:
                $abilities = ['read:only'];
        }

        // Créer un nouveau token d'accès
        $newToken = $user->createToken($request->device_name ?? 'default', $abilities);

        // Log du rafraîchissement
        activity()
            ->performedOn($user)
            ->withProperty('ip_address', $request->ip())
            ->log('Token rafraîchi', 'auth.refresh');

        return response()->json([
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'abilities' => $abilities,
            ],
        ]);
    }
}