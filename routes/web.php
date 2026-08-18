<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('panel')
        : redirect()->route('login.form');
});

Route::get('/inicio', function () {
    return redirect()->route('panel');
});

Route::get('/login', [AuthController::class, 'create'])->name('login.form');
Route::post('/login', [AuthController::class, 'store'])
    ->middleware(['throttle:login', 'throttle:login-ip'])
    ->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware(['legacy.auth', 'can:access-panel'])->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/panel/reportes', [PanelController::class, 'reportes'])->name('panel.reportes');
    Route::get('/panel/usuarios', [PanelController::class, 'usuarios'])->name('panel.usuarios');
    Route::get('/panel/configuracion', [PanelController::class, 'configuracion'])->name('panel.configuracion');
    Route::get('/panel/configuracion/buscar', [PanelController::class, 'buscarUsuarioConfiguracion'])->name('panel.configuracion.buscar');
    Route::get('/panel/proximos', [PanelController::class, 'proximos'])->name('panel.proximos');
    Route::get('/panel/programados', [PanelController::class, 'programados'])->name('panel.programados');
    Route::get('/panel/hechos', [PanelController::class, 'hechos'])->name('panel.hechos');
    Route::get('/panel/evidencia/{mantenimientoId}', [PanelController::class, 'evidenciaPdf'])->name('panel.evidencia.pdf');
    Route::post('/panel/usuarios/crear', [PanelController::class, 'crearUsuario'])->name('panel.usuarios.crear');
    Route::post('/panel/usuarios/actualizar', [PanelController::class, 'actualizarUsuario'])->name('panel.usuarios.actualizar');
    Route::post('/panel/usuarios/estado', [PanelController::class, 'cambiarEstadoUsuario'])->name('panel.usuarios.estado');
    Route::post('/panel/usuarios/eliminar', [PanelController::class, 'eliminarUsuario'])->name('panel.usuarios.eliminar');
    Route::post('/panel/proximos/programar', [PanelController::class, 'programarProximo'])->name('panel.proximos.programar');
    Route::post('/panel/programados/hecho', [PanelController::class, 'marcarProgramadoComoHecho'])->name('panel.programados.hecho');
    Route::post('/panel/tecnicos/crear', [PanelController::class, 'crearTecnico'])->name('panel.tecnicos.crear');
    Route::post('/panel/configuracion/preview', [PanelController::class, 'previsualizarGestionEstado'])->name('panel.configuracion.preview');
    Route::post('/panel/configuracion/gestionar', [PanelController::class, 'gestionarEstadoMantenimiento'])->name('panel.configuracion.gestionar');
    Route::post('/panel/configuracion/revertir', [PanelController::class, 'gestionarEstadoMantenimiento'])->name('panel.configuracion.revertir');
});
