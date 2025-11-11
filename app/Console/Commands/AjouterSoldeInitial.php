<?php

namespace App\Console\Commands;

use App\Models\Compte;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AjouterSoldeInitial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compte:solde-initial {--force : Forcer l\'application même si des soldes initiaux existent déjà}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ajoute un solde initial de 50 000f à tous les comptes existants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de l\'ajout du solde initial de 50 000f pour tous les comptes...');

        $comptes = Compte::all();
        $nombreComptesTraites = 0;
        $nombreComptesDejaTraites = 0;

        $bar = $this->output->createProgressBar($comptes->count());
        $bar->start();

        foreach ($comptes as $compte) {
            // Vérifier si le compte a déjà un solde initial
            $aDejaSoldeInitial = Transaction::where('compte_id', $compte->id)
                ->where('type', 'depot')
                ->where('libelle', 'Solde initial du compte')
                ->exists();

            if (!$aDejaSoldeInitial || $this->option('force')) {
                // Créer une transaction de dépôt pour le solde initial
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
            } else {
                $nombreComptesDejaTraites++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Terminé avec succès!");
        $this->info("- {$nombreComptesTraites} comptes ont reçu un solde initial de 50 000f");
        
        if ($nombreComptesDejaTraites > 0) {
            $this->warn("- {$nombreComptesDejaTraites} comptes avaient déjà un solde initial");
        }

        if ($this->option('force') && $nombreComptesDejaTraites > 0) {
            $this->warn("⚠️ L'option --force a été utilisée. Les soldes initiaux existants ont été recréés.");
        }

        return Command::SUCCESS;
    }

    /**
     * Génère une référence unique pour les transactions
     */
    private function generateReference(string $prefix): string
    {
        return $prefix . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}