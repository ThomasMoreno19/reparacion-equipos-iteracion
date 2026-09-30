<?php

namespace App\Http\Controllers;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\Contracts\MovementRepository;
use App\Domain\Movement\LookupMovement;
use App\Models\Empresa;
use App\Models\Movimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PublicRepairController extends Controller
{
    public function __construct(
        private LookupMovement $lookup,
        private CompanyRepository $companies,
        private MovementRepository $movements,
    ) {}

    public function home(): View
    {
        return view('public.home');
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:cuil,serial'],
            'value' => ['required', 'string', 'max:80'],
        ]);

        $result = $data['type'] === 'cuil'
            ? $this->lookup->byCuil($data['value'])
            : $this->lookup->bySerial($data['value']);

        if ($result['movimientos']->isEmpty()) {
            return back()->withInput()->withErrors(['lookup' => 'No se encontraron equipos con ese dato.']);
        }

        $companies = $result['movimientos']->groupBy('id_empresa')->map(function (Collection $movements): array {
            return [
                'company' => $movements->first()->empresa,
                'equipment_count' => $movements->pluck('id_equipo')->filter()->unique()->count(),
            ];
        })->values();

        return view('public.home', [
            'searched' => true,
            'companies' => $companies,
            'type' => $data['type'],
            'value' => $data['value'],
        ]);
    }

    public function lookupDetail(Request $request): View
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'type' => ['required', 'in:cuil,serial'],
            'value' => ['required', 'string', 'max:80'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);
        $company = $this->companies->find((int) $data['company_id']);
        abort_unless($company, 404);

        return $this->companyDetails(
            $company,
            $data['type'],
            $data['value'],
            $data['password'] ?? null,
        );
    }

    public function company(int $companyId): View
    {
        $company = $this->companies->find($companyId);
        abort_unless($company, 404);

        return view('public.company', ['company' => $company, 'searched' => false]);
    }

    public function direct(int $companyId, int $movementId, int $equipmentId): View
    {
        $company = $this->companies->find($companyId);
        $movement = $this->movements->findById($companyId, $movementId, $equipmentId);
        abort_unless($company && $movement, 404);

        return $this->directView($company, $movement, (bool) $movement->contrasena);
    }

    public function verifyDirect(Request $request): View
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'movement_id' => ['required', 'integer'],
            'equipment_id' => ['required', 'integer'],
            'password' => ['nullable', 'string', 'max:100'],
        ]);
        $company = $this->companies->find((int) $data['company_id']);
        $movement = $this->movements->findById(
            (int) $data['company_id'],
            (int) $data['movement_id'],
            (int) $data['equipment_id'],
        );
        abort_unless($company && $movement, 404);

        if ($movement->contrasena && (! $data['password'] || ! Hash::check($data['password'], $movement->contrasena))) {
            return $this->directView($company, $movement, true, 'La contraseña ingresada no es válida.');
        }

        return $this->directView($company, $movement, false);
    }

    private function companyDetails(Empresa $company, string $type, string $value, ?string $password): View
    {
        $result = $type === 'cuil'
            ? $this->lookup->byCuil($value, (int) $company->id)
            : $this->lookup->bySerial($value, (int) $company->id);
        $movements = $result['movimientos'];
        $protectedMovements = $movements->filter(static fn (Movimiento $movement): bool => (bool) $movement->contrasena);
        $requiresPassword = $protectedMovements->isNotEmpty();
        $passwordError = null;

        if ($requiresPassword) {
            $matchesProtectedMovement = $password !== null && $password !== '' && $protectedMovements->contains(
                static fn (Movimiento $movement): bool => Hash::check($password, $movement->contrasena),
            );

            if (! $matchesProtectedMovement) {
                $passwordError = $password ? 'La contraseña ingresada no es válida.' : null;
                $movements = new Collection;
            } else {
                $movements = $this->authorized($movements, $password);
                $requiresPassword = false;
            }
        }

        return view('public.company', [
            'company' => $company,
            'searched' => true,
            'lookupType' => $type,
            'lookupValue' => $value,
            'movimientos' => $movements,
            'requiresPassword' => $requiresPassword,
            'passwordError' => $passwordError,
        ]);
    }

    private function directView(Empresa $company, Movimiento $movement, bool $requiresPassword, ?string $passwordError = null): View
    {
        return view('public.direct', compact('company', 'movement', 'requiresPassword', 'passwordError'));
    }

    private function authorized(Collection $movements, string $password): Collection
    {
        return $movements->filter(
            static fn (Movimiento $movement): bool => ! $movement->contrasena || Hash::check($password, $movement->contrasena),
        )->values();
    }
}
