<?php

declare(strict_types=1);

namespace App\Services;

class ProcesadorEstructuraDatos
{
    /**
     * Estructura de datos: Arreglo Asociativo Indexado (Hash Map) por clave 'asignatura_sigla'
     * Permite relacionar en memoria O(1) las inscripciones del servidor MySQL Docker
     * con los docentes y aulas del servidor MySQL de la red local del docente.
     */
    public function cruzarDatosAmbosServidores(array $inscripcionesDocker, array $asignacionesDocente): array
    {
        $mapaDocentesPorSigla = [];

        foreach ($asignacionesDocente as $asig) {
            $sigla = strtoupper(trim((string) $asig->asignatura_sigla));
            $mapaDocentesPorSigla[$sigla] = [
                'docente' => (string) $asig->docente_nombre,
                'aula'    => (string) ($asig->codigo_aula . ' - ' . $asig->bloque),
                'horario' => (string) $asig->horario,
            ];
        }

        $listaIntegrada = [];

        foreach ($inscripcionesDocker as $insc) {
            $sigla = strtoupper(trim((string) $insc->sigla));
            $datosDocente = $mapaDocentesPorSigla[$sigla] ?? [
                'docente' => 'Sin docente asignado en BD Docente',
                'aula'    => 'Sin aula',
                'horario' => 'Por definir',
            ];

            $parcial  = (float) ($insc->nota_parcial ?? 0.0);
            $final    = (float) ($insc->nota_final ?? 0.0);
            $promedio = ($parcial + $final) / 2.0;

            $entidad = new ResumenEstudianteCurso(
                (string) $insc->codigo_estudiante,
                (string) $insc->nombre_completo,
                $sigla,
                (string) $insc->asignatura_nombre,
                $promedio,
                $datosDocente['docente'],
                $datosDocente['aula'],
                $datosDocente['horario']
            );

            $listaIntegrada[] = $entidad->toArray();
        }

        return $listaIntegrada;
    }
}