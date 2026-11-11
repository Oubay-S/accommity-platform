<?php

return [
    'commission_rate' => 0.12, // Frais de service plateforme (12%)
    'delai_reponse_hote_heures' => 24, // RG15
    'delai_versement_hote_heures' => 24, // 24h après check-in
    'politiques' => [
        'flexible' => ['heures' => 24, 'remboursement' => 100],
        'moderee' => ['heures' => 120, 'remboursement' => 100],
        'stricte' => ['heures' => 168, 'remboursement' => 50],
    ]
];
