<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WelcomeNewUser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $codePin;

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
    public function __construct(User $user, string $codePin)
    {
        $this->user = $user;
        $this->codePin = $codePin;
        $this->onQueue('default'); // File d'attente par défaut
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Début de l\'envoi de l\'email de bienvenue', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'attempt' => $this->attempts()
            ]);

            // Envoyer la notification
            $this->user->notify(new WelcomeNewUser([
                'nom' => $this->user->nom,
                'prenom' => $this->user->prenom,
                'telephone' => $this->user->telephone,
                'email' => $this->user->email,
                'code_pin' => $this->codePin
            ]));

            Log::info('Email de bienvenue envoyé avec succès', [
                'user_id' => $this->user->id,
                'email' => $this->user->email
            ]);

        } catch (\Exception $e) {
            Log::error('Échec de l\'envoi de l\'email de bienvenue', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Relancer l'exception pour que Laravel puisse gérer les retries
            throw $e;
        }
    }

    /**
     * Gère l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec définitif de l\'envoi de l\'email de bienvenue après tous les retries', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Ici on pourrait ajouter des actions supplémentaires :
        // - Notifier un administrateur
        // - Enregistrer dans une table de suivi
        // - Envoyer via un autre service email
    }
}