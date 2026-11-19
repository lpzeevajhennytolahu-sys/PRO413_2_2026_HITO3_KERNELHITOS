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

        // Formato nuevo: { estados: {...}, orden: [...] }
        if (isset($guardado['estados']) && is_array($guardado['estados'])) {
            foreach ($catalogo as $slug) {
                if (array_key_exists($slug, $guardado['estados'])) {
                    $defecto[$slug] = (bool) $guardado['estados'][$slug];
                }
            }

            return $defecto;
        }

        // Formato anterior: { slug: bool }
        foreach ($catalogo as $slug) {
            if (array_key_exists($slug, $guardado)) {
                $defecto[$slug] = (bool) $guardado[$slug];
            }
        }

        return $defecto;
    }

    /** @return list<string> */
    public static function orden(): array
    {
        $catalogo = array_keys(config('publico.inspiraciones', []));
        $archivo = self::ruta();
        $ordenGuardado = [];

        if (is_file($archivo)) {
            $guardado = json_decode((string) file_get_contents($archivo), true);
            if (is_array($guardado)) {
                if (isset($guardado['orden']) && is_array($guardado['orden'])) {
                    $ordenGuardado = $guardado['orden'];
                } else {
                    // Compatibilidad: activos en orden del catálogo según flags booleanos
                    foreach ($catalogo as $slug) {
                        if (! empty($guardado[$slug])) {
                            $ordenGuardado[] = $slug;
                        }
                    }
                }
            }
        }

        if ($ordenGuardado === []) {
            $ordenGuardado = config('publico.sugeridos', []);
        }

        $orden = [];
        foreach ($ordenGuardado as $slug) {
            if (is_string($slug) && in_array($slug, $catalogo, true) && ! in_array($slug, $orden, true)) {
                $orden[] = $slug;
            }
        }

        foreach ($catalogo as $slug) {
            if (! in_array($slug, $orden, true)) {
                $orden[] = $slug;
            }
        }

        return $orden;
    }

    /** @return list<string> */
    public static function activos(): array
    {
        $estados = self::estados();

        return array_values(array_filter(
            self::orden(),
            fn (string $slug): bool => (bool) ($estados[$slug] ?? false)
        ));
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

        $ordenEntrada = collect($entrada['orden'] ?? $seleccion)
            ->filter(fn ($slug) => is_string($slug) && in_array($slug, $catalogo, true))
            ->unique()
            ->values()
            ->all();

        foreach ($seleccion as $slug) {
            if (! in_array($slug, $ordenEntrada, true)) {
                $ordenEntrada[] = $slug;
            }
        }

        foreach ($catalogo as $slug) {
            if (! in_array($slug, $ordenEntrada, true)) {
                $ordenEntrada[] = $slug;
            }
        }

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
            json_encode([
                'estados' => $estados,
                'orden' => array_values($ordenEntrada),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n",
            LOCK_EX
        );
    }
}
