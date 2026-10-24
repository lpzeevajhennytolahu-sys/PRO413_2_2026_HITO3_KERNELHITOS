<?php

declare(strict_types=1);

namespace App\Services;

abstract class EntidadAcademicaBase
{
    protected string $identificador;
    protected string $nombre;

    public function __construct(string $identificador, string $nombre)
    {
        $this->identificador = $identificador;
        $this->nombre = $nombre;
    }

    public function getIdentificador(): string
    {
        return $this->identificador;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    abstract public function obtenerEstadoAcademico(): string;
}