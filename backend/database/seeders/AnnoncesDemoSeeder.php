<?php

namespace Database\Seeders;

use App\Enums\ModeReservation;
use App\Enums\StatutAnnonce;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\CategorieGeographique;
use App\Models\Annonces\PolitiqueAnnulation;
use App\Models\Utilisateurs\Profil;
use App\Models\Utilisateurs\User;
use App\Models\Utilisateurs\VerificationIdentite;
use Illuminate\Database\Seeder;

class AnnoncesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Annonce::query()->exists()) {
            return;
        }

        $hote = User::firstOrCreate(
            ['email' => 'hote.demo@accommity.ma'],
            [
                'nom' => 'Benali',
                'prenom' => 'Karim',
                'mot_de_passe' => 'Password123!',
                'telephone' => '0612345678',
                'est_hote' => true,
                'est_voyageur' => false,
                'est_actif' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'voyageur.demo@accommity.ma'],
            [
                'nom' => 'Alami',
                'prenom' => 'Yassine',
                'mot_de_passe' => 'Password123!',
                'telephone' => '0622334455',
                'est_hote' => false,
                'est_voyageur' => true,
                'est_actif' => true,
            ]
        );

        Profil::firstOrCreate(
            ['id_utilisateur' => $hote->getKey()],
            ['description' => 'Hôte passionné par l\'hospitalité marocaine.']
        );

        VerificationIdentite::factory()->validee()->create([
            'id_utilisateur' => $hote->getKey(),
        ]);

        $categories = CategorieGeographique::all()->keyBy('ville');
        $politiques = PolitiqueAnnulation::all()->keyBy('type_politique');

        if ($categories->isEmpty() || $politiques->isEmpty()) {
            return;
        }

        $images = [
            url('/images/annonces/log1.jpg'),
            url('/images/annonces/log2.jpg'),
            url('/images/annonces/log3.jpg'),
            url('/images/annonces/log4.jpg'),
            url('/images/annonces/log5.jpg'),
            url('/images/annonces/log6.png'),
            url('/images/annonces/log8.jpg'),
            url('/images/annonces/log9.png'),
            url('/images/annonces/log10.jpg'),
        ];

        $annonces = [
            [
                'ville' => 'Marrakech',
                'politique' => 'flexible',
                'titre' => 'Riad Al Jazira au cœur de la Médina',
                'description' => 'Authentique riad restauré avec patio arboré, bassin rafraîchissant et terrasse panoramique sur la Koutoubia.',
                'type_logement' => 'riad',
                'adresse' => 'Derb Sidi Bouloukate, Médina, Marrakech',
                'capacite' => 6,
                'tarif_nuit' => 1450,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, piscine, terrasse, petit-déjeuner',
                'photos' => [$images[0], $images[1], $images[2]],
            ],
            [
                'ville' => 'Marrakech',
                'politique' => 'moderee',
                'titre' => 'Villa Palmeraie avec piscine privée',
                'description' => 'Spacieuse villa contemporaine entourée d\'oliviers, idéale pour les familles en quête de sérénité.',
                'type_logement' => 'villa',
                'adresse' => 'Circuit de la Palmeraie, Marrakech',
                'capacite' => 8,
                'tarif_nuit' => 3200,
                'mode_reservation' => ModeReservation::SUR_DEMANDE,
                'equipements' => 'wifi, climatisation, piscine, parking, cuisine, terrasse',
                'photos' => [$images[3], $images[4], $images[5]],
            ],
            [
                'ville' => 'Fès',
                'politique' => 'flexible',
                'titre' => 'Dar Zellige à Fès El-Bali',
                'description' => 'Demeure historique ornée de zelliges traditionnels et de cèdre sculpté, à deux pas de Bou Inania.',
                'type_logement' => 'riad',
                'adresse' => 'Talaa Kebira, Fès El-Bali',
                'capacite' => 4,
                'tarif_nuit' => 980,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, terrasse, petit-déjeuner',
                'photos' => [$images[6], $images[7], $images[8]],
            ],
            [
                'ville' => 'Tanger',
                'politique' => 'moderee',
                'titre' => 'Appartement vue détroit à Malabata',
                'description' => 'Appartement lumineux face à la baie de Tanger, proche de la corniche et de la gare TGV.',
                'type_logement' => 'appartement',
                'adresse' => 'Boulevard Mohammed VI, Malabata, Tanger',
                'capacite' => 4,
                'tarif_nuit' => 850,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, parking, cuisine, terrasse',
                'photos' => [$images[1], $images[3]],
            ],
            [
                'ville' => 'Chefchaouen',
                'politique' => 'flexible',
                'titre' => 'Maison bleue perchée sur les hauteurs',
                'description' => 'Charmante maison traditionnelle aux murs indigo avec vue imprenable sur les montagnes du Rif.',
                'type_logement' => 'maison',
                'adresse' => 'Quartier Al-Andalus, Chefchaouen',
                'capacite' => 5,
                'tarif_nuit' => 720,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, cuisine, terrasse, petit-déjeuner',
                'photos' => [$images[2], $images[6], $images[0]],
            ],
            [
                'ville' => 'Essaouira',
                'politique' => 'moderee',
                'titre' => 'Maison d\'hôtes face aux remparts',
                'description' => 'Baignée de lumière atlantique, cette maison de caractère offre un accès direct à la plage et aux souks.',
                'type_logement' => 'maison',
                'adresse' => 'Rue Skala, Médina, Essaouira',
                'capacite' => 6,
                'tarif_nuit' => 1150,
                'mode_reservation' => ModeReservation::SUR_DEMANDE,
                'equipements' => 'wifi, cuisine, terrasse, petit-déjeuner',
                'photos' => [$images[4], $images[7], $images[8]],
            ],
            [
                'ville' => 'Casablanca',
                'politique' => 'stricte',
                'titre' => 'Studio design à Anfa Supérieur',
                'description' => 'Studio moderne tout équipé pour séjours d\'affaires ou escapades urbaines à Casablanca.',
                'type_logement' => 'studio',
                'adresse' => 'Boulevard d\'Anfa, Casablanca',
                'capacite' => 2,
                'tarif_nuit' => 590,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, parking, cuisine',
                'photos' => [$images[5], $images[1]],
            ],
            [
                'ville' => 'Rabat',
                'politique' => 'flexible',
                'titre' => 'Appartement calme aux Oudayas',
                'description' => 'À proximité immédiate de la Kasbah des Oudayas et du Bouregreg, idéal pour découvrir la capitale.',
                'type_logement' => 'appartement',
                'adresse' => 'Quartier Hassan, Rabat',
                'capacite' => 3,
                'tarif_nuit' => 680,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, cuisine',
                'photos' => [$images[8], $images[2]],
            ],
            [
                'ville' => 'Agadir',
                'politique' => 'moderee',
                'titre' => 'Villa océan à Taghazout Bay',
                'description' => 'Villa moderne avec piscine à débordement surplombant les spots de surf de la côte atlantique.',
                'type_logement' => 'villa',
                'adresse' => 'Route de Taghazout, Agadir',
                'capacite' => 10,
                'tarif_nuit' => 3500,
                'mode_reservation' => ModeReservation::SUR_DEMANDE,
                'equipements' => 'wifi, climatisation, piscine, parking, cuisine, terrasse',
                'photos' => [$images[0], $images[3], $images[4], $images[5]],
            ],
            [
                'ville' => 'Marrakech',
                'politique' => 'flexible',
                'titre' => 'Chambre privée dans un riad fleuri',
                'description' => 'Suite intime avec salle de bain en tadelakt et accès libre au patio et à la terrasse.',
                'type_logement' => 'chambre',
                'adresse' => 'Riad Zitoun Jdid, Marrakech',
                'capacite' => 2,
                'tarif_nuit' => 380,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, petit-déjeuner',
                'photos' => [$images[7]],
            ],
            [
                'ville' => 'Tanger',
                'politique' => 'stricte',
                'titre' => 'Villa sur les collines de Vieille Montagne',
                'description' => 'Propriété d\'exception nichée dans la verdure avec jardin privé et vue sur l\'Atlantique.',
                'type_logement' => 'villa',
                'adresse' => 'Jbel Kebir, Tanger',
                'capacite' => 7,
                'tarif_nuit' => 2400,
                'mode_reservation' => ModeReservation::SUR_DEMANDE,
                'equipements' => 'wifi, climatisation, piscine, parking, cuisine, terrasse',
                'photos' => [$images[3], $images[6], $images[8]],
            ],
            [
                'ville' => 'Fès',
                'politique' => 'flexible',
                'titre' => 'Studio lumineux Ville Nouvelle',
                'description' => 'Confort moderne et emplacement central près des cafés, restaurants et commerces.',
                'type_logement' => 'studio',
                'adresse' => 'Avenue Hassan II, Fès',
                'capacite' => 2,
                'tarif_nuit' => 350,
                'mode_reservation' => ModeReservation::INSTANTANEE,
                'equipements' => 'wifi, climatisation, cuisine',
                'photos' => [$images[1]],
            ],
        ];

        foreach ($annonces as $item) {
            $categorie = $categories->get($item['ville']) ?? $categories->first();
            $politique = $politiques->get($item['politique']) ?? $politiques->first();

            Annonce::create([
                'id_hote' => $hote->getKey(),
                'id_categorie' => $categorie->getKey(),
                'id_politique' => $politique->getKey(),
                'titre' => $item['titre'],
                'description' => $item['description'],
                'photo_url' => $item['photos'],
                'type_logement' => $item['type_logement'],
                'adresse' => $item['adresse'],
                'capacite' => $item['capacite'],
                'tarif_nuit' => $item['tarif_nuit'],
                'mode_reservation' => $item['mode_reservation'],
                'statut' => StatutAnnonce::PUBLIEE,
                'equipements' => $item['equipements'],
                'reglement_interieur' => 'Respect du voisinage après 22h. Non-fumeur à l\'intérieur.',
            ]);
        }

        // 2 annonces non publiées pour vérifier le filtrage "publiee uniquement"
        $premiereCategorie = $categories->first();
        $premierePolitique = $politiques->first();

        Annonce::create([
            'id_hote' => $hote->getKey(),
            'id_categorie' => $premiereCategorie->getKey(),
            'id_politique' => $premierePolitique->getKey(),
            'titre' => 'Riad en cours de validation',
            'description' => 'Annonce en attente de modération par l\'équipe.',
            'photo_url' => [$images[0]],
            'type_logement' => 'riad',
            'adresse' => 'Médina',
            'capacite' => 4,
            'tarif_nuit' => 900,
            'mode_reservation' => ModeReservation::INSTANTANEE,
            'statut' => StatutAnnonce::EN_ATTENTE,
            'equipements' => 'wifi, climatisation',
        ]);

        Annonce::create([
            'id_hote' => $hote->getKey(),
            'id_categorie' => $premiereCategorie->getKey(),
            'id_politique' => $premierePolitique->getKey(),
            'titre' => 'Appartement temporairement suspendu',
            'description' => 'Annonce suspendue non visible dans le catalogue public.',
            'photo_url' => [$images[2]],
            'type_logement' => 'appartement',
            'adresse' => 'Centre-ville',
            'capacite' => 3,
            'tarif_nuit' => 600,
            'mode_reservation' => ModeReservation::SUR_DEMANDE,
            'statut' => StatutAnnonce::SUSPENDUE,
            'equipements' => 'wifi',
        ]);
    }
}
