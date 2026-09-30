<?php

namespace App\Console\Commands;

use App\Domain\Company\Contracts\CompanyRepository;
use App\Domain\Movement\ImportMovements;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

#[Signature('movimientos:importar {id_empresa : ID de la empresa} {archivo? : Ruta del Excel .xlsx}')]
#[Description('Importa movimientos desde Repara.xlsx sin usar el login web.')]
class ImportMovementsCommand extends Command
{
    public function handle(CompanyRepository $companies, ImportMovements $importer): int
    {
        $idEmpresa = filter_var($this->argument('id_empresa'), FILTER_VALIDATE_INT);

        if ($idEmpresa === false || $idEmpresa < 1 || $companies->find($idEmpresa) === null) {
            $this->error('Se ingresó un número de empresa inválido');

            return SymfonyCommand::INVALID;
        }

        $rutaExcel = $this->argument('archivo')
            ?? dirname(base_path()).DIRECTORY_SEPARATOR.'Repara.xlsx';
        $rutaExcelReal = realpath($rutaExcel);

        if ($rutaExcelReal === false || ! is_file($rutaExcelReal) || ! is_readable($rutaExcelReal)) {
            $this->error("No se pudo leer el archivo Excel: {$rutaExcel}");

            return SymfonyCommand::FAILURE;
        }

        $archivo = new UploadedFile(
            $rutaExcelReal,
            basename($rutaExcelReal),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            UPLOAD_ERR_OK,
            true,
        );
        $resultado = $importer->execute($archivo, $idEmpresa);

        $this->info("Movimientos importados o actualizados: {$resultado['processed']}.");

        if ($resultado['errors'] !== []) {
            $this->newLine();
            $this->error('Se encontraron errores durante la importación:');

            foreach ($resultado['errors'] as $error) {
                $this->line("- {$error}");
            }

            return SymfonyCommand::FAILURE;
        }

        $this->info('Proceso finalizado correctamente.');

        return SymfonyCommand::SUCCESS;
    }
}
