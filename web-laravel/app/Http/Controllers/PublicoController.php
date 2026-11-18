<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PublicoController extends Controller
{
    use UsaApiPrivada;

    private function ideaViaje(Request $request): array
    {
        $datos = $request->validate([
            'destinos' => ['nullable', 'string', 'max:200'],
            'fechaInicio' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fechaFin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'personas' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'moneda' => ['nullable', 'in:BOB,USD,EUR'],
            'hotel' => ['nullable', 'string', 'max:200'],
            'alimentacion' => ['nullable', 'string', 'max:200'],
            'transporte' => ['nullable', 'string', 'max:200'],
            'idea' => ['nullable', 'string', 'max:400'],
            'tipoRuta' => ['nullable', 'string', 'max:80'],
        ]);

        $lugares = collect(config('publico.inspiraciones', []))
            ->map(fn (array $lugar, string $slug) => [
                'slug' => $slug,
                'nombre' => $lugar['nombre'],
                'region' => $lugar['region'],
                'descripcion' => $lugar['descripcion'] ?? '',
                'imagen' => asset('images/thaki/'.$lugar['imagen']),
                'url' => route('publico.inspiracion', $slug),
                'lat' => (float) ($lugar['lat'] ?? 0),
                'lng' => (float) ($lugar['lng'] ?? 0),
            ])
            ->filter(fn (array $lugar) => $lugar['lat'] && $lugar['lng'])
            ->values();

        $destino = trim((string) ($datos['destinos'] ?? $datos['idea'] ?? $request->session()->get('viaje_borrador.destinos', '')));
        $activo = $lugares->first(function (array $lugar) use ($destino) {
            return $destino !== '' && str_contains(mb_strtolower($lugar['nombre']), mb_strtolower($destino));
        });

        return [
            'formulario' => [
                'destinos' => $destino,
                'fechaInicio' => $datos['fechaInicio'] ?? $request->session()->get('viaje_borrador.fechaInicio'),
                'fechaFin' => $datos['fechaFin'] ?? $request->session()->get('viaje_borrador.fechaFin'),
                'personas' => $datos['personas'] ?? $request->session()->get('viaje_borrador.personas', 2),
                'presupuesto' => $datos['presupuesto'] ?? $request->session()->get('viaje_borrador.presupuesto'),
                'moneda' => $datos['moneda'] ?? $request->session()->get('viaje_borrador.moneda', 'USD'),
                'hotel' => $datos['hotel'] ?? $request->session()->get('viaje_borrador.hotel'),
                'alimentacion' => $datos['alimentacion'] ?? $request->session()->get('viaje_borrador.alimentacion'),
                'transporte' => $datos['transporte'] ?? $request->session()->get('viaje_borrador.transporte'),
                'idea' => $datos['idea'] ?? $destino,
                'tipoRuta' => $datos['tipoRuta'] ?? 'Viaje a medida',
            ],
            'lugares' => $lugares,
            'lugarActivo' => $activo['slug'] ?? null,
            'sugerencias' => $lugares->take(3)->values(),
        ];
    }

    public function asistente(Request $request): View
    {
        return view('publico.asistente', $this->ideaViaje($request));
    }

    public function viaje(Request $request): View
    {
        return view('publico.viaje', $this->ideaViaje($request));
    }

    public function planificar(Request $request): RedirectResponse
    {
        if (! $request->filled('destinos') && $request->filled('idea')) {
            $request->merge(['destinos' => $request->input('idea')]);
        }
        $datos = $request->validate([
            'destinos' => ['required', 'string', 'max:200'],
            'fechaInicio' => ['nullable', 'required_with:fechaFin', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fechaFin' => ['nullable', 'required_with:fechaInicio', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'personas' => ['required', 'integer', 'min:1', 'max:1000'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'moneda' => ['required', 'in:BOB,USD,EUR'],
            'hotel' => ['nullable', 'string', 'max:200'], 'alimentacion' => ['nullable', 'string', 'max:200'], 'transporte' => ['nullable', 'string', 'max:200'],
            'idea' => ['nullable', 'string', 'max:400'],
            'tipoRuta' => ['nullable', 'string', 'max:80'],
        ], [
            'destinos.required' => 'Cuéntanos qué destino te gustaría conocer.',
            'fechaInicio.required_with' => 'Indica la fecha de inicio del viaje.',
            'fechaFin.required_with' => 'Indica la fecha de regreso.',
            'fechaInicio.after_or_equal' => 'La fecha de inicio debe ser hoy o posterior.',
            'fechaFin.after_or_equal' => 'La fecha de regreso debe ser igual o posterior a la de inicio.',
            'personas.min' => 'El viaje debe tener al menos un viajero.',
            'personas.max' => 'Indica una cantidad de viajeros entre 1 y 1000.',
        ]);
        if (in_array($request->session()->get('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return redirect()->route('publico.inicio')->withErrors(['viaje' => 'Para planificar tu viaje, ingresa con una cuenta de cliente.']);
        }
        $request->session()->put('viaje_borrador', $datos);
        if ($request->session()->get('usuario.rol') === 'CLIENTE') {
            return redirect()->route('portal.solicitud');
        }

        return redirect()->route('acceso.registro')->with('exito', 'Conservamos tus preferencias. Crea tu cuenta de cliente o ingresa para continuar con tu viaje.');
    }

    public function guardarRuta(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombreRuta' => ['required', 'string', 'max:120'],
            'destinos' => ['required', 'string', 'max:2000'],
            'tipoRuta' => ['required', 'string', 'max:80'],
            'idea' => ['nullable', 'string', 'max:2000'],
            'distanciaKm' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'desnivelM' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'paradas' => ['required', 'integer', 'min:2', 'max:50'],
            'personas' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'moneda' => ['nullable', 'in:BOB,USD,EUR'],
            'fechaInicio' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fechaFin' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'presupuesto' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'hotel' => ['nullable', 'string', 'max:200'],
            'alimentacion' => ['nullable', 'string', 'max:200'],
            'transporte' => ['nullable', 'string', 'max:200'],
        ], [
            'destinos.required' => 'Añade al menos dos lugares a tu ruta antes de guardar.',
            'paradas.min' => 'Tu ruta necesita al menos dos paradas.',
        ]);

        if (in_array($request->session()->get('usuario.rol'), ['ADMIN', 'ADMINISTRATIVO'], true)) {
            return redirect()->route('publico.inicio')->withErrors(['viaje' => 'Para guardar tu ruta, ingresa con una cuenta de cliente.']);
        }

        $paradas = array_values(array_filter(array_map('trim', preg_split('/\s*·\s*|\r\n|\n/', $datos['destinos']) ?: [])));
        $ruta = [
            ...$datos,
            'paradasLista' => $paradas,
            'guardadaEn' => now()->timezone('America/La_Paz')->format('d/m/Y H:i'),
        ];
        $request->session()->put('ruta_personalizada', $ruta);
        $request->session()->put('viaje_borrador', [
            'destinos' => implode(' · ', array_slice($paradas, 0, 8)),
            'tipoRuta' => $datos['tipoRuta'],
            'idea' => $datos['idea'] ?? null,
            'personas' => $datos['personas'] ?? 2,
            'moneda' => $datos['moneda'] ?? 'USD',
            'fechaInicio' => $datos['fechaInicio'] ?? null,
            'fechaFin' => $datos['fechaFin'] ?? null,
            'presupuesto' => $datos['presupuesto'] ?? null,
            'hotel' => $datos['hotel'] ?? null,
            'alimentacion' => $datos['alimentacion'] ?? null,
            'transporte' => $datos['transporte'] ?? null,
            'intereses' => $this->documentoRutaTexto($ruta),
        ]);

        if ($request->session()->get('usuario.rol') !== 'CLIENTE' || ! $request->session()->has('api_token')) {
            return redirect()->route('acceso.registro')->with('exito', 'Guardamos tu ruta personalizada. Crea tu cuenta o ingresa para enviarla a tu asesor Thaki.');
        }

        $this->consultarApi('POST', '/api/cliente/mensajes', [
            'contenido' => $this->documentoRutaTexto($ruta),
        ]);

        return redirect()->route('portal.mensajes')->with('exito', 'Enviamos tu ruta personalizada al chat con el asesor. También puedes abrir el documento de la ruta.');
    }

    public function documentoRuta(Request $request): View|RedirectResponse
    {
        $ruta = $request->session()->get('ruta_personalizada');
        if (! is_array($ruta)) {
            return redirect()->route('publico.asistente')->withErrors(['viaje' => 'Todavía no hay una ruta guardada para documentar.']);
        }

        return view('publico.ruta-documento', ['ruta' => $ruta]);
    }

    private function documentoRutaTexto(array $ruta): string
    {
        $lineas = [
            'RUTA PERSONALIZADA THAKI VOYAGE',
            'Título: '.($ruta['nombreRuta'] ?? 'Ruta personalizada'),
            'Actividad: '.($ruta['tipoRuta'] ?? 'Viaje a medida'),
            'Paradas ('.(int) ($ruta['paradas'] ?? count($ruta['paradasLista'] ?? [])).'):',
        ];
        foreach ($ruta['paradasLista'] ?? [] as $i => $parada) {
            $lineas[] = ($i + 1).'. '.$parada;
        }
        if (! empty($ruta['distanciaKm'])) {
            $lineas[] = 'Distancia aprox.: '.number_format((float) $ruta['distanciaKm'], 2, ',', '.').' km';
        }
        if (! empty($ruta['desnivelM'])) {
            $lineas[] = 'Desnivel aprox.: '.number_format((float) $ruta['desnivelM'], 0, ',', '.').' m';
        }
        if (! empty($ruta['presupuesto'])) {
            $moneda = $ruta['moneda'] ?? 'USD';
            $lineas[] = 'Presupuesto: '.number_format((float) $ruta['presupuesto'], 2, ',', '.').' '.$moneda;
        } elseif (! empty($ruta['moneda'])) {
            $lineas[] = 'Moneda preferida: '.$ruta['moneda'];
        }
        if (! empty($ruta['fechaInicio']) || ! empty($ruta['fechaFin'])) {
            $lineas[] = 'Fechas: '.($ruta['fechaInicio'] ?? '—').' → '.($ruta['fechaFin'] ?? '—');
        }
        if (! empty($ruta['personas'])) {
            $lineas[] = 'Viajeros: '.$ruta['personas'];
        }
        if (! empty($ruta['idea'])) {
            $lineas[] = 'Comentario del viajero: '.$ruta['idea'];
        }
        $lineas[] = 'Quiero que me ayudes a armar esta ruta con el equipo Thaki.';

        return implode("\n", $lineas);
    }

    public function inicio(): View
    {
        $datos = ['empresa' => [], 'destinos' => [], 'testimonios' => []];
        $errorCatalogo = false;
        try {
            $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->connectTimeout(2)->timeout(6)->get('/api/publico/inicio');
            if ($response->successful()) {
                $datos = [...$datos, ...$response->json()];
            } else {
                $errorCatalogo = true;
            }
        } catch (ConnectionException $e) {
            $errorCatalogo = true;
        }

        return view('publico.inicio', [...$datos, 'errorCatalogo' => $errorCatalogo]);
    }

    public function inspiracion(string $slug): View
    {
        $destino = config('publico.inspiraciones.'.$slug);
        abort_unless(is_array($destino), 404);

        return view('publico.destino', ['destino' => $destino, 'editorial' => true]);
    }

    public function destino(string $codigo): View
    {
        $response = Http::baseUrl(config('services.quarkus.url'))->acceptJson()->timeout(10)->get('/api/publico/destinos/'.rawurlencode($codigo));
        abort_if($response->status() === 404, 404);

        return view('publico.destino', ['destino' => $response->throw()->json(), 'editorial' => false]);
    }
}
