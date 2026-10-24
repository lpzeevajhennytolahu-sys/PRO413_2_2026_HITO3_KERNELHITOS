<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ProcesadorEstructuraDatos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class GestionEscolarController extends Controller
{
    private ProcesadorEstructuraDatos $procesador;

    public function __construct(ProcesadorEstructuraDatos $procesador)
    {
        $this->procesador = $procesador;
    }

    public function index(): View
    {
        $estadoDocker = 'Conectado';
        $estadoDocente = 'Conectado';
        $errorMensaje = null;

        $estudiantes = [];
        $asignaturas = [];
        $inscripciones = [];
        $docentes = [];
        $aulas = [];
        $gestiones = [];
        $asignaciones = [];
        $fichaIntegrada = [];

        // 1. Consulta al Servidor 1: MySQL Docker
        try {
            $estudiantes = DB::connection('mysql')->select('SELECT * FROM estudiantes ORDER BY id DESC');
            $asignaturas = DB::connection('mysql')->select('SELECT * FROM asignaturas ORDER BY id ASC');
            $inscripciones = DB::connection('mysql')->select('
                SELECT i.id, e.codigo_estudiante, e.nombre_completo, a.sigla, a.nombre AS asignatura_nombre,
                       i.gestion_codigo, i.estado, c.nota_parcial, c.nota_final, c.observacion
                FROM inscripciones i
                INNER JOIN estudiantes e ON i.estudiante_id = e.id
                INNER JOIN asignaturas a ON i.asignatura_id = a.id
                LEFT JOIN calificaciones c ON c.inscripcion_id = i.id
                ORDER BY i.id DESC
            ');
        } catch (Throwable $e) {
            $estadoDocker = 'Tablas pendientes o sin conexión';
            $errorMensaje = 'BD Docker: Presione el botón "Inicializar Tablas SQL" o verifique el contenedor MySQL.';
        }

        // 2. Consulta al Servidor 2: MySQL Red Local Docente (10.0.47.204)
        try {
            $docentes = DB::connection('mysql_docente')->select('SELECT * FROM docentes ORDER BY id DESC');
            $aulas = DB::connection('mysql_docente')->select('SELECT * FROM aulas ORDER BY id ASC');
            $gestiones = DB::connection('mysql_docente')->select('SELECT * FROM gestiones_academicas ORDER BY id DESC');
            $asignaciones = DB::connection('mysql_docente')->select('
                SELECT ad.id, d.codigo_docente, d.nombre_completo AS docente_nombre, g.codigo_gestion,
                       au.codigo_aula, au.bloque, ad.asignatura_sigla, ad.horario
                FROM asignaciones_docentes ad
                INNER JOIN docentes d ON ad.docente_id = d.id
                INNER JOIN gestiones_academicas g ON ad.gestion_id = g.id
                INNER JOIN aulas au ON ad.aula_id = au.id
                ORDER BY ad.id DESC
            ');
        } catch (Throwable $e) {
            $estadoDocente = 'Tablas pendientes o sin conexión';
            $errorMensaje = ($errorMensaje ? $errorMensaje . ' | ' : '') . 'BD Docente (10.0.47.204): Presione "Inicializar Tablas SQL" o verifique red local.';
        }

        // 3. Procesamiento con Estructura de Datos y POO
        if (!empty($inscripciones) || !empty($asignaciones)) {
            $fichaIntegrada = $this->procesador->cruzarDatosAmbosServidores($inscripciones, $asignaciones);
        }

        return view('escolar.index', compact(
            'estadoDocker',
            'estadoDocente',
            'errorMensaje',
            'estudiantes',
            'asignaturas',
            'inscripciones',
            'docentes',
            'aulas',
            'gestiones',
            'asignaciones',
            'fichaIntegrada'
        ));
    }

    public function inicializarBasesDeDatos(): RedirectResponse
    {
        $mensajes = [];

        try {
            $sqlDocker = file_get_contents(base_path('sql/01_bd_docker_estudiantes.sql'));
            if ($sqlDocker !== false) {
                DB::connection('mysql')->unprepared($sqlDocker);
                $mensajes[] = 'BD Docker inicializada';
            }
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'No se pudo inicializar la BD Docker. Verifique que el contenedor MySQL esté en ejecución.');
        }

        try {
            $sqlDocente = file_get_contents(base_path('sql/02_bd_docente_institucional.sql'));
            if ($sqlDocente !== false) {
                DB::connection('mysql_docente')->unprepared($sqlDocente);
                $mensajes[] = 'BD Docente (10.0.47.204) inicializada';
            }
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'BD Docker lista, pero falló la conexión a BD Docente (10.0.47.204). Verifique IP y credenciales en .env.');
        }

        return redirect()->route('escolar.index')
            ->with('success', '¡Éxito! ' . implode(' y ', $mensajes) . ' con tablas y datos de prueba.');
    }

    // ==================== CRUD SERVIDOR 1: MYSQL DOCKER ====================

    public function storeEstudiante(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo_estudiante' => 'required|string|max:30',
            'nombre_completo'   => 'required|string|max:150',
            'correo'            => 'required|email|max:120',
            'semestre'          => 'required|integer|min:1|max:10',
            'asignatura_id'     => 'required|integer',
            'nota_parcial'      => 'required|numeric|min:0|max:100',
            'nota_final'        => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::connection('mysql')->transaction(function () use ($validated): void {
                DB::connection('mysql')->insert(
                    'INSERT INTO estudiantes (codigo_estudiante, nombre_completo, correo, semestre) VALUES (?, ?, ?, ?)',
                    [
                        trim($validated['codigo_estudiante']),
                        trim($validated['nombre_completo']),
                        trim($validated['correo']),
                        (int) $validated['semestre'],
                    ]
                );

                $estudianteId = (int) DB::connection('mysql')->getPdo()->lastInsertId();

                DB::connection('mysql')->insert(
                    'INSERT INTO inscripciones (estudiante_id, asignatura_id, gestion_codigo, fecha_inscripcion, estado) VALUES (?, ?, ?, CURDATE(), ?)',
                    [$estudianteId, (int) $validated['asignatura_id'], 'II-2026', 'Inscrito']
                );

                $inscripcionId = (int) DB::connection('mysql')->getPdo()->lastInsertId();
                $promedio = ((float) $validated['nota_parcial'] + (float) $validated['nota_final']) / 2.0;
                $observacion = $promedio >= 51.0 ? 'Aprobado' : 'Reprobado';

                DB::connection('mysql')->insert(
                    'INSERT INTO calificaciones (inscripcion_id, nota_parcial, nota_final, observacion) VALUES (?, ?, ?, ?)',
                    [$inscripcionId, (float) $validated['nota_parcial'], (float) $validated['nota_final'], $observacion]
                );
            });

            return redirect()->route('escolar.index')
                ->with('success', 'Estudiante, inscripción y calificación guardados en BD Docker.');
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'No se pudo registrar en BD Docker. Verifique que el código de estudiante no esté duplicado.');
        }
    }

    public function updateCalificacion(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'nota_parcial' => 'required|numeric|min:0|max:100',
            'nota_final'   => 'required|numeric|min:0|max:100',
        ]);

        try {
            $promedio = ((float) $validated['nota_parcial'] + (float) $validated['nota_final']) / 2.0;
            $observacion = $promedio >= 51.0 ? 'Aprobado' : 'Reprobado';

            DB::connection('mysql')->update(
                'UPDATE calificaciones SET nota_parcial = ?, nota_final = ?, observacion = ? WHERE inscripcion_id = ?',
                [(float) $validated['nota_parcial'], (float) $validated['nota_final'], $observacion, $id]
            );

            return redirect()->route('escolar.index')
                ->with('success', "Calificación actualizada en BD Docker (Nuevo estado: {$observacion}).");
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'Error al actualizar la calificación en BD Docker.');
        }
    }

    public function deleteInscripcion(int $id): RedirectResponse
    {
        try {
            DB::connection('mysql')->delete('DELETE FROM inscripciones WHERE id = ?', [$id]);
            return redirect()->route('escolar.index')
                ->with('success', 'Inscripción eliminada correctamente de BD Docker.');
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'No se pudo eliminar la inscripción de BD Docker.');
        }
    }

    // ==================== CRUD SERVIDOR 2: MYSQL DOCENTE (10.0.47.204) ====================

    public function storeDocenteAsignacion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'codigo_docente'   => 'required|string|max:30',
            'nombre_completo'  => 'required|string|max:150',
            'especialidad'     => 'required|string|max:120',
            'correo'           => 'required|email|max:120',
            'aula_id'          => 'required|integer',
            'asignatura_sigla' => 'required|string|max:20',
            'horario'          => 'required|string|max:80',
        ]);

        try {
            DB::connection('mysql_docente')->transaction(function () use ($validated): void {
                DB::connection('mysql_docente')->insert(
                    'INSERT INTO docentes (codigo_docente, nombre_completo, especialidad, correo) VALUES (?, ?, ?, ?)',
                    [
                        trim($validated['codigo_docente']),
                        trim($validated['nombre_completo']),
                        trim($validated['especialidad']),
                        trim($validated['correo']),
                    ]
                );

                $docenteId = (int) DB::connection('mysql_docente')->getPdo()->lastInsertId();

                DB::connection('mysql_docente')->insert(
                    'INSERT INTO asignaciones_docentes (docente_id, gestion_id, aula_id, asignatura_sigla, horario) VALUES (?, 1, ?, ?, ?)',
                    [$docenteId, (int) $validated['aula_id'], strtoupper(trim($validated['asignatura_sigla'])), trim($validated['horario'])]
                );
            });

            return redirect()->route('escolar.index')
                ->with('success', 'Docente y asignación guardados en el Servidor MySQL del Docente (10.0.47.204).');
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'No se pudo registrar en BD Docente. Verifique que el código de docente no esté duplicado.');
        }
    }

    public function updateHorarioDocente(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'horario' => 'required|string|max:80',
        ]);

        try {
            DB::connection('mysql_docente')->update(
                'UPDATE asignaciones_docentes SET horario = ? WHERE id = ?',
                [trim($validated['horario']), $id]
            );

            return redirect()->route('escolar.index')
                ->with('success', 'Horario actualizado correctamente en el Servidor MySQL del Docente.');
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'Error al actualizar horario en BD Docente.');
        }
    }

    public function deleteAsignacionDocente(int $id): RedirectResponse
    {
        try {
            DB::connection('mysql_docente')->delete('DELETE FROM asignaciones_docentes WHERE id = ?', [$id]);
            return redirect()->route('escolar.index')
                ->with('success', 'Asignación eliminada del Servidor MySQL del Docente.');
        } catch (Throwable $e) {
            return redirect()->route('escolar.index')
                ->with('error', 'Error al eliminar la asignación en BD Docente.');
        }
    }
}