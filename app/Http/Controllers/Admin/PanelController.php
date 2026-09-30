<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\ImportMovements;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Role;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PanelController
{
    public function __construct(private CompanyRepository $companies) {}

    public function index(): View
    {
        return view('admin.panel', ['companies' => $this->companies->all()]);
    }

    public function storeCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'admin_nombre' => ['required', 'string', 'max:100', 'unique:usuario,nombre'],
            'admin_contrasena' => ['required', 'string', 'min:2'],
            'imagen' => ['nullable', 'image', 'max:5120'],
        ]);
        $logo = isset($data['imagen']) ? '/storage/'.$data['imagen']->store('logos', 'public') : null;

        DB::transaction(function () use ($data, $logo): void {
            $company = $this->companies->create($data['nombre'], $logo);
            $this->saveAdmin($company, $data);
        });

        return back()->with('success', 'Empresa guardada correctamente.');
    }

    public function updateCompany(Request $request, int $companyId): RedirectResponse
    {
        $company = $this->companies->find($companyId);
        abort_unless($company, 404);
        $admin = $company->admin()->first();
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'admin_nombre' => ['required', 'string', 'max:100', Rule::unique('usuario', 'nombre')->ignore($admin?->id)],
            'admin_contrasena' => [$admin ? 'nullable' : 'required', 'string', 'min:2'],
            'imagen' => ['nullable', 'image', 'max:5120'],
            'eliminar_logo' => ['nullable', 'boolean'],
        ]);
        $replaceLogo = $request->hasFile('imagen') || $request->boolean('eliminar_logo');
        $logo = $company->logo_url;

        if ($request->hasFile('imagen')) {
            $logo = '/storage/'.$data['imagen']->store('logos', 'public');
        } elseif ($request->boolean('eliminar_logo')) {
            $logo = null;
        }

        DB::transaction(function () use ($companyId, $company, $admin, $data, $logo, $replaceLogo): void {
            $this->companies->update($companyId, $data['nombre'], $logo, $replaceLogo);
            $this->saveAdmin($company, $data, $admin);
        });

        if ($replaceLogo && $logo !== $company->logo_url) {
            $this->deleteStoredLogo($company->logo_url);
        }

        return back()->with('success', 'Empresa actualizada correctamente.');
    }

    public function destroyCompany(int $companyId): RedirectResponse
    {
        $company = $this->companies->find($companyId);
        abort_unless($company, 404);

        try {
            DB::transaction(function () use ($companyId): void {
                $this->companies->delete($companyId);
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return back()->withErrors(['company' => 'No se puede eliminar esta empresa porque tiene datos asociados en EFactWeb.']);
        }

        DB::transaction(function () use ($companyId): void {
            Usuario::query()
                ->where('id_empresa', $companyId)
                ->where('role', Role::Admin->value)
                ->delete();
        });

        $this->deleteStoredLogo($company->logo_url);

        return back()->with('success', 'Empresa eliminada correctamente.');
    }

    public function import(Request $request, ImportMovements $importer): RedirectResponse
    {
        $data = $request->validate([
            'empresa_id' => ['required', 'integer', Rule::exists('empresa', 'id')],
            'excel_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);
        $result = $importer->execute($data['excel_file'], (int) $data['empresa_id']);

        return back()->with('success', "Se importaron o actualizaron {$result['processed']} movimientos.")->with('import_errors', $result['errors']);
    }

    private function deleteStoredLogo(?string $logoUrl): void
    {
        if ($logoUrl && str_starts_with($logoUrl, '/storage/')) {
            Storage::disk('public')->delete(substr($logoUrl, 9));
        }
    }

    /**
     * @param  array{admin_nombre: string, admin_contrasena?: string|null}  $data
     */
    private function saveAdmin(Empresa $company, array $data, ?Usuario $admin = null): void
    {
        if ($admin === null) {
            Usuario::query()->create([
                'nombre' => $data['admin_nombre'],
                'contrasena' => Hash::make($data['admin_contrasena']),
                'role' => Role::Admin,
                'id_empresa' => $company->id,
            ]);

            return;
        }

        $admin->nombre = $data['admin_nombre'];

        if (! empty($data['admin_contrasena'])) {
            $admin->contrasena = Hash::make($data['admin_contrasena']);
        }

        $admin->save();
    }
}
