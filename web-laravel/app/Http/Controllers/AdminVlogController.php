<?php

namespace App\Http\Controllers;

use App\Support\Vlog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminVlogController extends Controller
{
    public function index(): View
    {
        abort_unless(session('usuario.rol') === 'ADMIN', 403);

        $publicaciones = Vlog::todos();

        return view('admin.vlog', [
            'publicaciones' => $publicaciones,
            'total' => count($publicaciones),
            'comentarios' => collect($publicaciones)->sum(fn (array $post): int => count($post['comentarios'] ?? [])),
        ]);
    }

    public function eliminarPublicacion(string $id): RedirectResponse
    {
        abort_unless(session('usuario.rol') === 'ADMIN', 403);
        abort_unless(Vlog::buscar($id), 404);
        Vlog::eliminarPublicacion($id);

        return redirect()
            ->route('admin.vlog')
            ->with('estado', 'La publicación se eliminó del vlog.');
    }

    public function eliminarComentario(string $id, string $comentario): RedirectResponse
    {
        abort_unless(session('usuario.rol') === 'ADMIN', 403);
        abort_unless(Vlog::buscar($id), 404);
        abort_unless(Vlog::eliminarComentario($id, $comentario), 404);

        return redirect()
            ->to(route('admin.vlog').'#vlog-'.$id)
            ->with('estado', 'El comentario se eliminó.');
    }
}
