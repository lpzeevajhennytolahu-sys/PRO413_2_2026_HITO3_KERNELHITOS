@extends('layouts.admin')
@section('titulo', 'Panel de operaciones')
@section('clase-pagina', 'inicio-operaciones')
@section('contenido')
@php
    $saludo = now('America/La_Paz')->hour < 12 ? 'Buenos días' : (now('America/La_Paz')->hour < 19 ? 'Buenas tardes' : 'Buenas noches');
    $cargo = $rol === 'ADMIN' ? 'Administración' : 'Asesoría de viajes';
    $maxBarra = max(1, collect($mensual)->max(fn ($m) => max($m['solicitudes'], $m['viajes'])));
    $pico = collect($mensual)->sortByDesc(fn ($m) => $m['viajes'])->first();
    $areaMax = max(1, collect($mensual)->max(fn ($m) => $m['solicitudes'] + $m['viajes']));
    $areaW = 520; $areaH = 160; $ptsSol = []; $ptsVia = []; $ptsBase = [];
    foreach ($mensual as $i => $m) {
        $x = count($mensual) === 1 ? $areaW / 2 : ($i / (count($mensual) - 1)) * $areaW;
        $ySol = $areaH - (($m['solicitudes'] / $areaMax) * ($areaH - 12));
        $yVia = $areaH - ((($m['solicitudes'] + $m['viajes']) / $areaMax) * ($areaH - 12));
        $ptsSol[] = round($x, 1).','.round($ySol, 1);
        $ptsVia[] = round($x, 1).','.round($yVia, 1);
        $ptsBase[] = round($x, 1).','.$areaH;
    }
    $polyVia = '0,'.$areaH.' '.implode(' ', $ptsVia).' '.$areaW.','.$areaH;
    $polySol = '0,'.$areaH.' '.implode(' ', $ptsSol).' '.$areaW.','.$areaH;
@endphp

<header class="analytics-cabecera">
    <div>
        <p class="sobre-titulo">PANEL · {{ mb_strtoupper($cargo) }}</p>
        <h1>{{ $saludo }}, {{ explode(' ', $nombre)[0] }}</h1>
    </div>
    <div class="analytics-cabecera-meta">
        <span>{{ now('America/La_Paz')->translatedFormat('d M Y') }}</span>
        <span>Bolivia · UTC−04:00</span>
    </div>
</header>

@if($error)
<div class="aviso error" role="alert"><strong>{{ $error }}</strong><a href="{{ route('admin.index') }}">Actualizar</a></div>
@endif

<section class="analytics-kpis" aria-label="Indicadores principales">
    <a class="analytics-kpi analytics-kpi-oscuro" href="{{ route('clientes.index') }}">
        <span class="analytics-kpi-icono"><x-icon name="users"/></span>
        <span class="analytics-kpi-etiqueta">Clientes</span>
        <strong>{{ number_format($atencion['clientes'], 0, ',', '.') }}</strong>
    </a>
    <a class="analytics-kpi" href="{{ route('atencion.index') }}">
        <span class="analytics-kpi-icono"><x-icon name="chat"/></span>
        <span class="analytics-kpi-etiqueta">Solicitudes</span>
        <strong>{{ number_format($atencion['solicitudes'], 0, ',', '.') }}</strong>
    </a>
    <a class="analytics-kpi" href="{{ route('atencion.index') }}">
        <span class="analytics-kpi-icono"><x-icon name="bell"/></span>
        <span class="analytics-kpi-etiqueta">Mensajes</span>
        <strong>{{ number_format($atencion['mensajesNuevos'], 0, ',', '.') }}</strong>
    </a>
    <a class="analytics-kpi" href="{{ route('inventario.operaciones') }}">
        <span class="analytics-kpi-icono"><x-icon name="box"/></span>
        <span class="analytics-kpi-etiqueta">Inventario</span>
        <strong>{{ number_format($inventario['productos'], 0, ',', '.') }}</strong>
    </a>
</section>

