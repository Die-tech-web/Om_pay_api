<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Services\CompteService;
use App\Http\Requests\CreateCompteRequest;
use App\Http\Traits\ApiResponseTrait;

class CompteController extends Controller
{
    use ApiResponseTrait;

    protected $compteService;

    public function __construct(CompteService $compteService)
    {
        $this->compteService = $compteService;
    }

    // public function index()
    // {
    //     $user = auth()->user();
    //     $comptes = $this->compteService->getUserComptes($user);

    //     return $this->successResponse($comptes, 'Comptes récupérés avec succès');
    // }

    // public function store(CreateCompteRequest $request)
    // {
    //     $user = auth()->user();
    //     $compte = $this->compteService->createCompte($user, $request->validated());

    //     return $this->successResponse($compte, 'Compte créé avec succès', 201);
    // }

    public function show()
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();
        
        // Récupérer le compte via la relation
        $compte = Compte::where('id_client', $user->id)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Vérification de sécurité : le compte appartient à l'utilisateur connecté
        if ($compte->id_client !== $user->id) {
            return $this->errorResponse('Accès non autorisé', 403);
        }
        
        $compte = $this->compteService->getCompteWithTransactions($compte);

        // Formater la réponse : infos client directement dans data + métadonnées en bas
        $compteArray = $compte->toArray();
        
        // Inclure les informations de l'utilisateur directement dans data
        $compteArray = array_merge($compteArray, [
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'telephone' => $user->telephone,
            'id_client' => $user->id,
        ]);

        // Supprimer le code_pin et les données redondantes pour la sécurité
        unset($compteArray['code_pin']);
        unset($compteArray['created_at']);
        unset($compteArray['updated_at']);

        return $this->successResponse($compteArray, 'Compte récupéré avec succès');
    }
}
