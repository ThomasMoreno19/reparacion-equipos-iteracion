<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Repair\ImportRepairs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PanelController
{
    public function __construct(private CompanyRepository $companies) {}

    public function index()
    {
        return view('admin.panel', ['companies' => $this->companies->all()]);
    }

    public function storeCompany(Request $request)
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:150'], 'imagen' => ['nullable', 'image', 'max:5120']]);
        $logo = isset($data['imagen']) ? '/storage/' . $data['imagen']->store('logos', 'public') : null;
        $this->companies->create($data['nombre'], $logo);
        return back()->with('success', 'Empresa guardada correctamente.');
    }

    public function updateCompany(Request $request, int $companyId)
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:150'], 'imagen' => ['nullable', 'image', 'max:5120'], 'eliminar_logo' => ['nullable', 'boolean']]);
        $company = $this->companies->find($companyId);
        abort_unless($company, 404);
        $replaceLogo = $request->hasFile('imagen') || $request->boolean('eliminar_logo');
        $logo = $company->logo_url;

        if ($request->hasFile('imagen')) {
            $logo = '/storage/' . $data['imagen']->store('logos', 'public');
            $this->deleteStoredLogo($company->logo_url);
        } elseif ($request->boolean('eliminar_logo')) {
            $logo = null;
            $this->deleteStoredLogo($company->logo_url);
        }

        $this->companies->update($companyId, $data['nombre'], $logo, $replaceLogo);
        return back()->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroyCompany(int $companyId)
    {
        $company = $this->companies->find($companyId);
        abort_unless($company, 404);
        $this->deleteStoredLogo($company->logo_url);
        $this->companies->delete($companyId);
        return back()->with('success', 'Empresa eliminada correctamente.');
    }

    public function import(Request $request, ImportRepairs $importer)
    {
        $data = $request->validate(['empresa_id' => ['required', 'integer', 'exists:empresa,id'], 'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $result = $importer->execute($data['csv_file'], (int) $data['empresa_id']);
        return back()->with('success', "Se procesaron {$result['processed']} filas.")->with('import_errors', $result['errors']);
    }

    private function deleteStoredLogo(?string $logoUrl): void
    {
        if ($logoUrl && str_starts_with($logoUrl, '/storage/')) Storage::disk('public')->delete(substr($logoUrl, 9));
    }
}
