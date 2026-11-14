<?php

namespace Database\Seeders;

use App\Models\Annonces\PolitiqueAnnulation;
use Illuminate\Database\Seeder;

class PolitiquesAnnulationSeeder extends Seeder
{
    public function run(): void
    {
        $politiques = [
            [
                'type_politique' => 'flexible',
                'delai_remb_total' => 24,
                'delai_remb_partiel' => 0,
                'taux_remb_partiel' => 0,
                'description' => "Remboursement intégral jusqu'à 24 h avant l'arrivée.",
            ],
            [
                'type_politique' => 'moderee',
                'delai_remb_total' => 120,
                'delai_remb_partiel' => 24,
                'taux_remb_partiel' => 50,
                'description' => "Remboursement intégral jusqu'à 5 jours avant l'arrivée, 50 % jusqu'à 24 h avant.",
            ],
            [
                'type_politique' => 'stricte',
                'delai_remb_total' => 336,
                'delai_remb_partiel' => 168,
                'taux_remb_partiel' => 50,
                'description' => "Remboursement intégral jusqu'à 14 jours avant l'arrivée, 50 % jusqu'à 7 jours avant.",
            ],
        ];

        foreach ($politiques as $p) {
            PolitiqueAnnulation::firstOrCreate(['type_politique' => $p['type_politique']], $p);
        }
    }
}
