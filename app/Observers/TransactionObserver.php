<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\User;
use App\Events\TransactionCreated;
use App\Jobs\SendTransactionReceivedEmailJob;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        // Gérer la logique métier selon le type de transaction
        $this->traiterTransaction($transaction);
        
        // Générer le reçu PDF pour les transactions validées
        if ($transaction->statut === 'validee') {
            $this->generateRecu($transaction);
        }
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        // Si le statut change vers "validee", traiter la transaction
        if ($transaction->wasChanged('statut') && $transaction->statut === 'validee') {
            $this->traiterTransaction($transaction);
        }
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Traiter la logique métier selon le type de transaction
     */
    private function traiterTransaction(Transaction $transaction): void
    {
        if ($transaction->statut !== 'validee') {
            return;
        }

        try {
            match ($transaction->type) {
                'transfert' => $this->traiterTransfert($transaction),
                'paiement' => $this->traiterPaiement($transaction),
                'retrait' => $this->traiterRetrait($transaction),
                'depot' => $this->traiterDepot($transaction),
                default => null
            };

            // Vérification finale du solde
            $this->verifierSoldeFinal($transaction->compte);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire échouer la transaction principale
            Log::error('Erreur dans le traitement de l\'observer de transaction', [
                'transaction_id' => $transaction->id,
                'type' => $transaction->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // En cas d'erreur critique, on peut décider de marquer la transaction comme problématique
            // mais pour l'instant, on log seulement
        }
    }

    /**
     * Traiter un transfert
     */
    private function traiterTransfert(Transaction $transaction): void
    {
        $frais = $this->calculerFrais($transaction->montant);

        try {
            // Créer la transaction pour le destinataire
            $this->creerTransactionDestinataire($transaction);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la transaction destinataire', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }

        // Créer la transaction de frais si nécessaire
        if ($frais > 0) {
            try {
                $this->creerTransactionFrais($transaction, $frais);
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création de la transaction de frais', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Traiter un paiement
     */
    private function traiterPaiement(Transaction $transaction): void
    {
        // Logique spécifique aux paiements (vérification code marchand, etc.)
        // Pour l'instant, on ne fait rien de spécial
    }

    /**
     * Traiter un retrait
     */
    private function traiterRetrait(Transaction $transaction): void
    {
        // Logique spécifique aux retraits
        // Pour l'instant, on ne fait rien de spécial
    }

    /**
     * Traiter un dépôt
     */
    private function traiterDepot(Transaction $transaction): void
    {
        // Logique spécifique aux dépôts
        // Pour l'instant, on ne fait rien de spécial
    }

    /**
     * Créer la transaction pour le destinataire d'un transfert
     */
    private function creerTransactionDestinataire(Transaction $transactionExpediteur): void
    {
        $compteExpediteur = $transactionExpediteur->compte;
        
        if (!$transactionExpediteur->numero_destinataire) {
            return;
        }

        // Normaliser le numéro de téléphone (supprimer +221 si présent)
        $numeroNormalise = str_replace('+221', '', $transactionExpediteur->numero_destinataire);

        try {
            // Chercher d'abord avec le numéro complet, puis avec le normalisé
            // Forcer la conversion en string pour éviter les comparaisons integer vs string
            $destinataireUser = User::where('telephone', (string) $transactionExpediteur->numero_destinataire)->first();
            if (!$destinataireUser) {
                $destinataireUser = User::where('telephone', (string) $numeroNormalise)->first();
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche du destinataire', [
                'numero' => $transactionExpediteur->numero_destinataire,
                'numero_normalise' => $numeroNormalise,
                'error' => $e->getMessage()
            ]);
            return;
        }
    
        if (!$destinataireUser || !$destinataireUser->id || !\Illuminate\Support\Str::isUuid($destinataireUser->id)) {
            Log::warning('Destinataire invalide pour transfert', [
                'numero_normalise' => $numeroNormalise,
                'user_trouve' => $destinataireUser ? 'oui' : 'non',
                'user_id' => $destinataireUser->id ?? 'null'
            ]);
            return;
        }

        $compteDestinataire = $destinataireUser->comptes()->first();

        if (!$compteDestinataire || !$compteDestinataire->id || !\Illuminate\Support\Str::isUuid($compteDestinataire->id)) {
            Log::warning('Aucun compte valide trouvé pour le destinataire', [
                'user_id' => $destinataireUser->id,
                'numero_normalise' => $numeroNormalise
            ]);
            return;
        }

        // Vérifier qu'on ne transfère pas vers le même compte
        if ($compteDestinataire->id === $compteExpediteur->id) {
            Log::info('Transfert vers le même compte ignoré', [
                'compte_id' => $compteExpediteur->id
            ]);
            return;
        }

        $emetteurUser = $compteExpediteur->user()->first();
        
        $compteDestinataire->transactions()->create([
            'type' => 'depot',
            'montant' => $transactionExpediteur->montant,
            'libelle' => 'Transfert reçu de ' . ($emetteurUser ? $emetteurUser->nom . ' ' . $emetteurUser->prenom : 'Client'),
            'description' => 'Transfert reçu de ' . $compteExpediteur->numero_compte,
            'reference' => $this->generateReference(),
            'date_transaction' => $transactionExpediteur->date_transaction,
            'statut' => 'validee',
        ]);

        // Récupérer la transaction destinataire créée
        $transactionDestinataire = $compteDestinataire->transactions()
            ->where('type', 'depot')
            ->where('montant', $transactionExpediteur->montant)
            ->where('date_transaction', $transactionExpediteur->date_transaction)
            ->first();

        // L'email est maintenant envoyé depuis le TransactionService après commit
    }

    /**
     * Créer la transaction de frais pour un transfert
     */
    private function creerTransactionFrais(Transaction $transaction, float $frais): void
    {
        // Normaliser le numéro de téléphone (supprimer +221 si présent)
        $numeroNormalise = str_replace('+221', '', $transaction->numero_destinataire);

        try {
            $destinataireUser = User::where('telephone', (string) $numeroNormalise)->first();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la recherche du destinataire pour frais', [
                'numero' => $transaction->numero_destinataire,
                'numero_normalise' => $numeroNormalise,
                'error' => $e->getMessage()
            ]);
            return;
        }

        if (!$destinataireUser || !$destinataireUser->id || !\Illuminate\Support\Str::isUuid($destinataireUser->id)) {
            Log::warning('Destinataire invalide pour frais', [
                'numero_normalise' => $numeroNormalise,
                'transaction_id' => $transaction->id,
                'user_trouve' => $destinataireUser ? 'oui' : 'non',
                'user_id' => $destinataireUser->id ?? 'null'
            ]);
            return;
        }

        Log::info('Destinataire trouvé pour frais', [
            'user_id' => $destinataireUser->id,
            'email' => $destinataireUser->email,
            'numero_normalise' => $numeroNormalise
        ]);

        $transaction->compte->transactions()->create([
            'type' => 'frais',
            'montant' => $frais,
            'libelle' => 'Frais de transfert',
            'description' => 'Frais pour transfert vers ' . $destinataireUser->nom . ' ' . $destinataireUser->prenom,
            'reference' => $this->generateReference(),
            'date_transaction' => $transaction->date_transaction,
            'statut' => 'validee',
        ]);
    }

    /**
     * Vérifier que le solde final n'est pas négatif
     */
    private function verifierSoldeFinal(Compte $compte): void
    {
        try {
            $nouveauSolde = $compte->getSoldeAttribute();

            if ($nouveauSolde < 0) {
                // Log de sécurité - en production, on pourrait envoyer une alerte
                \Illuminate\Support\Facades\Log::warning('Solde négatif détecté après transaction', [
                    'compte_id' => $compte->id,
                    'solde' => $nouveauSolde
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du solde final', [
                'compte_id' => $compte->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Générer le reçu
     */
    private function generateRecu(Transaction $transaction): void
    {
        try {
            // Pour l'instant, on stocke juste les données du reçu dans un fichier JSON
            // En production, on utiliserait une bibliothèque comme TCPDF ou DomPDF pour générer un PDF

            // Calculer les frais séparément si nécessaire
            $frais = 0;
            if ($transaction->type === 'transfert') {
                // Chercher s'il y a une transaction de frais associée
                $fraisTransaction = $transaction->compte->transactions()
                    ->where('type', 'frais')
                    ->where('date_transaction', $transaction->date_transaction)
                    ->where('reference', '!=', $transaction->reference)
                    ->first();
                $frais = $fraisTransaction ? $fraisTransaction->montant : 0;
            }

            $recuData = [
                'reference' => $transaction->reference,
                'libelle' => $transaction->libelle,
                'montant' => $transaction->montant,
                'frais' => $frais,
                'total_debite' => $transaction->montant + $frais,
                'destinataire' => $transaction->numero_destinataire ?? $transaction->code_marchand,
                'date_transaction' => $transaction->date_transaction->format('d/m/Y H:i:s'),
                'numero_compte' => $transaction->compte->numero_compte,
                'type' => $transaction->type,
            ];

            $fileName = 'recu_' . $transaction->reference . '.json';
            $filePath = storage_path('app/recus/' . $fileName);

            // Créer le dossier s'il n'existe pas
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            file_put_contents($filePath, json_encode($recuData, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération du reçu', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function calculerFrais(float $montant): float
    {
        // Frais de transfert : 1% du montant minimum 100 FCFA
        return max($montant * 0.01, 100);
    }

    private function generateReference(): string
    {
        return 'PP' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 10));
    }

}
