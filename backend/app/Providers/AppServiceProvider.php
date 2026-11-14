<?php

namespace App\Providers;

use App\Models\Annonces\Annonce;
use App\Models\Evaluations\Evaluation;
use App\Models\Reservations\Reservation;
use App\Policies\AnnoncePolicy;
use App\Policies\EvaluationPolicy;
use App\Policies\ReservationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Annonce::class, AnnoncePolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(Evaluation::class, EvaluationPolicy::class);

        // In local dev, crash when create()/update() receives a column that isn't in $fillable
        // (catches old names like 'hote_id' instead of silently dropping them).
        // Don't use Model::shouldBeStrict(): it also forbids lazy loading, which breaks the role middleware.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }
}
