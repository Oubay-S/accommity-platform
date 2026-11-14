<?php

namespace Database\Seeders;

use App\Models\Annonces\PolitiqueAnnulation;
use Illuminate\Database\Seeder;

class PolitiquesAnnulationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('hebergement.politiques') as $type => $p) {
            PolitiqueAnnulation::updateOrCreate(['type_politique' => $type], $p);
        }
    }
}
