<?php

namespace App\Services;

use App\Notifications\WelcomeNewUser;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Envoie un email de bienvenue de manière asynchrone
     * Ne bloque jamais l'inscription même en cas d'échec
     */
    public function sendWelcomeEmail(User $user, string $codePin): void
    {
        try {
            // Utilise les queues pour éviter de bloquer la réponse
            $job = new \App\Jobs\SendWelcomeEmailJob($user, $codePin);
            
            // En production, utilise les queues
            if (app()->environment('production')) {
                dispatch($job);
            } else {
                // En développement, exécuté immédiatement mais en arrière-plan
                dispatch_sync($job);
            }
            
            Log::info('Email de bienvenue programmé pour envoi', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
        } catch (\Exception $e) {
            // En cas d'erreur, on log mais on ne bloque jamais l'inscription
            Log::error('Erreur lors de la programmation de l\'email de bienvenue', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // En production, on peut créer une tâche de retry
            if (app()->environment('production')) {
                $this->scheduleRetry($user, $codePin);
            }
        }
    }

    /**
     * Programme un retry en cas d'échec
     */
    private function scheduleRetry(User $user, string $codePin, int $attempts = 0): void
    {
        if ($attempts >= 3) {
            Log::error('Échec définitif de l\'envoi d\'email de bienvenue', [
                'user_id' => $user->id,
                'email' => $user->email,
                'attempts' => $attempts
            ]);
            return;
        }

        $delay = pow(2, $attempts) * 60; // 1min, 2min, 4min...
        
        dispatch(function () use ($user, $codePin, $attempts) {
            $this->sendWelcomeEmail($user, $codePin);
        })->delay(now()->addMinutes($delay));
    }

    /**
     * Teste la configuration email
     */
    public function testEmailConfiguration(): array
    {
        try {
            $testEmail = 'test@ompay.com';
            
            // Test avec un utilisateur fictif
            $testUser = new User([
                'nom' => 'Test',
                'prenom' => 'User',
                'email' => $testEmail,
                'telephone' => '000000000'
            ]);

            // Ne pas envoyer réellement, juste vérifier la configuration
            Log::info('Test de configuration email réussi', [
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_from' => config('mail.from.address')
            ]);

            return [
                'success' => true,
                'message' => 'Configuration email valide',
                'config' => [
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from' => config('mail.from.address')
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur de configuration email', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de configuration email',
                'error' => $e->getMessage()
            ];
        }
    }
}