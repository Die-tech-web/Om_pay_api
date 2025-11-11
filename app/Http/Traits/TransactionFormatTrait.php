<?php

namespace App\Traits;

use App\Models\Transaction;
use App\Models\User;

trait TransactionFormatTrait
{
    /**
     * Formate les informations du destinataire d'une transaction
     */
    protected function formaterDestinataire(Transaction $transaction): array
    {
        $info = [
            'nom' => null,
            'numero' => null,
            'est_client' => false
        ];

        switch ($transaction->type) {
            case 'transfert':
                if ($transaction->numero_destinataire) {
                    $destinataireUser = User::where('telephone', $transaction->numero_destinataire)->first();
                    
                    if ($destinataireUser) {
                        $info['nom'] = $destinataireUser->nom . ' ' . $destinataireUser->prenom;
                        $info['numero'] = $transaction->numero_destinataire;
                        $info['est_client'] = true;
                    } else {
                        $info['numero'] = $transaction->numero_destinataire;
                        $info['est_client'] = false;
                    }
                }
                break;

            case 'paiement':
                if ($transaction->code_marchand) {
                    $info['code_marchand'] = $transaction->code_marchand;
                    $info['numero'] = $transaction->code_marchand;
                }
                break;

            case 'depot':
            case 'retrait':
                $info['type_operation'] = $transaction->type === 'depot' ? 'Dépôt' : 'Retrait';
                break;
        }

        return $info;
    }

    /**
     * Formate le montant d'une transaction
     */
    protected function formaterMontant(Transaction $transaction): string
    {
        return match ($transaction->type) {
            'depot' => '+' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
            'frais' => '-' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
            'transfert', 'paiement', 'retrait' => '-' . number_format($transaction->montant, 0, ',', ' ') . ' CFA',
            default => $transaction->montant . ' CFA'
        };
    }

    /**
     * Formate une transaction pour l'affichage en liste
     */
    protected function formaterTransactionListe(Transaction $transaction): array
    {
        return [
            'libelle' => $transaction->libelle,
            'montant' => $this->formaterMontant($transaction),
            'destinataire' => $this->formaterDestinataire($transaction),
            'date' => $transaction->date_transaction->format('Y-m-d'),
            'type' => $transaction->type,
            'statut' => $transaction->statut,
        ];
    }

    /**
     * Formate une transaction pour les réponses d'API
     */
    protected function formaterTransactionReponse(Transaction $transaction): array
    {
        return [
            'libelle' => $transaction->libelle,
            'montant' => $this->formaterMontant($transaction),
            'destinataire' => $this->formaterDestinataire($transaction),
            'date' => $transaction->date_transaction->format('d/m/Y'),
            'reference' => $transaction->reference,
            'type' => $transaction->type,
            'statut' => $transaction->statut,
        ];
    }

    /**
     * Formate la réponse paginée
     */
    protected function formaterReponsePaginee($transactions): array
    {
        return [
            'data' => $transactions->items(),
            'pagination' => [
                'total_items' => $transactions->total(),
                'items_per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'has_previous' => $transactions->hasPreviousPage(),
                'has_next' => $transactions->hasNextPage(),
                'links' => [
                    'first' => $transactions->url(1),
                    'previous' => $transactions->previousPageUrl(),
                    'next' => $transactions->nextPageUrl(),
                    'last' => $transactions->url($transactions->lastPage())
                ]
            ]
        ];
    }
}