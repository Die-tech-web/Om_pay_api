<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes d'authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes marchands - Commentées pour l'instant
// Route::get('/marchands', [App\Http\Controllers\MarchandController::class, 'index']);
// Route::get('/marchands/{id}', [App\Http\Controllers\MarchandController::class, 'show']);

// Routes protégées
Route::middleware('auth:api')->group(function () {
    // Route::post('/logout', [AuthController::class, 'logout']); // Commenté temporairement

    // Routes comptes
    // Route::apiResource('comptes', CompteController::class)->except(['update', 'destroy']); // Commenté - un seul compte par utilisateur
    Route::get('/mon-compte', [CompteController::class, 'show']); // Voir mon compte (ID récupéré du token)

    // Routes transactions - ID du compte récupéré depuis le token
    Route::get('/mes-transactions', [TransactionController::class, 'index']); // Mes transactions
    Route::post('/mes-transactions', [TransactionController::class, 'store']); // Nouvelle transaction
    Route::get('/mes-transactions/{reference}', [TransactionController::class, 'show']); // Détails transaction par référence
});
