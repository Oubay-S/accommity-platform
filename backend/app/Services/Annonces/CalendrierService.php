<?php

namespace App\Services\Annonces;

use App\Enums\StatutReservation;
use App\Exceptions\ApiException;
use App\Models\Annonces\Annonce;
use App\Models\Annonces\Calendrier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calendrier hôte (RG08, RG09).
 *
 * Convention :
 *  - un blocage manuel couvre les jours [date_debut, date_fin] inclus ;
 *  - une réservation occupe les nuits [date_arrivee, date_depart[ ;
 *  - les réservations ne sont jamais écrites dans `calendriers`, elles sont
 *    déduites des lignes `reservations` à chaque lecture.
 */
class CalendrierService
{
    /**
     * @return array{blocages: array, dates_reservees: array, reservations: array}
     */
    public function getCalendrierHote(Annonce $annonce, ?string $debut = null, ?string $fin = null): array
    {
        $debut = $debut ? Carbon::parse($debut)->startOfDay() : now()->startOfDay();
        $fin = $fin ? Carbon::parse($fin)->startOfDay() : $debut->copy()->addMonths(2);

        if ($fin->lt($debut)) {
            throw new ApiException('La date de fin doit être postérieure à la date de début.', 422, 'PLAGE_INVALIDE');
        }

        if ($debut->diffInDays($fin) > 366) {
            throw new ApiException('La plage demandée ne peut pas dépasser 366 jours.', 422, 'PLAGE_TROP_LONGUE');
        }

        $blocages = $annonce->calendriers()
            ->where('type_blockage', 'bloque_manuel')
            ->where('est_disponible', false)
            ->whereDate('date_fin', '>=', $debut->toDateString())
            ->whereDate('date_debut', '<=', $fin->toDateString())
            ->orderBy('date_debut')
            ->get()
            ->map(fn (Calendrier $calendrier) => [
                'id_calendrier' => $calendrier->getKey(),
                'date_debut' => $calendrier->date_debut->format('Y-m-d'),
                'date_fin' => $calendrier->date_fin->format('Y-m-d'),
            ])
            ->values()
            ->all();

        $reservations = $annonce->reservations()
            ->whereIn('statut', [StatutReservation::CONFIRMEE->value, StatutReservation::EN_COURS->value])
            ->whereDate('date_depart', '>', $debut->toDateString())
            ->whereDate('date_arrivee', '<=', $fin->toDateString())
            ->orderBy('date_arrivee')
            ->get();

        $datesReservees = [];

        foreach ($reservations as $reservation) {
            $curseur = Carbon::parse($reservation->date_arrivee);
            $depart = Carbon::parse($reservation->date_depart);

            while ($curseur->lt($depart)) {
                $datesReservees[] = $curseur->format('Y-m-d');
                $curseur->addDay();
            }
        }

        $datesReservees = array_values(array_unique($datesReservees));
        sort($datesReservees);

        return [
            'blocages' => $blocages,
            'dates_reservees' => $datesReservees,
            'reservations' => $reservations->map(fn ($reservation) => [
                'id_reservation' => $reservation->getKey(),
                'date_arrivee' => Carbon::parse($reservation->date_arrivee)->format('Y-m-d'),
                'date_depart' => Carbon::parse($reservation->date_depart)->format('Y-m-d'),
                'statut' => $reservation->statut->value,
            ])->values()->all(),
        ];
    }

    public function bloquerDates(Annonce $annonce, string $dateDebut, string $dateFin): Calendrier
    {
        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->startOfDay();

        if ($fin->lt($debut)) {
            throw new ApiException('La date de fin doit être postérieure à la date de début.', 422, 'PLAGE_INVALIDE');
        }

        $this->refuserSiReservationChevauche($annonce, $debut, $fin);

        return DB::transaction(function () use ($annonce, $debut, $fin) {
            // Fusionne avec les blocages manuels qui se chevauchent ou sont adjacents.
            $existants = $annonce->calendriers()
                ->where('type_blockage', 'bloque_manuel')
                ->where('est_disponible', false)
                ->whereDate('date_debut', '<=', $fin->copy()->addDay()->toDateString())
                ->whereDate('date_fin', '>=', $debut->copy()->subDay()->toDateString())
                ->get();

            $debutFusion = $debut->copy();
            $finFusion = $fin->copy();

            foreach ($existants as $existant) {
                $debutExistant = Carbon::parse($existant->date_debut);
                $finExistante = Carbon::parse($existant->date_fin);

                if ($debutExistant->lt($debutFusion)) {
                    $debutFusion = $debutExistant;
                }

                if ($finExistante->gt($finFusion)) {
                    $finFusion = $finExistante;
                }

                $existant->delete();
            }

            return Calendrier::create([
                'id_annonce' => $annonce->getKey(),
                'date_debut' => $debutFusion->toDateString(),
                'date_fin' => $finFusion->toDateString(),
                'est_disponible' => false,
                'type_blockage' => 'bloque_manuel',
            ]);
        });
    }

    public function debloquerDates(Annonce $annonce, string $dateDebut, string $dateFin): int
    {
        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->startOfDay();

        if ($fin->lt($debut)) {
            throw new ApiException('La date de fin doit être postérieure à la date de début.', 422, 'PLAGE_INVALIDE');
        }

        return DB::transaction(function () use ($annonce, $debut, $fin) {
            $blocages = $annonce->calendriers()
                ->where('type_blockage', 'bloque_manuel')
                ->where('est_disponible', false)
                ->whereDate('date_debut', '<=', $fin->toDateString())
                ->whereDate('date_fin', '>=', $debut->toDateString())
                ->get();

            $supprimes = 0;

            foreach ($blocages as $blocage) {
                $debutBlocage = Carbon::parse($blocage->date_debut);
                $finBlocage = Carbon::parse($blocage->date_fin);

                $blocage->delete();
                $supprimes++;

                // Partie avant la plage débloquée
                if ($debutBlocage->lt($debut)) {
                    Calendrier::create([
                        'id_annonce' => $annonce->getKey(),
                        'date_debut' => $debutBlocage->toDateString(),
                        'date_fin' => $debut->copy()->subDay()->toDateString(),
                        'est_disponible' => false,
                        'type_blockage' => 'bloque_manuel',
                    ]);
                }

                // Partie après la plage débloquée
                if ($finBlocage->gt($fin)) {
                    Calendrier::create([
                        'id_annonce' => $annonce->getKey(),
                        'date_debut' => $fin->copy()->addDay()->toDateString(),
                        'date_fin' => $finBlocage->toDateString(),
                        'est_disponible' => false,
                        'type_blockage' => 'bloque_manuel',
                    ]);
                }
            }

            return $supprimes;
        });
    }

    private function refuserSiReservationChevauche(Annonce $annonce, Carbon $debut, Carbon $fin): void
    {
        $chevauche = $annonce->reservations()
            ->whereIn('statut', [StatutReservation::CONFIRMEE->value, StatutReservation::EN_COURS->value])
            ->whereDate('date_arrivee', '<=', $fin->toDateString())
            ->whereDate('date_depart', '>', $debut->toDateString())
            ->exists();

        if ($chevauche) {
            throw new ApiException(
                'Impossible de bloquer une plage contenant une réservation active.',
                409,
                'DATES_RESERVEES',
            );
        }
    }
}
