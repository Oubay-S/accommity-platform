<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Annonces\PolitiqueAnnulation;

class PolitiquesAnnulationSeeder extends Seeder
{
    public function run(): void
    {
        $politiques = [
            ['nom' => 'Flexible', 'description' => 'Remboursement intégral jusqu\'à 24h avant l\'arrivée', 'delai_heures' => 24, 'pourcentage_remboursement' => 100],
            ['nom' => 'Modérée', 'description' => 'Remboursement intégral jusqu\'à 5 jours avant l\'arrivée', 'delai_heures' => 120, 'pourcentage_remboursement' => 100],
            ['nom' => 'Stricte', 'description' => 'Remboursement de 50% jusqu\'à 7 jours avant l\'arrivée', 'delai_heures' => 168, 'pourcentage_remboursement' => 50],
        ];

        foreach ($politiques as $pol) {
            PolitiqueAnnulation::firstOrCreate(['nom' => $pol['nom']], $pol);
        }
    }
}
