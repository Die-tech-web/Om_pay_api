<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Transaction;
use App\Services\CompteService;
use App\Services\TransactionService;
use App\Http\Requests\CreateCompteRequest;
use App\Http\Requests\CreateCompteTransactionRequest;
use App\Http\Traits\ApiResponseTrait;

class CompteController extends Controller
{
    use ApiResponseTrait;

    protected $compteService;
    protected $transactionService;

    public function __construct(CompteService $compteService, TransactionService $transactionService)
    {
        $this->compteService = $compteService;
        $this->transactionService = $transactionService;
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

    /**
     * Récupérer le solde, le type, le statut et le code QR d'un compte par numéro
     */
    public function getSolde($numero_compte)
    {
        $compte = Compte::where('numero_compte', $numero_compte)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        return $this->successResponse([
            'solde' => $compte->solde,
            'type' => $compte->type,
            'statut' => $compte->statut,
            'code_qr' => $compte->code_qr,
        ], 'Solde récupéré avec succès');
    }

    /**
     * Récupérer toutes les transactions liées à un compte (entrantes et sortantes)
     */
    public function getTransactions($numero_compte)
    {
        $compte = Compte::where('numero_compte', $numero_compte)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Toutes les transactions liées : sortantes (compte_id) et entrantes (numero_destinataire)
        $transactions = Transaction::where(function($query) use ($compte, $numero_compte) {
            $query->where('compte_id', $compte->id)
                  ->orWhere('numero_destinataire', $numero_compte);
        })->get();

        // Ajouter les informations de l'expéditeur pour chaque transaction
        $transactions->transform(function ($transaction) use ($compte) {
            $transactionArray = $transaction->toArray();

            // Pour les transactions sortantes (compte_id == compte actuel)
            if ($transaction->compte_id === $compte->id) {
                $transactionArray['expediteur'] = $compte->user->nom . ' ' . $compte->user->prenom;
            }
            // Pour les transactions entrantes (numero_destinataire == numero_compte)
            else {
                $expediteurUser = $transaction->compte->user;
                $transactionArray['expediteur'] = $expediteurUser ? $expediteurUser->nom . ' ' . $expediteurUser->prenom : 'Inconnu';
            }

            return $transactionArray;
        });

        return $this->successResponse($transactions, 'Transactions récupérées avec succès');
    }

    /**
     * Récupérer uniquement les transactions effectuées par le propriétaire du compte
     */
    public function getMesTransactions($numero_compte)
    {
        $compte = Compte::where('numero_compte', $numero_compte)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Seulement les transactions effectuées par le propriétaire (compte_id)
        $transactions = Transaction::where('compte_id', $compte->id)->get();

        // Ajouter les informations de l'expéditeur (toujours le propriétaire du compte)
        $transactions->transform(function ($transaction) use ($compte) {
            $transactionArray = $transaction->toArray();
            $transactionArray['expediteur'] = $compte->user->nom . ' ' . $compte->user->prenom;
            return $transactionArray;
        });

        return $this->successResponse($transactions, 'Mes transactions récupérées avec succès');
    }

    /**
     * Effectuer une transaction depuis un compte spécifique
     */
    public function storeTransaction(CreateCompteTransactionRequest $request, $numero_compte)
    {
        $compte = Compte::where('numero_compte', $numero_compte)->first();

        if (!$compte) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        try {
            $transaction = $this->transactionService->createTransaction($compte, $request->validated());

            return $this->successResponse($transaction, 'Transaction effectuée avec succès', 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
