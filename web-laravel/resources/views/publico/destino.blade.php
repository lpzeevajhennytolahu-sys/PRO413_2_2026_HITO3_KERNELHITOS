@extends('layouts.publico')
@section('titulo', $destino['nombre'])
@section('contenido')
<section class="detalle-hero" style="--foto:url('{{ $editorial ? asset('images/thaki/'.$destino['imagen']) : asset('images/thaki/illimani.jpg') }}')">
    <div>
        <a href="{{ route('publico.inicio') }}#inspiraciones">← Lugares más visitados</a>
        <span class="etiqueta clara">{{ $destino['region'] ?? 'BOLIVIA' }}</span>
        <h1>{{ $destino['nombre'] }}</h1>
        @if(!empty($destino['descripcion']))
            <p class="detalle-resumen">{{ $destino['descripcion'] }}</p>
        @endif
    </div>
</section>
<section class="seccion lectura detalle-lugar">
    <span class="etiqueta">{{ $editorial ? 'LUGAR MÁS VISITADO' : 'DESCUBRE EL DESTINO' }}</span>
    <h2>El comienzo de <em>tu propia ruta.</em></h2>
    @unless($editorial)
        <p>{{ $destino['descripcion'] ?? $destino['descripcionCorta'] ?? '' }}</p>
    @endunless

    @if(!empty($destino['destacados']))
        <div class="detalle-destacados" aria-label="Qué no te puedes perder">
            @foreach($destino['destacados'] as $punto)
                <article><span aria-hidden="true">✦</span><p>{{ $punto }}</p></article>
            @endforeach
        </div>
    @endif

    @foreach(['historia' => 'El lugar', 'experiencia' => 'Cómo explorarlo', 'recomendaciones' => 'Antes de viajar', 'temporada' => 'Cuándo viajar'] as $campo => $titulo)
        @if(!empty($destino[$campo]))
            <h3>{{ $titulo }}</h3>
            <p class="texto-preservado">{{ $destino[$campo] }}</p>
        @endif
    @endforeach

    <p>Comparte esta idea con tu asesor. La ruta y los servicios se definen en tu propuesta personalizada.</p>
    <div class="detalle-acciones">
        <a class="boton" href="{{ route(session('usuario.rol') === 'CLIENTE' ? 'portal.solicitud' : 'acceso.registro') }}">Añadir a mi idea de viaje ↗</a>
        <a class="enlace" href="{{ route('publico.inicio') }}#inspiraciones">Ver más lugares</a>
    </div>
</section>
@endsection
