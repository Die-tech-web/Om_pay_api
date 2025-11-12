<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer le client OAuth Passport si nécessaire
        $this->createPassportClient();

        // Exécuter le seeder pour les soldes initiaux
        $this->call([
            MarchandSeeder::class,
            TestComptesSeeder::class,
            SoldeInitialSeeder::class,

        ]);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }

    /**
     * Créer le client OAuth Passport pour les tokens personnels
     */
    private function createPassportClient(): void
    {
        if (\Laravel\Passport\Client::where('personal_access_client', true)->exists()) {
            $this->command->info('Client OAuth Passport déjà existant.');
            return;
        }

        $this->command->info('Création du client OAuth Passport...');

        $client = new \Laravel\Passport\Client();
        $client->id = (string) \Illuminate\Support\Str::uuid();
        $client->user_id = null;
        $client->name = 'Laravel Personal Access Client';
        $client->secret = \Illuminate\Support\Str::random(40);
        $client->redirect = 'http://localhost';
        $client->personal_access_client = true;
        $client->password_client = false;
        $client->revoked = false;
        $client->save();

        $personalAccessClient = new \Laravel\Passport\PersonalAccessClient();
        $personalAccessClient->id = (string) \Illuminate\Support\Str::uuid();
        $personalAccessClient->client_id = $client->id;
        $personalAccessClient->save();

        $this->command->info('Client OAuth Passport créé avec succès.');
    }
}
