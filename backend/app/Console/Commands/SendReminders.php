<?php

namespace App\Console\Commands;

use App\Enums\StatutReservation;
use App\Models\Reservations\Reservation;
use App\Services\Notifications\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Commande planifiée — rappels check-in (RO07).
 *
 *  - 48 h avant l'arrivée : notification à l'hôte
 *  - 24 h avant l'arrivée : notification au voyageur
 *
 * L'envoi est idempotent (cf. NotificationService::rappel).
 */
class SendReminders extends Command
{
    protected $signature = 'reminders:send {--date= : Date de référence Y-m-d (utile pour tester)}';

    protected $description = 'Envoie les rappels aux hôtes (48 h) et voyageurs (24 h) avant le check-in';

    public function handle(NotificationService $notificationService): int
    {
        $reference = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $dans48h = $reference->copy()->addDays(2)->toDateString();
        $dans24h = $reference->copy()->addDay()->toDateString();

        $hotes = 0;
        $voyageurs = 0;

        Reservation::query()
            ->where('statut', StatutReservation::CONFIRMEE->value)
            ->whereDate('date_arrivee', $dans48h)
            ->with('hote')
            ->get()
            ->each(function (Reservation $reservation) use ($notificationService, &$hotes): void {
                if ($reservation->hote !== null
                    && $notificationService->rappel($reservation, $reservation->hote, '48 heures')->wasRecentlyCreated) {
                    $hotes++;
                }
            });

        Reservation::query()
            ->where('statut', StatutReservation::CONFIRMEE->value)
            ->whereDate('date_arrivee', $dans24h)
            ->with('voyageur')
            ->get()
            ->each(function (Reservation $reservation) use ($notificationService, &$voyageurs): void {
                if ($reservation->voyageur !== null
                    && $notificationService->rappel($reservation, $reservation->voyageur, '24 heures')->wasRecentlyCreated) {
                    $voyageurs++;
                }
            });

        $this->info("Rappels envoyés : {$hotes} hôte(s) (48 h), {$voyageurs} voyageur(s) (24 h).");

        return Command::SUCCESS;
    }
}
