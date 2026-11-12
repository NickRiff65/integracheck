<?php

use App\Http\Controllers\ConciliacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\IncidenteController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\IntegracionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/simular', [DashboardController::class, 'simular'])->name('simular');
Route::get('/integraciones/{integracion}', [IntegracionController::class, 'show'])->name('integraciones.show');
Route::post('/integraciones/{integracion}/verificar', [IntegracionController::class, 'verificar'])->name('integraciones.verificar');
Route::get('/envios', [EnvioController::class, 'index'])->name('envios.index');
Route::get('/envios/{envio}', [EnvioController::class, 'show'])->name('envios.show');
Route::get('/incidentes', [IncidenteController::class, 'index'])->name('incidentes.index');
Route::get('/incidentes/{incidente}', [IncidenteController::class, 'show'])->name('incidentes.show');
Route::put('/incidentes/{incidente}', [IncidenteController::class, 'update'])->name('incidentes.update');
Route::get('/informes', [InformeController::class, 'index'])->name('informes.index');
Route::get('/conciliaciones', [ConciliacionController::class, 'index'])->name('conciliaciones.index');
Route::get('/conciliaciones/crear', [ConciliacionController::class, 'create'])->name('conciliaciones.create');
Route::post('/conciliaciones', [ConciliacionController::class, 'store'])->name('conciliaciones.store');
Route::get('/conciliaciones/{conciliacion}', [ConciliacionController::class, 'show'])->name('conciliaciones.show');
