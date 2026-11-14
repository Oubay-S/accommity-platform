<?php

return [
    'commission_rate' => 0.12, // Frais de service plateforme (12%)
    'delai_reponse_hote_heures' => 24, // RG15
    'delai_versement_hote_heures' => 24, // 24h après check-in
    'politiques' => [
        'flexible' => [
            'delai_remb_total' => 24,
            'delai_remb_partiel' => 0,
            'taux_remb_partiel' => 0,
            'description' => "Remboursement intégral jusqu'à 24 h avant l'arrivée.",
        ],
        'moderee' => [
            'delai_remb_total' => 120,
            'delai_remb_partiel' => 24,
            'taux_remb_partiel' => 50,
            'description' => "Remboursement intégral jusqu'à 5 jours avant l'arrivée, 50 % jusqu'à 24 h avant.",
        ],
        'stricte' => [
            'delai_remb_total' => 336,
            'delai_remb_partiel' => 168,
            'taux_remb_partiel' => 50,
            'description' => "Remboursement intégral jusqu'à 14 jours avant l'arrivée, 50 % jusqu'à 7 jours avant.",
        ],
    ],
];
