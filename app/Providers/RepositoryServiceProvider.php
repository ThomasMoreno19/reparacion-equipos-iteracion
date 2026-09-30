<?php

namespace App\Providers;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\Contracts\MovementRepository;
use App\Infrastructure\Persistence\EloquentCompanyRepository;
use App\Infrastructure\Persistence\EloquentMovementRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepository::class, EloquentCompanyRepository::class);
        $this->app->bind(MovementRepository::class, EloquentMovementRepository::class);
    }
}
