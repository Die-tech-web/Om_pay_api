<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Transaction;
use App\Notifications\TransactionReceivedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTransactionReceivedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $transactionData;

    /**
     * Nombre de tentatives en cas d'échec
     */
    public $tries = 3;

    /**
     * Délai entre les tentatives (en secondes)
     */
    public $backoff = [60, 120, 240]; // 1min, 2min, 4min

    /**
     * Create a new job instance.
     */
    public function __construct(string $userId, array $transactionData)
    {
        $this->userId = $userId;
        $this->transactionData = $transactionData;
        $this->onQueue('default'); // File d'attente par défaut
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Récupérer l'utilisateur par ID
            $user = User::find($this->userId);
            if (!$user) {
                Log::error('Job annulé : utilisateur non trouvé', [
                    'user_id' => $this->userId,
                    'attempt' => $this->attempts()
                ]);
                return;
            }

            Log::info('Début de l\'envoi de l\'email de réception de transfert', [
                'user_id' => $user->id,
                'email' => $user->email,
                'montant' => $this->transactionData['montant'],
                'attempt' => $this->attempts()
            ]);

            // Vérifier que la transaction destinataire existe toujours (au cas où elle aurait été annulée)
            if (isset($this->transactionData['transaction_destinataire_id'])) {
                $transactionDestinataire = Transaction::find($this->transactionData['transaction_destinataire_id']);
                if (!$transactionDestinataire || $transactionDestinataire->statut !== 'validee') {
                    Log::info('Transaction destinataire annulée, email non envoyé', [
                        'user_id' => $user->id,
                        'transaction_id' => $this->transactionData['transaction_destinataire_id']
                    ]);
                    return;
                }
            }

            // Envoyer la notification
            $user->notify(new TransactionReceivedNotification($this->transactionData));

            Log::info('Email de réception de transfert envoyé avec succès', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de réception de transfert, mais job continué', [
                'user_id' => $this->userId,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Ne pas relancer l'exception pour éviter l'échec du job
        }
    }

    /**
     * Gère l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec définitif de l\'envoi de l\'email de réception de transfert après tous les retries', [
            'user_id' => $this->userId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Ici on pourrait ajouter des actions supplémentaires :
        // - Notifier un administrateur
        // - Enregistrer dans une table de suivi
        // - Envoyer via un autre service email
    }
}