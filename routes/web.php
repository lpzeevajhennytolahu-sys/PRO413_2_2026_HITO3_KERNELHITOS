<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GestionEscolarController;

Route::get('/', [GestionEscolarController::class, 'index'])->name('escolar.index');
Route::post('/inicializar-sql', [GestionEscolarController::class, 'inicializarBasesDeDatos'])->name('escolar.init');

// Endpoints Servidor 1: Base de Datos Docker
Route::post('/docker/estudiantes', [GestionEscolarController::class, 'storeEstudiante'])->name('docker.estudiante.store');
Route::patch('/docker/calificaciones/{id}', [GestionEscolarController::class, 'updateCalificacion'])->name('docker.calificacion.update');
Route::delete('/docker/inscripciones/{id}', [GestionEscolarController::class, 'deleteInscripcion'])->name('docker.inscripcion.delete');

// Endpoints Servidor 2: Base de Datos Red Local Docente (10.0.47.204)
Route::post('/docente/asignaciones', [GestionEscolarController::class, 'storeDocenteAsignacion'])->name('docente.asignacion.store');
Route::patch('/docente/asignaciones/{id}', [GestionEscolarController::class, 'updateHorarioDocente'])->name('docente.asignacion.update');
Route::delete('/docente/asignaciones/{id}', [GestionEscolarController::class, 'deleteAsignacionDocente'])->name('docente.asignacion.delete');