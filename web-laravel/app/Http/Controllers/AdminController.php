<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $api = Http::baseUrl(config('services.quarkus.url'))
            ->withToken((string) session('api_token', ''))
            ->acceptJson()
            ->timeout(15);

        $inventario = [
            'productos' => 0,
            'unidades' => 0,
            'arreglo' => 0,
            'bajas' => 0,
        ];
        $atencion = [
            'clientes' => 0,
            'clientesPendientes' => 0,
            'solicitudes' => 0,
            'solicitudesNuevas' => 0,
            'conversaciones' => 0,
            'mensajesNuevos' => 0,
            'reservasNuevas' => 0,
        ];
        $mensual = [];
        $calendario = ['mes' => now('America/La_Paz')->format('Y-m'), 'dias' => [], 'hoy' => now('America/La_Paz')->day];
        $error = null;
        $errores = [];

        try {
            $productos = $api->get('/api/inventario/productos')->throw()->json();
            if (! is_array($productos) || ! array_is_list($productos)) {
                throw new \UnexpectedValueException('La API no devolvió una lista de productos.');
            }

            $coleccion = collect($productos)->reject(fn (array $producto): bool => str_starts_with((string) ($producto['codigo'] ?? ''), 'DEMO-')
                || str_starts_with((string) ($producto['nombre'] ?? ''), '[DEMO]'));

            $inventario = [
                'productos' => $coleccion->count(),
                'unidades' => (int) $coleccion->sum('unidadesRegistradas'),
                'arreglo' => (int) $coleccion->sum('unidadesRequierenArreglo'),
                'bajas' => (int) $coleccion->sum('unidadesConBaja'),
            ];
        } catch (ConnectionException|RequestException|\UnexpectedValueException $e) {
            report($e);
            $errores[] = 'inventario';
        }

        $solicitudes = collect();
        $viajes = collect();

        try {
            $clientes = collect($api->get('/api/atencion/clientes')->throw()->json() ?? []);
            $panel = $api->get('/api/atencion/panel')->throw()->json() ?? [];
            $solicitudes = collect($panel['solicitudes'] ?? []);
            $conversaciones = collect($panel['conversaciones'] ?? []);

            $atencion = [
                'clientes' => $clientes->count(),
                'clientesPendientes' => $clientes->filter(fn (array $c): bool => ($c['solicitudesNuevas'] ?? 0) + ($c['mensajesNuevos'] ?? 0) + ($c['reservasNuevas'] ?? 0) > 0)->count(),
                'solicitudes' => $solicitudes->count(),
                'solicitudesNuevas' => $solicitudes->filter(fn (array $s): bool => in_array(($s['estado'] ?? ''), ['NUEVA', 'PENDIENTE', 'RECIBIDA'], true))->count(),
                'conversaciones' => $conversaciones->count(),
                'mensajesNuevos' => (int) $clientes->sum('mensajesNuevos'),
                'reservasNuevas' => (int) $clientes->sum('reservasNuevas'),
            ];

            $mes = Carbon::now('America/La_Paz')->startOfMonth();
            $desde = $mes->copy()->subMonths(8)->startOfMonth();
            $hasta = $mes->copy()->endOfMonth();
            $viajes = collect($api->get('/api/atencion/calendario', [
                'desde' => $desde->toDateString(),
                'hasta' => $hasta->toDateString(),
            ])->throw()->json() ?? []);

            $calendario = $this->armarCalendario($mes, $viajes);
        } catch (ConnectionException|RequestException|\UnexpectedValueException $e) {
            report($e);
            $errores[] = 'atencion';
        }

        $mensual = $this->serieMensual($solicitudes, $viajes);
        $salud = $inventario['unidades'] > 0
            ? (int) round((($inventario['unidades'] - $inventario['arreglo']) / max($inventario['unidades'], 1)) * 100)
            : 0;
        $atencionPct = $atencion['clientes'] > 0
            ? (int) round((($atencion['clientes'] - $atencion['clientesPendientes']) / max($atencion['clientes'], 1)) * 100)
            : 0;

        if ($errores !== []) {
            $error = count($errores) === 2
                ? 'No pudimos cargar todas las estadísticas. Comprueba que la API y MySQL estén activos.'
                : 'Algunas estadísticas no están disponibles en este momento.';
        }

        $resumen = $inventario;
        $rol = session('usuario.rol', 'ADMINISTRATIVO');
        $nombre = session('usuario.nombre', 'Equipo Thaki');

        return view('admin.index', compact(
            'resumen', 'inventario', 'atencion', 'mensual', 'calendario', 'salud', 'atencionPct', 'error', 'rol', 'nombre'
        ));
    }

    private function serieMensual($solicitudes, $viajes): array
    {
        $meses = [];
        $cursor = Carbon::now('America/La_Paz')->startOfMonth()->subMonths(8);
        for ($i = 0; $i < 9; $i++) {
            $clave = $cursor->format('Y-m');
            $meses[$clave] = [
                'clave' => $clave,
                'etiqueta' => mb_strtoupper($cursor->translatedFormat('M')),
                'solicitudes' => 0,
                'viajes' => 0,
            ];
            $cursor->addMonth();
        }

        foreach ($solicitudes as $solicitud) {
            $fecha = $solicitud['fechaInicio'] ?? $solicitud['fecha'] ?? null;
            if (! $fecha) {
                continue;
            }
            $clave = Carbon::parse($fecha, 'America/La_Paz')->format('Y-m');
            if (isset($meses[$clave])) {
                $meses[$clave]['solicitudes']++;
            }
        }

        foreach ($viajes as $viaje) {
            $fecha = $viaje['fechaInicio'] ?? null;
            if (! $fecha) {
                continue;
            }
            $clave = Carbon::parse($fecha, 'America/La_Paz')->format('Y-m');
            if (isset($meses[$clave])) {
                $meses[$clave]['viajes']++;
            }
        }

        return array_values($meses);
    }

    private function armarCalendario(Carbon $mes, $viajes): array
    {
        $inicio = $mes->copy()->startOfWeek(Carbon::SUNDAY);
        $fin = $mes->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $diasConViaje = [];
        foreach ($viajes as $viaje) {
            $desde = Carbon::parse($viaje['fechaInicio'] ?? $mes->toDateString(), 'America/La_Paz')->startOfDay();
            $hasta = Carbon::parse($viaje['fechaFin'] ?? $viaje['fechaInicio'] ?? $mes->toDateString(), 'America/La_Paz')->startOfDay();
            for ($d = $desde->copy(); $d->lte($hasta); $d->addDay()) {
                if ($d->isSameMonth($mes)) {
                    $diasConViaje[$d->day] = true;
                }
            }
        }

        $dias = [];
        for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {
            $dias[] = [
                'dia' => $fecha->day,
                'actual' => $fecha->isSameMonth($mes),
                'hoy' => $fecha->isSameDay(Carbon::now('America/La_Paz')),
                'activo' => $fecha->isSameMonth($mes) && isset($diasConViaje[$fecha->day]),
            ];
        }

        return [
            'mes' => $mes->format('Y-m'),
            'titulo' => $mes->translatedFormat('F Y'),
            'hoy' => Carbon::now('America/La_Paz')->day,
            'dias' => $dias,
        ];
    }
}