<section class="analytics-medio" aria-label="Resultados y salud operativa">
    <article class="analytics-card analytics-barras">
        <div class="analytics-card-top">
            <h2>Resultado</h2>
            <a class="analytics-cta" href="{{ route('atencion.index') }}">Revisar ahora</a>
        </div>
        <div class="analytics-leyenda">
            <span><i class="punto punto-naranja"></i> Solicitudes</span>
            <span><i class="punto punto-navy"></i> Viajes</span>
        </div>
        <div class="analytics-chart-barras" role="img" aria-label="Solicitudes y viajes por mes">
            @foreach($mensual as $mes)
                @php
                    $hSol = max(4, (int) round(($mes['solicitudes'] / $maxBarra) * 150));
                    $hVia = max(4, (int) round(($mes['viajes'] / $maxBarra) * 150));
                    $destacar = $pico && $pico['clave'] === $mes['clave'] && $mes['viajes'] > 0;
                @endphp
                <div class="barra-grupo {{ $destacar ? 'destacado' : '' }}">
                    @if($destacar)
                        <span class="barra-tooltip">{{ number_format($mes['viajes'], 0, ',', '.') }}</span>
                    @endif
                    <div class="barra-pares">
                        <span class="barra barra-naranja" style="height: {{ $hSol }}px" title="Solicitudes: {{ $mes['solicitudes'] }}"></span>
                        <span class="barra barra-navy" style="height: {{ $hVia }}px" title="Viajes: {{ $mes['viajes'] }}"></span>
                    </div>
                    <small>{{ $mes['etiqueta'] }}</small>
                </div>
            @endforeach
        </div>
    </article>

    <article class="analytics-card analytics-donut">
        <div class="donut-wrap" style="--donut: {{ max(0, min(100, $salud)) }}">
            <div class="donut-ring" aria-hidden="true"></div>
            <div class="donut-centro">
                <strong>{{ $salud }}%</strong>
                <span>almacén</span>
            </div>
        </div>
        <ul class="donut-lista">
            <li><i class="punto punto-navy"></i><span>Unidades operativas</span><strong>{{ number_format(max(0, $inventario['unidades'] - $inventario['arreglo']), 0, ',', '.') }}</strong></li>
            <li><i class="punto punto-naranja"></i><span>Requieren arreglo</span><strong>{{ number_format($inventario['arreglo'], 0, ',', '.') }}</strong></li>
            <li><i class="punto punto-navy"></i><span>Atención al día</span><strong>{{ $atencionPct }}%</strong></li>
            <li><i class="punto punto-naranja"></i><span>Clientes pendientes</span><strong>{{ number_format($atencion['clientesPendientes'], 0, ',', '.') }}</strong></li>
        </ul>
    </article>
</section>

<section class="analytics-bajo" aria-label="Tendencia y agenda">
    <article class="analytics-card analytics-area">
        <div class="analytics-card-top">
            <h2>Tendencia</h2>
            <div class="analytics-leyenda">
                <span><i class="punto punto-naranja"></i> Solicitudes</span>
                <span><i class="punto punto-navy"></i> Viajes</span>
            </div>
        </div>
        <svg class="area-svg" viewBox="0 0 {{ $areaW }} {{ $areaH }}" preserveAspectRatio="none" role="img" aria-label="Tendencia mensual">
            <defs>
                <linearGradient id="gradVia" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#1f3b63" stop-opacity=".55"/>
                    <stop offset="100%" stop-color="#1f3b63" stop-opacity=".05"/>
                </linearGradient>
                <linearGradient id="gradSol" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ff7a3d" stop-opacity=".75"/>
                    <stop offset="100%" stop-color="#ff7a3d" stop-opacity=".08"/>
                </linearGradient>
            </defs>
            <polygon points="{{ $polyVia }}" fill="url(#gradVia)"/>
            <polygon points="{{ $polySol }}" fill="url(#gradSol)"/>
            <polyline points="{{ implode(' ', $ptsVia) }}" fill="none" stroke="#1f3b63" stroke-width="2.5" stroke-linejoin="round"/>
            <polyline points="{{ implode(' ', $ptsSol) }}" fill="none" stroke="#ff7a3d" stroke-width="2.5" stroke-linejoin="round"/>
        </svg>
        <div class="area-meses">
            @foreach($mensual as $mes)<span>{{ $mes['etiqueta'] }}</span>@endforeach
        </div>
    </article>

    <article class="analytics-card analytics-calendario">
        <div class="analytics-card-top">
            <h2>{{ $calendario['titulo'] ?? now('America/La_Paz')->translatedFormat('F Y') }}</h2>
            <a class="analytics-link" href="{{ route('atencion.calendario') }}">Agenda ↗</a>
        </div>
        <div class="mini-cal">
            <div class="mini-cal-cab">@foreach(['D','L','M','M','J','V','S'] as $d)<span>{{ $d }}</span>@endforeach</div>
            <div class="mini-cal-grid">
                @foreach($calendario['dias'] as $dia)
                    <span @class([
                        'mini-dia',
                        'fuera' => ! $dia['actual'],
                        'hoy' => $dia['hoy'],
                        'activo' => $dia['activo'],
                    ])>{{ $dia['dia'] }}</span>
                @endforeach
            </div>
        </div>
    </article>
</section>

<nav class="analytics-accesos" aria-label="Accesos rápidos">
    <a href="{{ route('clientes.index') }}"><x-icon name="users"/><span>Clientes</span></a>
    <a href="{{ route('atencion.index') }}"><x-icon name="chat"/><span>Solicitudes</span></a>
    <a href="{{ route('inventario.operaciones') }}"><x-icon name="box"/><span>Inventario</span></a>
    <a href="{{ route('admin.gestion') }}"><x-icon name="grid"/><span>Organización</span></a>
</nav>
@endsection
