<?php
namespace App\Http\Controllers;
use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Repair\Contracts\RepairRepository;
use App\Domain\Repair\LookupRepair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PublicRepairController extends Controller
{
    public function __construct(private LookupRepair $lookup, private CompanyRepository $companies, private RepairRepository $repairs) {}
    public function home() { return view('public.home'); }
    public function lookup(Request $request)
    {
        $data = $request->validate(['type' => ['required', 'in:cuil,serial'], 'value' => ['required', 'string', 'max:80'], 'password' => ['nullable', 'string', 'max:100'], 'company_id' => ['nullable', 'integer']]);
        $companyId = $data['company_id'] ?? null;
        $result = $data['type'] === 'cuil' ? $this->lookup->byCuil($data['value'], $companyId) : $this->lookup->bySerial($data['value'], $companyId);
        $result['equipos'] = $this->authorized($result['equipos'], $data['password'] ?? '');
        if ($result['equipos']->isEmpty()) return back()->withInput()->withErrors(['lookup' => 'No se encontraron equipos o la contraseña no es válida.']);
        if ($companyId !== null) {
            $company = $this->companies->find($companyId);
            abort_unless($company, 404);
            return view('public.company', $result + compact('company') + ['searched' => true]);
        }
        return view('public.home', $result + ['searched' => true]);
    }
    public function company(int $companyId) { $company = $this->companies->find($companyId); abort_unless($company, 404); return view('public.company', compact('company')); }
    public function direct(int $companyId, int $movementId, int $equipmentId)
    { $company = $this->companies->find($companyId); $equipment = $this->repairs->findById($companyId, $equipmentId); abort_unless($company && $equipment, 404); return view('public.direct', compact('company', 'equipment', 'movementId')); }
    private function authorized($equipos, string $password) { return $equipos->filter(fn ($equipment) => !$equipment->contrasena || ($password !== '' && Hash::check($password, $equipment->contrasena)))->values(); }
}
