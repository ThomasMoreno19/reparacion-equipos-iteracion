<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicRepairController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\PanelController;

Route::get('/', [PublicRepairController::class, 'home'])->name('home');
Route::post('/consultar', [PublicRepairController::class, 'lookup'])->name('public.lookup');
Route::get('/{companyId}', [PublicRepairController::class, 'company'])->whereNumber('companyId')->name('company');
Route::get('/{companyId}/{movementId}/{equipmentId}', [PublicRepairController::class, 'direct'])->whereNumber('companyId')->whereNumber('movementId')->whereNumber('equipmentId')->name('repair.direct');

Route::prefix('admin')->group(function () {
    Route::get('/', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::middleware('auth')->group(function () {
        Route::get('/panel', [PanelController::class, 'index'])->name('admin.panel');
        Route::post('/empresas', [PanelController::class, 'storeCompany'])->name('admin.company.store');
        Route::put('/empresas/{companyId}', [PanelController::class, 'updateCompany'])->whereNumber('companyId')->name('admin.company.update');
        Route::delete('/empresas/{companyId}', [PanelController::class, 'destroyCompany'])->whereNumber('companyId')->name('admin.company.destroy');
        Route::post('/movimientos/importar', [PanelController::class, 'import'])->name('admin.import');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    });
});
