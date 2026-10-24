<?php

declare(strict_types=1);

namespace App\Services;

class ResumenEstudianteCurso extends EntidadAcademicaBase
{
    private string $siglaAsignatura;
    private string $nombreAsignatura;
    private float $promedioFinal;
    private string $docenteAsignado;
    private string $aulaAsignada;
    private string $horario;

    public function __construct(
        string $codigoEstudiante,
        string $nombreEstudiante,
        string $siglaAsignatura,
        string $nombreAsignatura,
        float $promedioFinal,
        string $docenteAsignado,
        string $aulaAsignada,
        string $horario
    ) {
        parent::__construct($codigoEstudiante, $nombreEstudiante);
        $this->siglaAsignatura = $siglaAsignatura;
        $this->nombreAsignatura = $nombreAsignatura;
        $this->promedioFinal = $promedioFinal;
        $this->docenteAsignado = $docenteAsignado;
        $this->aulaAsignada = $aulaAsignada;
        $this->horario = $horario;
    }

    public function getPromedioFinal(): float
    {
        return $this->promedioFinal;
    }

    public function obtenerEstadoAcademico(): string
    {
        if ($this->promedioFinal >= 51.0) {
            return 'APROBADO';
        }
        if ($this->promedioFinal > 0.0) {
            return 'REPROBADO';
        }
        return 'EN CURSO';
    }

    public function toArray(): array
    {
        return [
            'codigo_estudiante' => $this->getIdentificador(),
            'estudiante'        => $this->getNombre(),
            'sigla'             => $this->siglaAsignatura,
            'asignatura'        => $this->nombreAsignatura,
            'promedio'          => number_format($this->promedioFinal, 2),
            'estado'            => $this->obtenerEstadoAcademico(),
            'docente'           => $this->docenteAsignado,
            'aula'              => $this->aulaAsignada,
            'horario'           => $this->horario,
        ];
    }
}