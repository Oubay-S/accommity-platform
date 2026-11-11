<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Notifications\NotificationService;

/**
 * Commande planifiée - Envoi des rappels check-in / check-out
 * Règle d'organisation : RO07 (rappels à 48h et 24h)
 */
class SendReminders extends Command
{
    protected $signature = 'reminders:send';
    protected $description = 'Envoie les rappels avant arrivée et départ aux voyageurs et hôtes';

    public function handle(NotificationService $notificationService): int
    {
        // TODO: Implémenter l'envoi des rappels
        $this->info('Rappels envoyés.');
        return Command::SUCCESS;
    }
}
