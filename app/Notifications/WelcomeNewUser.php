<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNewUser extends Notification
{
    use Queueable;

    protected $userData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $userData)
    {
        $this->userData = $userData;
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
                    ->subject('Bienvenue sur OM Pay - Vos identifiants de connexion')
                    ->greeting('Bonjour ' . $this->userData['prenom'] . ' ' . $this->userData['nom'] . '!')
                    ->line('Bienvenue sur OM Pay! Votre compte a été créé avec succès.')
                    ->line('Voici vos identifiants de connexion :')
                    ->line('📱 Téléphone : ' . $this->userData['telephone'])
                    ->line('📧 Email : ' . $this->userData['email'])
                    ->line('🔐 Code PIN : ' . $this->userData['code_pin'])
                    ->line('💰 Votre compte dispose déjà d\'un solde initial de 50 000 F CFA.')
                    ->line('Vous pouvez maintenant vous connecter à votre compte et commencer à utiliser nos services.')
                    ->action('Se connecter', url('/login'))
                    ->line('Merci de rejoindre OM Pay!')
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
