<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategoriesGeographiquesSeeder::class,
            PolitiquesAnnulationSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                AnnoncesDemoSeeder::class,
                QaDemoSeeder::class,
            ]);
        }
    }
}
