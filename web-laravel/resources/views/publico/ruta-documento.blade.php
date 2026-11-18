@extends('layouts.publico')
@section('titulo', 'Documento de ruta')
@section('clase', 'pagina-publica publico-con-cabecera')
@section('contenido')
<section class="seccion" style="max-width:760px;margin:32px auto;padding:0 18px 48px">
    <div class="encabezado-seccion" style="margin-bottom:18px">
        <div>
            <span class="etiqueta">DOCUMENTO DE RUTA</span>
            <h1>{{ $ruta['nombreRuta'] ?? 'Ruta personalizada' }}</h1>
            <p>Resumen para tu asesor Thaki · guardado el {{ $ruta['guardadaEn'] ?? now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="acciones" style="display:flex;gap:10px;flex-wrap:wrap">
            <button type="button" class="boton secundario" onclick="window.print()">Imprimir / PDF</button>
            @if(session('usuario.rol') === 'CLIENTE')
                <a class="boton" href="{{ route('portal.mensajes') }}">Ir al chat con el asesor ↗</a>
            @else
                <a class="boton" href="{{ route('acceso.ingresar') }}">Ingresar para chatear ↗</a>
            @endif
        </div>
    </div>

    <article class="tarjeta" style="padding:22px;display:grid;gap:16px">
        <div>
            <span class="etiqueta">ACTIVIDAD</span>
            <p style="margin:6px 0 0;font-size:18px">{{ $ruta['tipoRuta'] ?? 'Viaje a medida' }}</p>
        </div>
        <div>
            <span class="etiqueta">PARADAS</span>
            <ol style="margin:10px 0 0;padding-left:18px;display:grid;gap:6px">
                @foreach(($ruta['paradasLista'] ?? []) as $parada)
                    <li>{{ $parada }}</li>
                @endforeach
            </ol>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
            <div><span class="etiqueta">PRESUPUESTO</span><p style="margin:6px 0 0">{{ !empty($ruta['presupuesto']) ? number_format((float) $ruta['presupuesto'], 2, ',', '.').' '.($ruta['moneda'] ?? 'USD') : 'Por definir' }}</p></div>
            <div><span class="etiqueta">MONEDA</span><p style="margin:6px 0 0">{{ $ruta['moneda'] ?? 'USD' }}</p></div>
            <div><span class="etiqueta">INICIO</span><p style="margin:6px 0 0">{{ $ruta['fechaInicio'] ?? '—' }}</p></div>
            <div><span class="etiqueta">REGRESO</span><p style="margin:6px 0 0">{{ $ruta['fechaFin'] ?? '—' }}</p></div>
            <div><span class="etiqueta">VIAJEROS</span><p style="margin:6px 0 0">{{ $ruta['personas'] ?? '—' }}</p></div>
            <div><span class="etiqueta">DISTANCIA</span><p style="margin:6px 0 0">{{ isset($ruta['distanciaKm']) ? number_format((float) $ruta['distanciaKm'], 2, ',', '.').' km' : '—' }}</p></div>
            <div><span class="etiqueta">DESNIVEL</span><p style="margin:6px 0 0">{{ isset($ruta['desnivelM']) ? number_format((float) $ruta['desnivelM'], 0, ',', '.').' m' : '—' }}</p></div>
            <div><span class="etiqueta">PARADAS</span><p style="margin:6px 0 0">{{ $ruta['paradas'] ?? count($ruta['paradasLista'] ?? []) }}</p></div>
        </div>
        @if(!empty($ruta['idea']))
            <div>
                <span class="etiqueta">COMENTARIO</span>
                <p style="margin:6px 0 0;white-space:pre-wrap">{{ $ruta['idea'] }}</p>
            </div>
        @endif
    </article>
</section>
@endsection
