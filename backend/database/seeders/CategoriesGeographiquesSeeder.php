<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Annonces\CategorieGeographique;

class CategoriesGeographiquesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Bord de mer', 'description' => 'Logements proches des plages'],
            ['nom' => 'Montagne', 'description' => 'Chalets et hébergements en altitude'],
            ['nom' => 'Centre-ville', 'description' => 'Appartements au cœur des métropoles'],
            ['nom' => 'Campagne', 'description' => 'Gîtes calmes et reposants'],
        ];

        foreach ($categories as $cat) {
            CategorieGeographique::firstOrCreate(['nom' => $cat['nom']], $cat);
        }
    }
}
