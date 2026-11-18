@extends('layouts.admin')
@section('titulo', 'Lugares más visitados')
@section('contenido')
<x-page-header title="Lugares más visitados" description="Elige qué destinos aparecen como sugerencias en la portada, junto a Otras preferencias." eyebrow="PORTADA PÚBLICA">
    <a class="boton boton-secundario" href="{{ route('publico.inicio') }}#planifica">Ver en la web</a>
</x-page-header>

@if(session('estado'))
    <div class="aviso" role="status">{{ session('estado') }}</div>
@endif

<form method="POST" action="{{ route('admin.lugares-visitados.guardar') }}" class="lugares-admin">
    @csrf
    <div class="lugares-admin-resumen">
        <p><strong>{{ $activos }}</strong> lugar{{ $activos === 1 ? '' : 'es' }} visible{{ $activos === 1 ? '' : 's' }} en el carrusel.</p>
        <button class="boton" type="submit">Guardar cambios</button>
    </div>
    <div class="lugares-admin-lista">
        @foreach($lugares as $lugar)
            <label class="lugar-admin-tarjeta">
                <input type="checkbox" name="activos[]" value="{{ $lugar['slug'] }}" @checked($lugar['activo'])>
                <img src="{{ asset('images/thaki/'.$lugar['imagen']) }}" alt="{{ $lugar['nombre'] }}">
                <div>
                    <span>{{ $lugar['region'] }}</span>
                    <strong>{{ $lugar['nombre'] }}</strong>
                    <p>{{ \Illuminate\Support\Str::limit($lugar['descripcion'], 110) }}</p>
                </div>
                <em>{{ $lugar['activo'] ? 'Visible' : 'Oculto' }}</em>
            </label>
        @endforeach
    </div>
    <div class="lugares-admin-resumen">
        <p>Marca los lugares que quieres mostrar. Si quitas todos, el bloque de sugerencias no aparece en la portada.</p>
        <button class="boton" type="submit">Guardar cambios</button>
    </div>
</form>
<script>
document.querySelectorAll('.lugar-admin-tarjeta input').forEach(function (caja) {
    var etiqueta = caja.closest('.lugar-admin-tarjeta').querySelector('em');
    var resumen = document.querySelector('.lugares-admin-resumen strong');
    var actualizar = function () {
        etiqueta.textContent = caja.checked ? 'Visible' : 'Oculto';
        if (resumen) resumen.textContent = document.querySelectorAll('.lugar-admin-tarjeta input:checked').length;
    };
    caja.addEventListener('change', actualizar);
});
</script>
@endsection
