<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WelcomeNewUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthService
{
    protected $compteService;
    protected $emailService;

    public function __construct(CompteService $compteService, EmailService $emailService)
    {
        $this->compteService = $compteService;
        $this->emailService = $emailService;
    }

    public function register(array $data)
    {
        $user = User::create([
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'],
            'email' => $data['email'],
            'role' => 'client', // Tous les utilisateurs sont des clients par défaut
        ]);

        // Créer un compte par défaut pour l'utilisateur en utilisant le CompteService
        // Cela garantira automatiquement un solde initial de 50 000f
        $compte = $this->compteService->createCompte($user, [
            'type' => 'client',
            'code_pin' => $data['code_pin']
        ]);

        // Envoyer l'email de bienvenue via le service email robuste
        $this->emailService->sendWelcomeEmail($user, $data['code_pin']);

        $token = $user->createToken('AuthToken', [], now()->addMinutes(60))->accessToken;
        $refreshToken = $user->createToken('RefreshToken', [], now()->addDays(30))->accessToken;

        // Calculer les dates d'expiration
        $accessTokenExpiresAt = now()->addMinutes(60);
        $refreshTokenExpiresAt = now()->addDays(30);

        // Retourner le compte avec le code PIN en clair (pas hashé)
        $compteArray = $compte->toArray();
        $compteArray['code_pin'] = $data['code_pin'];

        return [
            'user' => $user,
        ];
    }

    public function login(array $data)
    {
        $user = User::where('telephone', $data['telephone'])->first();

        if (!$user) {
            return null;
        }

        // Vérifier le code PIN sur le compte principal de l'utilisateur
        $compte = $user->comptes()->first();
        if (!$compte || !Hash::check($data['code_pin'], $compte->code_pin)) {
            return null;
        }

        // Créer le token d'accès
        $accessToken = $user->createToken('AuthToken', [], now()->addMinutes(60))->accessToken;
        
        // Créer le refresh token
        $refreshToken = $user->createToken('RefreshToken', [], now()->addDays(30))->accessToken;

        // Calculer les dates d'expiration
        $accessTokenExpiresAt = now()->addMinutes(60);
        $refreshTokenExpiresAt = now()->addDays(30);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 60 * 60, // 60 minutes en secondes
            'expires_at' => $accessTokenExpiresAt->toISOString(),
            'refresh_expires_at' => $refreshTokenExpiresAt->toISOString(),
        ];
    }

    public function logout($user)
    {
        $user->token()->revoke();
    }
}
