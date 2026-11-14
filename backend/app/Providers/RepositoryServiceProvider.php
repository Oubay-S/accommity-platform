<?php

namespace App\Providers;

use App\Repositories\Eloquent\AnnonceRepository;
use App\Repositories\Eloquent\PaiementRepository;
use App\Repositories\Eloquent\ReservationRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\AnnonceRepositoryInterface;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AnnonceRepositoryInterface::class, AnnonceRepository::class);
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(PaiementRepositoryInterface::class, PaiementRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
