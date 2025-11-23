<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionReceivedNotification extends Notification
{

    protected $transactionData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $transactionData)
    {
        $this->transactionData = $transactionData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('OM Pay - Vous avez reçu un transfert')
                    ->greeting('Bonjour ' . $this->transactionData['destinataire_nom'] . ' ' . $this->transactionData['destinataire_prenom'] . '!')
                    ->line('Vous avez reçu un transfert sur votre compte OM Pay.')
                    ->line('💰 **Montant reçu** : ' . number_format($this->transactionData['montant'], 0, ',', ' ') . ' CFA')
                    ->line('👤 **Expéditeur** : ' . $this->transactionData['expediteur_nom'])
                    ->line('📱 **Numéro expéditeur** : ' . $this->transactionData['expediteur_numero'])
                    ->line('📅 **Date** : ' . $this->transactionData['date_transaction'])
                    ->line('🔢 **Référence** : ' . $this->transactionData['reference'])
                    ->action('Voir mes transactions', url('/transactions'))
                    ->line('Votre solde a été mis à jour automatiquement.')
                    ->salutation('Cordialement,')
                    ->salutation('L\'équipe OM Pay');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}