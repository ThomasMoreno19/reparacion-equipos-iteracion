<?php
namespace App\Infrastructure\Persistence;
use App\Domain\Company\Contracts\CompanyRepository;
use App\Models\Empresa;
use Illuminate\Support\Collection;
final class EloquentCompanyRepository implements CompanyRepository
{
    public function find(int $id): ?Empresa { return Empresa::find($id); }
    public function all(): Collection { return Empresa::query()->orderBy('nombre')->get(); }
    public function create(string $name, ?string $logoUrl): Empresa { return Empresa::create(['nombre' => $name, 'logo_url' => $logoUrl, 'fecha_creacion' => now()]); }
}
