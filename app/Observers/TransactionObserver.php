<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Models\User;
use App\Events\TransactionCreated;

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

        match ($transaction->type) {
            'transfert' => $this->traiterTransfert($transaction),
            'paiement' => $this->traiterPaiement($transaction),
            'retrait' => $this->traiterRetrait($transaction),
            'depot' => $this->traiterDepot($transaction),
            default => null
        };

        // Vérification finale du solde
        $this->verifierSoldeFinal($transaction->compte);
    }

    /**
     * Traiter un transfert
     */
    private function traiterTransfert(Transaction $transaction): void
    {
        $frais = $this->calculerFrais($transaction->montant);
        
        // Créer la transaction pour le destinataire
        $this->creerTransactionDestinataire($transaction);
        
        // Créer la transaction de frais si nécessaire
        if ($frais > 0) {
            $this->creerTransactionFrais($transaction, $frais);
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

        $destinataireUser = User::where('telephone', $transactionExpediteur->numero_destinataire)->first();
        
        if (!$destinataireUser) {
            return;
        }

        $compteDestinataire = $destinataireUser->comptes()->first();
        
        if (!$compteDestinataire) {
            return;
        }

        // Vérifier qu'on ne transfère pas vers le même compte
        if ($compteDestinataire->id === $compteExpediteur->id) {
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
    }

    /**
     * Créer la transaction de frais pour un transfert
     */
    private function creerTransactionFrais(Transaction $transaction, float $frais): void
    {
        $destinataireUser = User::where('telephone', $transaction->numero_destinataire)->first();
        
        if (!$destinataireUser) {
            return;
        }

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
        $nouveauSolde = $compte->getSoldeAttribute();
        
        if ($nouveauSolde < 0) {
            // Log de sécurité - en production, on pourrait envoyer une alerte
            \Illuminate\Support\Facades\Log::warning('Solde négatif détecté après transaction', [
                'compte_id' => $compte->id,
                'solde' => $nouveauSolde
            ]);
        }
    }

    /**
     * Générer le reçu
     */
    private function generateRecu(Transaction $transaction): void
    {
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
