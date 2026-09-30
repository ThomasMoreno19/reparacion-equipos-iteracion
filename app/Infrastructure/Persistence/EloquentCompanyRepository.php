<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Models\Empresa;
use App\Models\Equipo;
use Illuminate\Support\Collection;

final class EloquentCompanyRepository implements CompanyRepository
{
    public function find(int $id): ?Empresa
    {
        return Empresa::find($id);
    }

    public function all(): Collection
    {
        return Empresa::query()->with('admin')->orderBy('nombre')->get();
    }

    public function create(string $name, ?string $logoUrl): Empresa
    {
        return Empresa::create([
            'nombre' => $name,
            'logo_url' => $logoUrl,
            'fecha_creacion' => now()->toDateString(),
            'tieneCarrito' => 1,
            'deshabilitar_excel' => 0,
            'imagenesEnArticulos' => 1,
            'incluirHorarios' => 0,
            'pedidosFueraHorario' => 0,
            'incluirCodigoBarra' => 0,
        ]);
    }

    public function update(int $id, string $name, ?string $logoUrl, bool $replaceLogo = false): Empresa
    {
        $company = Empresa::query()->findOrFail($id);
        $company->nombre = $name;
        if ($replaceLogo) {
            $company->logo_url = $logoUrl;
        }
        $company->save();

        return $company;
    }

    public function delete(int $id): void
    {
        $company = Empresa::query()->findOrFail($id);
        Equipo::query()->where('id_empresa', $company->id)->delete();
        $company->delete();
    }
}
