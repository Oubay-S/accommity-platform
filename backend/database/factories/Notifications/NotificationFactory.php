<?php

namespace Database\Factories\Notifications;

use App\Enums\TypeNotification;
use App\Models\Notifications\Notification;
use Database\Factories\Utilisateurs\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id_utilisateur' => UserFactory::new(),
            'titre' => $this->faker->sentence(4),
            'contenu' => $this->faker->sentence(12),
            'type_alerte' => $this->faker->randomElement(TypeNotification::cases()),
            'est_lue' => false,
        ];
    }

    public function lue(): static
    {
        return $this->state(fn () => ['est_lue' => true]);
    }

    public function deType(TypeNotification $type): static
    {
        return $this->state(fn () => ['type_alerte' => $type]);
    }
}
