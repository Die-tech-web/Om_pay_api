<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Marchand;

class MarchandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marchands = [
            [
                'nom' => 'Café du Plateau',
                'code_marchand' => 'CAF001',
                'adresse' => 'Plateau, Dakar',
                'telephone' => '771234567',
                'email' => 'contact@cafeduplateau.sn',
                'type_commerce' => 'Restauration',
                'actif' => true,
            ],
            [
                'nom' => 'Librairie des Aimables',
                'code_marchand' => 'LIB002',
                'adresse' => 'Médina, Dakar',
                'telephone' => '772345678',
                'email' => 'info@librairiedesaimables.sn',
                'type_commerce' => 'Éducation',
                'actif' => true,
            ],
            [
                'nom' => 'Pharmacie Beneficial',
                'code_marchand' => 'PHA003',
                'adresse' => 'Sacré-Cœur, Dakar',
                'telephone' => '773456789',
                'email' => 'pharma@beneficial.sn',
                'type_commerce' => 'Santé',
                'actif' => true,
            ],
            [
                'nom' => 'Boutique J Appong',
                'code_marchand' => 'BOU004',
                'adresse' => 'Sandaga, Dakar',
                'telephone' => '774567890',
                'email' => 'boutique@jappong.sn',
                'type_commerce' => 'Vêtements',
                'actif' => true,
            ],
            [
                'nom' => 'Electronique Shop',
                'code_marchand' => 'ELE005',
                'adresse' => 'Colobane, Dakar',
                'telephone' => '775678901',
                'email' => 'vente@electroniqueshop.sn',
                'type_commerce' => 'Technologie',
                'actif' => true,
            ],
            [
                'nom' => 'Restaurant Korite',
                'code_marchand' => 'RES006',
                'adresse' => 'Fann-Hock, Dakar',
                'telephone' => '776789012',
                'email' => 'contact@restaurantkorite.sn',
                'type_commerce' => 'Restauration',
                'actif' => true,
            ],
            [
                'nom' => 'Salon de Coiffure Belle',
                'code_marchand' => 'COI007',
                'adresse' => 'Yoff, Dakar',
                'telephone' => '777890123',
                'email' => 'rendezvous@salonbelle.sn',
                'type_commerce' => 'Beauté',
                'actif' => true,
            ],
            [
                'nom' => 'Station Service Total',
                'code_marchand' => 'STA008',
                'adresse' => 'Pikine, Dakar',
                'telephone' => '778901234',
                'email' => 'pikine@total.sn',
                'type_commerce' => 'Énergie',
                'actif' => true,
            ],
            [
                'nom' => 'Supermarché Aïwa',
                'code_marchand' => 'SUP009',
                'adresse' => 'Grand Yoff, Dakar',
                'telephone' => '779012345',
                'email' => 'grand.yoff@aiwa.sn',
                'type_commerce' => 'Alimentation',
                'actif' => true,
            ],
            [
                'nom' => 'Imprimerie Moderne',
                'code_marchand' => 'IMP010',
                'adresse' => 'Cambérène, Dakar',
                'telephone' => '770123456',
                'email' => 'imprimerie@moderne.sn',
                'type_commerce' => 'Services',
                'actif' => true,
            ],
        ];

        foreach ($marchands as $marchand) {
            Marchand::firstOrCreate(
                ['code_marchand' => $marchand['code_marchand']],
                $marchand
            );
        }

        $this->command->info('Marchands créés avec succès : ' . count($marchands) . ' entrées');
    }
}
