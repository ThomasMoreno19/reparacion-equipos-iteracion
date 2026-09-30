<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CompanySettingsController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\PublicRepairController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicRepairController::class, 'home'])->name('home');
Route::post('/consultar', [PublicRepairController::class, 'lookup'])->name('public.lookup');
Route::post('/consultar/detalle', [PublicRepairController::class, 'lookupDetail'])->name('public.lookup.detail');
Route::post('/consultar/directo', [PublicRepairController::class, 'verifyDirect'])->name('repair.direct.verify');
Route::get('/{companyId}', [PublicRepairController::class, 'company'])->whereNumber('companyId')->name('company');
Route::get('/{companyId}/{movementId}/{equipmentId}', [PublicRepairController::class, 'direct'])->whereNumber('companyId')->whereNumber('movementId')->whereNumber('equipmentId')->name('repair.direct');

Route::middleware(['auth', 'role:Superadmin,Admin'])->group(function () {
    Route::get('/configuracion/{companyId}', [CompanySettingsController::class, 'show'])->whereNumber('companyId')->name('company.settings');
});

Route::prefix('admin')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::middleware(['auth', 'role:Superadmin'])->group(function () {
        Route::get('/panel', [PanelController::class, 'index'])->name('admin.panel');
        Route::post('/empresas', [PanelController::class, 'storeCompany'])->name('admin.company.store');
        Route::put('/empresas/{companyId}', [PanelController::class, 'updateCompany'])->whereNumber('companyId')->name('admin.company.update');
        Route::delete('/empresas/{companyId}', [PanelController::class, 'destroyCompany'])->whereNumber('companyId')->name('admin.company.destroy');
        Route::post('/movimientos/importar', [PanelController::class, 'import'])->name('admin.import');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
