<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\Contracts\MovementRepository;
use App\Http\Controllers\Controller;
use App\Models\Movimiento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(private CompanyRepository $companies, private MovementRepository $movements) {}

    public function show(Request $request, int $companyId): View
    {
        $user = $request->user();
        abort_unless($user instanceof Usuario && $user->canAccessCompany($companyId), 403);

        $company = $this->companies->find($companyId);
        abort_unless($company, 404);

        $equipment = $company->equipos()->orderByDesc('id')->get();
        $movements = $this->movements->findAllByCompany($companyId);
        $clients = $movements->groupBy(function (Movimiento $item): string {
            $clientName = mb_strtolower(trim((string) $item->nombre_cliente));
            $clientDocument = trim((string) $item->cuil);

            return $clientDocument !== '' ? $clientDocument : 'name:'.($clientName ?: 'sin-cliente');
        });

        return view('admin.company-settings', compact('company', 'clients', 'movements', 'equipment'));
    }
}
