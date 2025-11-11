<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestComptesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Création de comptes de test avec solde initial de 50 000f...');

        // Créer quelques utilisateurs de test avec leurs comptes
        $usersData = [
            [
                'nom' => 'DIALLO',
                'prenom' => 'Aminata',
                'telephone' => '771234567',
                'email' => 'aminata.diallo@example.com',
                'code_pin' => '1234',
            ],
            [
                'nom' => 'MBAYE',
                'prenom' => 'Ibrahima',
                'telephone' => '772345678',
                'email' => 'ibrahima.mbayee@example.com',
                'code_pin' => '5678',
            ],
            [
                'nom' => 'SOW',
                'prenom' => 'Fatou',
                'telephone' => '773456789',
                'email' => 'fatou.sow@example.com',
                'code_pin' => '9876',
            ],
        ];

        foreach ($usersData as $userData) {
            // Créer l'utilisateur (éviter les doublons)
            $user = User::firstOrCreate(
                ['telephone' => $userData['telephone']],
                [
                    'nom' => $userData['nom'],
                    'prenom' => $userData['prenom'],
                    'telephone' => $userData['telephone'],
                    'email' => $userData['email'],
                    'role' => 'client',
                ]
            );

            // Créer le compte seulement s'il n'existe pas déjà
            $compte = $user->comptes()->firstOrCreate(
                ['id_client' => $user->id],
                [
                    'numero_compte' => $this->generateNumeroCompte(),
                    'code_pin' => Hash::make($userData['code_pin']),
                    'type' => 'client',
                    'date_creation' => now()->toDateString(),
                    'statut' => 'actif',
                    'metadata' => [
                        'derniereModification' => now(),
                        'version' => 1,
                    ],
                ]
            );

            // Ajouter le solde initial de 50 000f seulement s'il n'y a pas déjà de transactions
            if ($compte->transactions()->count() === 0) {
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

            $this->command->info("✅ Compte créé pour {$userData['nom']} {$userData['prenom']} ({$userData['telephone']}) - Solde: 50 000f");
        }

        $this->command->info('🎉 Comptes de test créés avec succès!');
    }

    /**
     * Génère un numéro de compte unique
     */
    private function generateNumeroCompte(): string
    {
        do {
            $numero = 'OM' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (Compte::where('numero_compte', $numero)->exists());

        return $numero;
    }

    /**
     * Génère une référence unique pour les transactions
     */
    private function generateReference(string $prefix): string
    {
        return $prefix . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}