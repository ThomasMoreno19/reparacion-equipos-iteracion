<?php
namespace App\Providers;
use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Repair\Contracts\RepairRepository;
use App\Infrastructure\Persistence\EloquentCompanyRepository;
use App\Infrastructure\Persistence\EloquentRepairRepository;
use Illuminate\Support\ServiceProvider;
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->bind(CompanyRepository::class, EloquentCompanyRepository::class); $this->app->bind(RepairRepository::class, EloquentRepairRepository::class); }
}
