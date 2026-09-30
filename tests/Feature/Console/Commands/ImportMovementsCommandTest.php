<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Company\Contracts\CompanyRepository;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class ImportMovementsCommandTest extends TestCase
{
    public function test_it_reports_the_exact_invalid_company_message_for_an_unknown_id(): void
    {
        $companies = Mockery::mock(CompanyRepository::class);
        $companies->shouldReceive('find')->once()->with(987654321)->andReturn(null);
        $this->app->instance(CompanyRepository::class, $companies);

        $exitCode = Artisan::call('movimientos:importar', [
            'id_empresa' => '987654321',
            'archivo' => 'archivo-inexistente.xlsx',
        ]);

        $this->assertSame(Command::INVALID, $exitCode);
        $this->assertSame(
            'Se ingresó un número de empresa inválido',
            trim(Artisan::output()),
        );
    }
}
