<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;
use App\Models\User;
use App\Jobs\SendTransactionReceivedEmailJob;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function getTransactions(Compte $compte, array $filters = [])
    {
        $query = $compte->transactions();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        $transactions = $query->latest()->paginate(20);

        // Formater les transactions pour la liste
        $transactions->getCollection()->transform(function ($transaction) {
            $montantAffiche = match ($transaction->type) {
                'depot' => '+' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
                'transfert', 'paiement', 'retrait' => '-' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
                default => $transaction->montant . ' CFA'
            };

            // Déterminer le destinataire
            $destinataireInfo = $this->getDestinataireInfo($transaction);

            return [
                'libelle' => $transaction->libelle,
                'montant' => $montantAffiche,
                'expediteur' => $transaction->compte->user->nom . ' ' . $transaction->compte->user->prenom,
                'destinataire' => $destinataireInfo,
                'date' => $transaction->date_transaction->format('Y-m-d'),
                'reference' => $transaction->reference,
                'type' => $transaction->type,
                'statut' => $transaction->statut,
            ];
        });

        // Retourner les données avec le format de pagination personnalisé
        return [
            'data' => $transactions->items(),
            'pagination' => [
                'total_items' => $transactions->total(),
                'items_per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'has_previous' => $transactions->currentPage() > 1,
                'has_next' => $transactions->hasMorePages(),
                'links' => [
                    'first' => $transactions->url(1),
                    'previous' => $transactions->previousPageUrl(),
                    'next' => $transactions->nextPageUrl(),
                    'last' => $transactions->url($transactions->lastPage())
                ]
            ]
        ];
    }

    /**
     * Récupère les informations du destinataire
     */
    private function getDestinataireInfo(Transaction $transaction): array
    {
        $info = [
            'nom' => null,
            'numero' => null,
            'est_client' => false
        ];

        // Si c'est un transfert vers un autre client
        if ($transaction->type === 'transfert' && $transaction->numero_destinataire) {
            $destinataireUser = \App\Models\User::where('telephone', (string) $transaction->numero_destinataire)->first();

            if ($destinataireUser) {
                $info['nom'] = $destinataireUser->nom . ' ' . $destinataireUser->prenom;
                $info['numero'] = $transaction->numero_destinataire;
                $info['est_client'] = true;
            } else {
                $info['numero'] = $transaction->numero_destinataire;
                $info['est_client'] = false;
            }
        }
        // Si c'est un paiement marchand
        elseif ($transaction->type === 'paiement' && $transaction->code_marchand) {
            $info['code_marchand'] = $transaction->code_marchand;
            $info['numero'] = $transaction->code_marchand;
        }
        // Pour les dépôts et retraits
        elseif (in_array($transaction->type, ['depot', 'retrait'])) {
            $info['type_operation'] = $transaction->type === 'depot' ? 'Dépôt' : 'Retrait';
        }

        return $info;
    }

    public function createTransaction(Compte $compte, array $data)
    {
        DB::beginTransaction();

        try {
            $type = $data['type'];
            $montant = $data['montant_transaction'];

            // Vérifier le solde avant de créer la transaction
            $this->checkBalanceBeforeTransaction($compte, $type, $montant);

            $transaction = $compte->transactions()->create([
                'type' => $type,
                'montant' => $montant,
                'libelle' => $this->getLibelle($type),
                'numero_destinataire' => $data['numero_telephone'] ?? null,
                'code_marchand' => $data['code_marchand'] ?? null,
                'reference' => $this->generateReference(),
                'date_transaction' => now(),
                'statut' => 'validee',
            ]);

            $this->processTransactionLogic($compte, $transaction);

            DB::commit();

            // Envoyer l'email de notification après la transaction
            if ($transaction->type === 'transfert' && $transaction->numero_destinataire) {
                $this->envoyerEmailReceptionTransfert($transaction, $compte);
            }

            return $this->formatTransactionResponse($transaction, $compte);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function checkBalanceBeforeTransaction(Compte $compte, string $type, float $montant)
    {
        $soldeActuel = $compte->getSoldeAttribute();

        switch ($type) {
            case 'retrait':
                if ($soldeActuel < $montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce retrait. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($montant, 0, ',', ' ') . ' CFA');
                }
                break;

            case 'transfert':
                $frais = $this->calculerFrais($montant);
                if ($soldeActuel < $montant + $frais) {
                    throw new \Exception('Solde insuffisant pour effectuer ce transfert. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($montant + $frais, 0, ',', ' ') . ' CFA (incluant les frais)');
                }
                break;

            case 'paiement':
                if ($soldeActuel < $montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce paiement. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($montant, 0, ',', ' ') . ' CFA');
                }
                break;
        }
    }

    private function processTransactionLogic(Compte $compte, Transaction $transaction)
    {
        switch ($transaction->type) {
            case 'depot':
                // Pour les dépôts, on ne fait que créer la transaction (le solde est géré par l'observateur)
                break;

            case 'retrait':
                // Vérification déjà faite avant
                break;

            case 'transfert':
                // La création de la transaction destinataire et l'envoi d'email sont gérés par l'observateur
                break;

            case 'paiement':
                // Vérification déjà faite avant
                break;
        }

        // Vérification finale supprimée car la vérification est faite avant la création de la transaction
    }

    public function getTransaction(Compte $compte, Transaction $transaction)
    {
        // Vérifier que la transaction appartient au compte de l'utilisateur
        if ($transaction->compte_id !== $compte->id) {
            throw new \Exception('Transaction non trouvée pour ce compte');
        }

        return $transaction;
    }

    public function getTransactionByReference(Compte $compte, string $reference)
    {
        // Trouver la transaction par référence pour ce compte
        $transaction = $compte->transactions()->where('reference', $reference)->first();

        if (!$transaction) {
            throw new \Exception('Transaction non trouvée pour ce compte');
        }

        return $transaction;
    }

    private function getLibelle(string $type): string
    {
        return match ($type) {
            'depot' => 'Dépôt d\'argent',
            'retrait' => 'Retrait d\'argent',
            'transfert' => 'Transfert d\'argent',
            'paiement' => 'Paiement marchand',
            default => 'Transaction'
        };
    }

    private function generateReference(): string
    {
        return 'PP' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 10));
    }

    private function calculerFrais(float $montant): float
    {
        // Frais de transfert : 1% du montant minimum 100 FCFA
        return max($montant * 0.01, 100);
    }

    private function formatTransactionResponse(Transaction $transaction, Compte $compte): array
    {
        $montantAffiche = match ($transaction->type) {
            'depot' => '+' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
            'retrait', 'transfert', 'paiement' => '-' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
            default => number_format($transaction->montant, 0, ',', ' ') . ' CFA'
        };

        return [
            'libelle' => $transaction->libelle,
            'montant' => $montantAffiche,
            'client' => $transaction->numero_destinataire ?? $transaction->code_marchand,
            'expediteur' => $compte->user->nom . ' ' . $compte->user->prenom,
            'date' => $transaction->date_transaction->format('d/m/Y'),
            'reference' => $transaction->reference,
            'type' => $transaction->type,
        ];
    }

    private function envoyerEmailReceptionTransfert(Transaction $transaction, Compte $compteExpediteur): void
    {
        $numeroNormalise = str_replace('+221', '', $transaction->numero_destinataire);

        try {
            $destinataireUser = User::where('telephone', (string) $numeroNormalise)->first();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erreur lors de la recherche du destinataire pour email', [
                'numero' => $transaction->numero_destinataire,
                'numero_normalise' => $numeroNormalise,
                'error' => $e->getMessage()
            ]);
            return;
        }

        if (!$destinataireUser) {
            \Illuminate\Support\Facades\Log::warning('Destinataire non trouvé pour email', [
                'numero_normalise' => $numeroNormalise
            ]);
            return;
        }

        $emetteurUser = $compteExpediteur->user;

        $transactionData = [
            'destinataire_nom' => $destinataireUser->nom,
            'destinataire_prenom' => $destinataireUser->prenom,
            'montant' => $transaction->montant,
            'expediteur_nom' => $emetteurUser ? $emetteurUser->nom . ' ' . $emetteurUser->prenom : 'Client OM Pay',
            'expediteur_numero' => $compteExpediteur->numero_compte,
            'date_transaction' => $transaction->date_transaction->format('d/m/Y H:i'),
            'reference' => $transaction->reference,
            'transaction_destinataire_id' => null, // Pas encore créé
        ];

        $job = new SendTransactionReceivedEmailJob($destinataireUser->id, $transactionData);
        dispatch($job);
    }
}