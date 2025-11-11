<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;
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
            $destinataireUser = \App\Models\User::where('telephone', $transaction->numero_destinataire)->first();

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

            // Vérifier que le compte appartient à l'utilisateur
            if ($compte->id_client !== auth()->id()) {
                throw new \Exception('Accès non autorisé à ce compte');
            }

            $transaction = $compte->transactions()->create([
                'type' => $type,
                'montant' => $data['montant_transaction'],
                'libelle' => $this->getLibelle($type),
                'numero_destinataire' => $data['numero_telephone'] ?? null,
                'code_marchand' => $data['code_marchand'] ?? null,
                'reference' => $this->generateReference(),
                'date_transaction' => now(),
                'statut' => 'validee',
            ]);

            $this->processTransactionLogic($compte, $transaction);

            DB::commit();

            return $this->formatTransactionResponse($transaction, $compte);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processTransactionLogic(Compte $compte, Transaction $transaction)
    {
        $soldeActuel = $compte->getSoldeAttribute();

        switch ($transaction->type) {
            case 'depot':
                // Pour les dépôts, on ne fait que créer la transaction (le solde est géré par l'observateur)
                break;

            case 'retrait':
                if ($soldeActuel < $transaction->montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce retrait. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($transaction->montant, 0, ',', ' ') . ' CFA');
                }
                break;

            case 'transfert':
                // Vérifier que le solde ne deviendra pas négatif
                if ($soldeActuel < $transaction->montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce transfert. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($transaction->montant, 0, ',', ' ') . ' CFA');
                }

                // Chercher le compte destinataire (optionnel)
                $destinataireUser = \App\Models\User::where('telephone', $transaction->numero_destinataire)->first();
                $compteDestinataire = $destinataireUser ? $destinataireUser->comptes()->first() : null;

                // Vérifier qu'on ne transfère pas vers le même compte (si le destinataire existe)
                if ($compteDestinataire && $compteDestinataire->id === $compte->id) {
                    throw new \Exception('Impossible de transférer vers le même compte');
                }

                // Créer la transaction pour le destinataire seulement s'il existe
                if ($compteDestinataire) {
                    $compteDestinataire->transactions()->create([
                        'type' => 'depot',
                        'montant' => $transaction->montant,
                        'libelle' => 'Transfert reçu de ' . ($compte->user->nom ?? 'Client'),
                        'description' => 'Transfert reçu de ' . $compte->numero_compte,
                        'reference' => $this->generateReference(),
                        'date_transaction' => now(),
                        'statut' => 'validee',
                    ]);
                }
                break;

            case 'paiement':
                if ($soldeActuel < $transaction->montant) {
                    throw new \Exception('Solde insuffisant pour effectuer ce paiement. Solde actuel: ' .
                        number_format($soldeActuel, 0, ',', ' ') . ' CFA, montant requis: ' .
                        number_format($transaction->montant, 0, ',', ' ') . ' CFA');
                }
                break;
        }

        // Vérification finale : s'assurer que le solde n'est pas négatif après la transaction
        $nouveauSolde = $compte->getSoldeAttribute();
        if ($nouveauSolde < 0) {
            throw new \Exception('Erreur: le solde ne peut pas être négatif après la transaction');
        }
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
            'date' => $transaction->date_transaction->format('d/m/Y'),
            'reference' => $transaction->reference,
            'type' => $transaction->type,
        ];
    }
}