<?php
namespace App\Http\Controllers\Admin;
use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Repair\ImportRepairs;
use Illuminate\Http\Request;
class PanelController
{
    public function __construct(private CompanyRepository $companies) {}
    public function index() { return view('admin.panel', ['companies' => $this->companies->all()]); }
    public function storeCompany(Request $request)
    { $data = $request->validate(['nombre' => ['required', 'string', 'max:150'], 'imagen' => ['nullable', 'image', 'max:5120']]); $logo = isset($data['imagen']) ? '/storage/'.$data['imagen']->store('logos', 'public') : null; $this->companies->create($data['nombre'], $logo); return back()->with('success', 'Empresa guardada correctamente.'); }
    public function import(Request $request, ImportRepairs $importer)
    { $data = $request->validate(['empresa_id' => ['required', 'integer', 'exists:Empresa,id'], 'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]); $result = $importer->execute($data['csv_file'], (int) $data['empresa_id']); return back()->with('success', "Se procesaron {$result['processed']} filas.")->with('import_errors', $result['errors']); }
}
