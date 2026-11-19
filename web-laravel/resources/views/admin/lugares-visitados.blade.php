@extends('layouts.admin')
@section('titulo', 'Lugares más visitados')
@section('contenido')
<x-page-header title="Lugares más visitados" description="Activa, oculta y ordena los destinos que aparecen como sugerencias en la portada." eyebrow="PORTADA PÚBLICA">
    <a class="boton boton-secundario" href="{{ route('publico.inicio') }}#planifica" target="_blank" rel="noopener">Ver en la web ↗</a>
</x-page-header>

@if(session('estado'))
    <div class="aviso" role="status">{{ session('estado') }}</div>
@endif

<form method="POST" action="{{ route('admin.lugares-visitados.guardar') }}" class="lugares-admin" id="form-lugares">
    @csrf
    <div class="lugares-admin-resumen">
        <div>
            <p><strong id="conteo-activos">{{ $activos }}</strong> lugar{{ $activos === 1 ? '' : 'es' }} visible{{ $activos === 1 ? '' : 's' }} en el carrusel.</p>
            <p class="lugares-ayuda">Usa las flechas para colocar el orden. Solo los marcados como visibles aparecen en la portada.</p>
        </div>
        <button class="boton" type="submit">Guardar cambios</button>
    </div>

    <div class="lugares-admin-lista" id="lista-lugares">
        @foreach($lugares as $lugar)
            <article class="lugar-admin-tarjeta" data-slug="{{ $lugar['slug'] }}">
                <input type="hidden" name="orden[]" value="{{ $lugar['slug'] }}">
                <label class="lugar-toggle">
                    <input type="checkbox" name="activos[]" value="{{ $lugar['slug'] }}" @checked($lugar['activo'])>
                    <span class="lugar-switch" aria-hidden="true"></span>
                    <span class="solo-lectores">Activar {{ $lugar['nombre'] }}</span>
                </label>
                <img src="{{ asset('images/thaki/'.$lugar['imagen']) }}" alt="{{ $lugar['nombre'] }}">
                <div class="lugar-info">
                    <span>{{ $lugar['region'] }}</span>
                    <strong>{{ $lugar['nombre'] }}</strong>
                    <p>{{ \Illuminate\Support\Str::limit($lugar['descripcion'], 110) }}</p>
                </div>
                <em class="lugar-estado">{{ $lugar['activo'] ? 'Visible' : 'Oculto' }}</em>
                <div class="lugar-orden">
                    <button type="button" class="boton boton-secundario boton-pequeno" data-mover="-1" aria-label="Subir {{ $lugar['nombre'] }}">↑</button>
                    <button type="button" class="boton boton-secundario boton-pequeno" data-mover="1" aria-label="Bajar {{ $lugar['nombre'] }}">↓</button>
                </div>
            </article>
        @endforeach
    </div>

    <div class="lugares-admin-resumen">
        <p>Si quitas todos, el bloque de sugerencias no aparece en la portada.</p>
        <button class="boton" type="submit">Guardar cambios</button>
    </div>
</form>

<script>
(() => {
    const lista = document.getElementById('lista-lugares');
    const conteo = document.getElementById('conteo-activos');
    if (!lista) return;

    const actualizarEstados = () => {
        lista.querySelectorAll('.lugar-admin-tarjeta').forEach((tarjeta) => {
            const caja = tarjeta.querySelector('input[type="checkbox"]');
            const estado = tarjeta.querySelector('.lugar-estado');
            if (estado && caja) estado.textContent = caja.checked ? 'Visible' : 'Oculto';
            tarjeta.classList.toggle('activo', !!caja?.checked);
        });
        if (conteo) conteo.textContent = lista.querySelectorAll('input[type="checkbox"]:checked').length;
    };

    lista.addEventListener('change', (event) => {
        if (event.target.matches('input[type="checkbox"]')) actualizarEstados();
    });

    lista.addEventListener('click', (event) => {
        const boton = event.target.closest('[data-mover]');
        if (!boton) return;
        const tarjeta = boton.closest('.lugar-admin-tarjeta');
        const delta = Number(boton.getAttribute('data-mover'));
        if (!tarjeta || !delta) return;
        if (delta < 0 && tarjeta.previousElementSibling) {
            lista.insertBefore(tarjeta, tarjeta.previousElementSibling);
        }
        if (delta > 0 && tarjeta.nextElementSibling) {
            lista.insertBefore(tarjeta.nextElementSibling, tarjeta);
        }
    });

    actualizarEstados();
})();
</script>
@endsection
