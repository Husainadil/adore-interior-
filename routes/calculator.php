<?php

use App\Http\Controllers\CalculatorAdminController;
use App\Http\Controllers\CalculatorController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('interior-cost-calculator')->group(function () {
    Route::get('/', [CalculatorController::class, 'index'])->name('calculator.index');
    Route::post('/preview', [CalculatorController::class, 'preview'])->middleware('throttle:120,1')->name('calculator.preview');
    Route::post('/estimates', [CalculatorController::class, 'store'])->middleware('throttle:20,10')->name('calculator.store');
    Route::middleware('signed')->group(function () {
        Route::get('/estimate/{estimate}', [CalculatorController::class, 'result'])->name('calculator.result');
        Route::get('/estimate/{estimate}/pdf', [CalculatorController::class, 'pdf'])->middleware('throttle:20,1')->name('calculator.pdf');
        Route::get('/estimate/{estimate}/whatsapp', [CalculatorController::class, 'whatsapp'])->name('calculator.whatsapp');
        Route::post('/estimate/{estimate}/consultation', [CalculatorController::class, 'lead'])->middleware('throttle:5,10')->name('calculator.lead');
    });
});
Route::middleware(AdminOnly::class)->prefix('admin/calculator')->group(function () {
    Route::get('/', [CalculatorAdminController::class, 'index'])->name('calculator.admin');
    Route::get('/leads/export', [CalculatorAdminController::class, 'export'])->name('calculator.admin.export');
    Route::get('/estimates/{estimate}/pdf', [CalculatorAdminController::class, 'pdf'])->name('calculator.admin.pdf');
    Route::get('/estimates/{estimate}', [CalculatorAdminController::class, 'show'])->name('calculator.admin.estimate');
    Route::get('/{module}/create', [CalculatorAdminController::class, 'edit'])->name('calculator.admin.create');
    Route::get('/{module}/{id}/edit', [CalculatorAdminController::class, 'edit'])->whereNumber('id')->name('calculator.admin.edit');
    Route::post('/{module}/{id?}', [CalculatorAdminController::class, 'save'])->whereNumber('id')->name('calculator.admin.save');
    Route::get('/{module}', [CalculatorAdminController::class, 'index'])->name('calculator.admin.module');
});
