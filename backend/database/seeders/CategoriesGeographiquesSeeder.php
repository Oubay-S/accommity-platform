<?php

namespace Database\Seeders;

use App\Models\Annonces\CategorieGeographique;
use Illuminate\Database\Seeder;

class CategoriesGeographiquesSeeder extends Seeder
{
    public function run(): void
    {
        $villes = [
            ['ville' => 'Marrakech', 'region' => 'Marrakech-Safi'],
            ['ville' => 'Fès', 'region' => 'Fès-Meknès'],
            ['ville' => 'Tanger', 'region' => 'Tanger-Tétouan-Al Hoceïma'],
            ['ville' => 'Casablanca', 'region' => 'Casablanca-Settat'],
            ['ville' => 'Rabat', 'region' => 'Rabat-Salé-Kénitra'],
            ['ville' => 'Essaouira', 'region' => 'Marrakech-Safi'],
            ['ville' => 'Agadir', 'region' => 'Souss-Massa'],
            ['ville' => 'Chefchaouen', 'region' => 'Tanger-Tétouan-Al Hoceïma'],
        ];

        foreach ($villes as $v) {
            CategorieGeographique::firstOrCreate(['ville' => $v['ville']], $v + ['pays' => 'Maroc']);
        }
    }
}
