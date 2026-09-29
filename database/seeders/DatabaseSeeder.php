<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Usuario::query()->updateOrCreate(
            ['nombre' => 'admin'],
            ['contrasena' => Hash::make('Admin1234!')],
        );

        $companies = ['TecnoFix', 'ReparaYa', 'Servicio Digital'];
        $firstEquipmentId = 1001;
        $movementId = 5001;

        foreach ($companies as $companyIndex => $companyName) {
            $company = Empresa::query()->firstOrCreate(
                ['nombre' => $companyName],
                ['fecha_creacion' => now(), 'logo_url' => null],
            );

            for ($clientIndex = 1; $clientIndex <= 5; $clientIndex++) {
                $clientName = "Cliente {$clientIndex} - {$companyName}";
                $cuil = (string) (20100000000 + ($companyIndex * 100) + $clientIndex);

                for ($equipmentIndex = 1; $equipmentIndex <= 2; $equipmentIndex++) {
                    $equipmentId = $firstEquipmentId + ($companyIndex * 10) + (($clientIndex - 1) * 2) + $equipmentIndex - 1;
                    Equipo::query()->updateOrCreate(
                        ['id' => $equipmentId],
                        [
                            'id_empresa' => $company->id,
                            'id_movimiento' => $movementId++,
                            'marca' => $equipmentIndex === 1 ? 'Lenovo' : 'Dell',
                            'modelo' => $equipmentIndex === 1 ? 'ThinkPad T14' : 'Latitude 5420',
                            'nombre_equipo' => "Equipo {$equipmentIndex} de {$clientName}",
                            'fecha_creacion' => now()->format('j/n/Y'),
                            'nombre_cliente' => $clientName,
                            'nro_serie' => "{$companyIndex}{$clientIndex}SERIE{$equipmentIndex}",
                            'estado' => $equipmentIndex === 1 ? 'En reparación' : 'Recibido',
                            'fecha' => now()->format('j/n/Y'),
                            'observacion' => $equipmentIndex === 1 ? 'Revisión general del equipo.' : 'Equipo ingresado correctamente.',
                            'cuil' => $cuil,
                            'contrasena' => Hash::make('123456'),
                        ],
                    );
                }
            }
        }
    }
}
