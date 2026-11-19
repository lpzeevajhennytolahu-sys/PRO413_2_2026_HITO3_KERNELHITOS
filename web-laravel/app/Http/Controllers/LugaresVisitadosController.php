<?php

namespace App\Http\Controllers;

use App\Support\LugaresVisitados;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LugaresVisitadosController extends Controller
{
    public function index(): View
    {
        abort_unless(session('usuario.rol') === 'ADMIN', 403);

        $estados = LugaresVisitados::estados();
        $catalogo = config('publico.inspiraciones', []);
        $lugares = collect(LugaresVisitados::orden())
            ->map(function (string $slug) use ($catalogo, $estados) {
                $lugar = $catalogo[$slug] ?? null;
                if (! $lugar) {
                    return null;
                }

                return [
                    'slug' => $slug,
                    'nombre' => $lugar['nombre'],
                    'region' => $lugar['region'],
                    'imagen' => $lugar['imagen'],
                    'descripcion' => $lugar['descripcion'] ?? '',
                    'activo' => (bool) ($estados[$slug] ?? false),
                ];
            })
            ->filter()
            ->values();

        return view('admin.lugares-visitados', [
            'lugares' => $lugares,
            'activos' => $lugares->where('activo', true)->count(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless(session('usuario.rol') === 'ADMIN', 403);

        $slugs = array_keys(config('publico.inspiraciones', []));
        $request->validate([
            'activos' => ['nullable', 'array'],
            'activos.*' => ['string', 'in:'.implode(',', $slugs)],
            'orden' => ['nullable', 'array'],
            'orden.*' => ['string', 'in:'.implode(',', $slugs)],
        ]);

        LugaresVisitados::guardar($request->all());

        return redirect()
            ->route('admin.lugares-visitados')
            ->with('estado', 'Los lugares más visitados se actualizaron.');
    }
}
