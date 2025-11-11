<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SoldeInitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Ajout du solde initial de 50 000f pour tous les comptes...');

        $comptes = Compte::all();
        $nombreComptesTraites = 0;

        foreach ($comptes as $compte) {
            // Vérifier si le compte a déjà un solde initial (en recherchant une transaction de dépôt initiale)
            $aDejaSoldeInitial = Transaction::where('compte_id', $compte->id)
                ->where('type', 'depot')
                ->where('libelle', 'Solde initial du compte')
                ->exists();

            if (!$aDejaSoldeInitial) {
                // Créer une transaction de dépôt pour le solde initial TESTER
                Transaction::create([
                    'compte_id' => $compte->id,
                    'type' => 'depot',
                    'montant' => 50000.00,
                    'libelle' => 'Solde initial du compte',
                    'description' => 'Dépôt initial pour activer le compte',
                    'reference' => $this->generateReference('INIT'),
                    'date_transaction' => Carbon::parse($compte->date_creation),
                    'statut' => 'validee',
                ]);

                $nombreComptesTraites++;
                $this->command->info("Solde initial ajouté pour le compte: {$compte->numero_compte}");
            }
        }

        $this->command->info("Terminé! {$nombreComptesTraites} comptes ont reçu un solde initial de 50 000f.");
    }

    /**
     * Génère une référence unique pour les transactions
     */
    private function generateReference(string $prefix): string
    {
        return $prefix . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}