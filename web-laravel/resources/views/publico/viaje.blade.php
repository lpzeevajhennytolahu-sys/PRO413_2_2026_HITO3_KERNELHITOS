@extends('layouts.publico')
@section('titulo', 'Asistente de viaje')
@section('clase', 'pagina-publica publico-con-cabecera')
@section('publico-clase', 'pagina-viaje')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('css/viaje.css') }}?v={{ filemtime(public_path('css/viaje.css')) }}" media="screen">
@endpush
@section('contenido')
<section class="viaje" data-viaje>
    <aside class="viaje-panel">
        <a class="viaje-volver" href="{{ route('publico.inicio') }}#planifica" aria-label="Volver al inicio">←</a>
        <form method="GET" action="{{ route('publico.planificar') }}" class="viaje-formulario" data-viaje-form>
            <h1>Diseña tu viaje</h1>
            <label for="viaje-destino">Destinos
                <select id="viaje-destino" name="destinos" required data-viaje-destino>
                    <option value="" disabled @selected(! $formulario['destinos'])>Elige un lugar</option>
                    @foreach($lugares as $lugar)
                        <option value="{{ $lugar['nombre'] }}" data-slug="{{ $lugar['slug'] }}" @selected($formulario['destinos'] === $lugar['nombre'] || str_contains(mb_strtolower((string) $formulario['destinos']), mb_strtolower($lugar['nombre'])))>{{ $lugar['nombre'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="viaje-fechas">
                <label for="viaje-inicio">Fechas
                    <input id="viaje-inicio" type="date" name="fechaInicio" min="{{ now()->format('Y-m-d') }}" value="{{ $formulario['fechaInicio'] }}">
                </label>
                <label for="viaje-fin">Regreso
                    <input id="viaje-fin" type="date" name="fechaFin" min="{{ now()->format('Y-m-d') }}" value="{{ $formulario['fechaFin'] }}">
                </label>
            </div>
            <label for="viaje-ruta">Tipos de ruta
                <select id="viaje-ruta" name="tipoRuta">
                    @foreach(['Viaje a medida', 'Naturaleza', 'Cultura', 'Trekking', 'Luna de miel'] as $tipo)
                        <option value="{{ $tipo }}" @selected(($formulario['tipoRuta'] ?? 'Viaje a medida') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </label>
            <label for="viaje-personas">Viajeros
                <input id="viaje-personas" type="number" name="personas" min="1" max="1000" value="{{ $formulario['personas'] }}" required>
            </label>
            <div class="viaje-detalle">
                <h2>¿Qué tienes pensado hacer?</h2>
                <label class="viaje-idea" for="viaje-idea">
                    <textarea id="viaje-idea" name="idea" rows="3" maxlength="400" placeholder="Ritmo, intereses, qué te gustaría vivir…">{{ $formulario['idea'] !== $formulario['destinos'] ? $formulario['idea'] : '' }}</textarea>
                </label>
                <p class="viaje-detalle-nota">Opcional · ayuda a tu asesor a entender mejor tu viaje.</p>
            </div>
            <input type="hidden" name="moneda" value="{{ $formulario['moneda'] }}">
            <input type="hidden" name="presupuesto" value="{{ $formulario['presupuesto'] }}">
            <input type="hidden" name="hotel" value="{{ $formulario['hotel'] }}">
            <input type="hidden" name="alimentacion" value="{{ $formulario['alimentacion'] }}">
            <input type="hidden" name="transporte" value="{{ $formulario['transporte'] }}">
            <button class="boton viaje-crear" type="submit" data-viaje-crear disabled>Crear viaje ✦</button>
            <p class="viaje-nota">Marca un lugar en el mapa para verlo. Luego crea tu viaje y un asesor lo afinará contigo.</p>
        </form>
        <div class="viaje-ficha" data-viaje-ficha hidden>
            <img alt="" data-viaje-foto>
            <div>
                <span class="etiqueta" data-viaje-region></span>
                <h2 data-viaje-nombre></h2>
                <p data-viaje-texto></p>
                <a class="enlace" data-viaje-enlace href="#">Ver detalle ↗</a>
                <button type="button" class="boton secundario pequeno" data-viaje-elegir>Usar este destino</button>
            </div>
        </div>
    </aside>
    <div class="viaje-mapa-wrap">
        <div id="viaje-mapa" class="viaje-mapa" role="application" aria-label="Mapa de lugares registrados"></div>
        <div class="viaje-mapa-leyenda">Lugares registrados · toca un marcador para ver la minivista</div>
    </div>
</section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
window.THAKI_VIAJE = {
    lugares: @json($lugares),
    activo: @json($lugarActivo)
};
</script>
<script src="{{ asset('js/viaje.js') }}?v={{ filemtime(public_path('js/viaje.js')) }}"></script>
@endsection
