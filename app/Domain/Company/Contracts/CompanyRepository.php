<?php
namespace App\Domain\Company\Contracts;
use App\Models\Empresa;
use Illuminate\Support\Collection;
interface CompanyRepository
{
    public function find(int $id): ?Empresa;
    public function all(): Collection;
    public function create(string $name, ?string $logoUrl): Empresa;
}
