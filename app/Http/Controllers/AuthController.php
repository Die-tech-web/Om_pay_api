<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Http\Traits\ApiResponseTrait;

class AuthController extends Controller
{
    use ApiResponseTrait;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse($result, 'Utilisateur créé avec succès', 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return $this->errorResponse('Téléphone ou code PIN invalide', 401);
        }

        return $this->successResponse($result, 'Connexion réussie');
    }

    public function logout()
    {
        $this->authService->logout(auth()->user());
        return $this->successResponse([], 'Déconnexion réussie');
    }
}
