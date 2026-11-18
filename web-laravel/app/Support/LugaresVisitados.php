<?php

namespace App\Support;

class LugaresVisitados
{
    public static function ruta(): string
    {
        return storage_path('app/lugares-visitados.json');
    }

    /** @return array<string, bool> */
    public static function estados(): array
    {
        $catalogo = array_keys(config('publico.inspiraciones', []));
        $defecto = array_fill_keys($catalogo, false);
        foreach (config('publico.sugeridos', []) as $slug) {
            if (array_key_exists($slug, $defecto)) {
                $defecto[$slug] = true;
            }
        }

        $archivo = self::ruta();
        if (! is_file($archivo)) {
            return $defecto;
        }

        $guardado = json_decode((string) file_get_contents($archivo), true);
        if (! is_array($guardado)) {
            return $defecto;
        }

        foreach ($catalogo as $slug) {
            if (array_key_exists($slug, $guardado)) {
                $defecto[$slug] = (bool) $guardado[$slug];
            }
        }

        return $defecto;
    }

    /** @return list<string> */
    public static function activos(): array
    {
        return array_values(array_keys(array_filter(self::estados())));
    }

    /** @param  array<string, mixed>  $entrada */
    public static function guardar(array $entrada): void
    {
        $catalogo = array_keys(config('publico.inspiraciones', []));
        $seleccion = collect($entrada['activos'] ?? [])
            ->filter(fn ($slug) => is_string($slug) && in_array($slug, $catalogo, true))
            ->unique()
            ->values()
            ->all();

        $estados = array_fill_keys($catalogo, false);
        foreach ($seleccion as $slug) {
            $estados[$slug] = true;
        }

        $archivo = self::ruta();
        $dir = dirname($archivo);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $archivo,
            json_encode($estados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n",
            LOCK_EX
        );
    }
}
