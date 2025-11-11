<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;

class CompteService
{
    public function getUserComptes(User $user)
    {
        return $user->comptes()->get()->each(function ($compte) {
            $compte->solde = $compte->getSoldeAttribute();
        });
    }

    public function createCompte(User $user, array $data)
    {
        $compte = $user->comptes()->create([
            'numero_compte' => $this->generateNumeroCompte(),
            'code_pin' => \Illuminate\Support\Facades\Hash::make($data['code_pin']),
            'type' => $data['type'] ?? 'client',
            'date_creation' => now()->toDateString(),
            'statut' => 'actif',
            'metadata' => [
                'derniereModification' => now(),
                'version' => 1,
            ],
        ]);

        // Ajouter automatiquement le solde initial de 50 000f
        $this->ajouterSoldeInitial($compte);

        return $compte;
    }

    public function getCompteWithTransactions(Compte $compte)
    {
        $compte->solde = $compte->getSoldeAttribute();

        return $compte;
    }

    /**
     * Ajoute un solde initial de 50 000f à un nouveau compte
     */
    private function ajouterSoldeInitial(Compte $compte)
    {
        Transaction::create([
            'compte_id' => $compte->id,
            'type' => 'depot',
            'montant' => 50000.00,
            'libelle' => 'Solde initial du compte',
            'description' => 'Dépôt initial pour activer le compte',
            'reference' => $this->generateReference('INIT'),
            'date_transaction' => now(),
            'statut' => 'validee',
        ]);
    }

    /**
     * Génère une référence unique pour les transactions
     */
    private function generateReference(string $prefix): string
    {
        return $prefix . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }

    private function generateNumeroCompte(): string
    {
        do {
            $numero = 'OM' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Compte::where('numero_compte', $numero)->exists());

        return $numero;
    }
}