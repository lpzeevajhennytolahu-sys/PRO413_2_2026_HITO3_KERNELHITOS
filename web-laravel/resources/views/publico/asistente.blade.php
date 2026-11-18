@extends('layouts.publico')
@section('titulo', 'Ruta personalizada')
@section('clase', 'pagina-publica publico-con-cabecera')
@section('publico-clase', 'pagina-asistente')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('css/asistente.css') }}?v={{ filemtime(public_path('css/asistente.css')) }}" media="screen">
@endpush
@section('contenido')
<section class="ruta-app" data-asistente data-logueado="{{ session('usuario') ? '1' : '0' }}">
    <div class="ruta-mapa-wrap">
        <div id="asistente-mapa" class="ruta-mapa" role="application" aria-label="Mapa para crear tu ruta personalizada"></div>

        <form class="ruta-mapa-buscar" data-ruta-buscar-form>
            <span aria-hidden="true">⌕</span>
            <input type="search" placeholder="Buscar en el mapa" list="ruta-lugares-datalist" maxlength="200" data-ruta-buscar autocomplete="off">
            <datalist id="ruta-lugares-datalist">@foreach($lugares as $lugar)<option value="{{ $lugar['nombre'] }}">{{ $lugar['region'] }}</option>@endforeach</datalist>
        </form>

        <div class="ruta-mapa-hint" data-ruta-hint>Haz clic en un lugar o en el mapa para ir armando tu ruta</div>

        <div class="ruta-mapa-tools">
            <button type="button" class="ruta-tool" data-ruta-deshacer title="Deshacer" aria-label="Deshacer">↶</button>
            <button type="button" class="ruta-tool" data-ruta-limpiar title="Limpiar ruta" aria-label="Limpiar ruta">⌫</button>
            <button type="button" class="ruta-tool" data-ruta-centrar title="Centrar lugares" aria-label="Centrar">◎</button>
        </div>

        <div class="ruta-mapa-barra">
            <div class="ruta-stats">
                <div><span>Paradas</span><strong data-ruta-paradas>0</strong></div>
                <div><span>Distancia</span><strong data-ruta-distancia-mapa>0,00 km</strong></div>
                <div><span>Desnivel</span><strong data-ruta-desnivel-mapa>0 m</strong></div>
            </div>
        </div>
    </div>

    <aside class="ruta-panel" data-ruta-panel>
        <div class="ruta-panel-top">
            <a class="ruta-icono" href="{{ route('publico.inicio') }}" aria-label="Volver">←</a>
            <div class="ruta-modo" role="group" aria-label="Modo de edición">
                <button type="button" class="activo" data-ruta-modo="basico">Básico</button>
                <button type="button" data-ruta-modo="avanzado">Avanzado</button>
            </div>
            <button type="button" class="ruta-icono" data-ruta-plegar aria-label="Plegar panel">⌃</button>
        </div>

        <div class="ruta-panel-scroll">
            <div class="ruta-titulo-row">
                <h1 contenteditable="true" spellcheck="false" data-ruta-nombre>Ruta personalizada</h1>
                <span aria-hidden="true">✎</span>
            </div>

            <div class="ruta-presupuesto">
                <label for="ruta-presupuesto">Presupuesto total (opcional)
                    <input id="ruta-presupuesto" form="ruta-guardar-form" type="number" name="presupuesto" min="0" max="9999999999.99" step="0.01" value="{{ $formulario['presupuesto'] }}" placeholder="Por definir" data-ruta-presupuesto>
                </label>
                <label for="ruta-moneda">Moneda
                    <select id="ruta-moneda" form="ruta-guardar-form" name="moneda" data-ruta-moneda>
                        @foreach(['USD', 'BOB', 'EUR'] as $moneda)
                            <option value="{{ $moneda }}" @selected(($formulario['moneda'] ?? 'USD') === $moneda)>{{ $moneda }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="ruta-viaje-datos">
                <div class="ruta-viaje-fechas">
                    <label for="ruta-inicio">
                        <span><x-icon name="calendar"/> Inicio del viaje</span>
                        <input id="ruta-inicio" form="ruta-guardar-form" type="date" name="fechaInicio" min="{{ now()->format('Y-m-d') }}" value="{{ $formulario['fechaInicio'] }}" data-ruta-inicio>
                    </label>
                    <span class="ruta-viaje-sep" aria-hidden="true">→</span>
                    <label for="ruta-fin">
                        <span>Regreso</span>
                        <input id="ruta-fin" form="ruta-guardar-form" type="date" name="fechaFin" min="{{ now()->format('Y-m-d') }}" value="{{ $formulario['fechaFin'] }}" data-ruta-fin>
                    </label>
                </div>
                <label for="ruta-personas" class="ruta-viaje-viajeros">
                    <span><x-icon name="users"/> Viajeros</span>
                    <input id="ruta-personas" form="ruta-guardar-form" type="number" name="personas" min="1" max="1000" value="{{ $formulario['personas'] ?: 2 }}" required data-ruta-personas>
                </label>
            </div>

            <div class="ruta-metricas" data-ruta-metricas>
                <div>
                    <span>Distancia</span>
                    <strong data-ruta-distancia>0,00 km</strong>
                </div>
                <div>
                    <span>Desnivel</span>
                    <strong data-ruta-desnivel>0 m</strong>
                </div>
                <div>
                    <span>Tiempo est.</span>
                    <strong data-ruta-tiempo>0 h</strong>
                </div>
                @php
                    $iconosActividad = [
                        'Senderismo' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="4.5" r="2"/><path d="M10.5 8.5 8 21M13.5 8.5 16 21M9 12h6M10 8.5h4"/></svg>',
                        'MTB' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="6.5" cy="16.5" r="3"/><circle cx="17.5" cy="16.5" r="3"/><path d="M6.5 16.5h4.2L13 10h3.5M13 10l-2.2 4.2M12.2 7.2h3.3"/></svg>',
                        'Trail running' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="4.5" r="2"/><path d="m8 21 3-6 3 2 2 4M11 15l2.5-4.5L17 12M9.5 11.5 12 9l3-.5"/></svg>',
                        'Correr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="13" cy="4.5" r="2"/><path d="m7.5 21 3.2-6.2 3 2.2 2.3 4M10.7 14.8l2.8-4.6L17 11.5M10 11.2 12.5 8.8l3.2-.3"/></svg>',
                        'Ciclismo de carretera' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="17" r="2.8"/><circle cx="18" cy="17" r="2.8"/><path d="M6 17h4.5L13.2 9.5h3.8M13.2 9.5 11 14.5M12.5 7h3"/></svg>',
                        'Mochilear' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 8.5V7a4 4 0 0 1 8 0v1.5"/><rect x="6.5" y="8.5" width="11" height="12" rx="2.5"/><path d="M10 13h4M9 8.5h6"/></svg>',
                        'Trekking' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 18.5 5.5-8 3.2 4.2 2.3-3.2L20.5 18.5"/><path d="M14.5 7.2 16 5.5l1.6 1.7"/></svg>',
                        'Viaje a medida' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.2"/><path d="m12 7.2 1.4 3.2 3.4.3-2.6 2.3.8 3.3L12 14.6l-3 1.7.8-3.3-2.6-2.3 3.4-.3z"/></svg>',
                    ];
                @endphp
                <div class="ruta-actividad-wrap" data-ruta-actividad-wrap>
                    <span>Actividad</span>
                    <button type="button" class="ruta-actividad-btn" data-ruta-actividad-btn aria-expanded="false" aria-haspopup="listbox">
                        <span class="ruta-actividad-ico" data-ruta-actividad-ico aria-hidden="true">{!! $iconosActividad['Senderismo'] !!}</span>
                        <span data-ruta-actividad-label>Senderismo</span>
                        <span class="ruta-actividad-flecha" aria-hidden="true">
                            <svg viewBox="0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="m1.2 6.2 4.8-4.4 4.8 4.4"/></svg>
                        </span>
                    </button>
                    <ul class="ruta-actividad-menu" data-ruta-actividad-menu hidden role="listbox" aria-label="Tipo de actividad">
                        @foreach($iconosActividad as $tipo => $svg)
                            <li role="option">
                                <button type="button" data-ruta-actividad-opcion data-valor="{{ $tipo }}" @class(['activo' => $tipo === 'Senderismo'])>
                                    <span class="ruta-actividad-ico" aria-hidden="true">{!! $svg !!}</span>
                                    <em>{{ $tipo }}</em>
                                    <b aria-hidden="true">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3.2 8.2 3.2 3.2 6.4-6.8"/></svg>
                                    </b>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                    <input type="hidden" id="ruta-actividad" value="Senderismo" data-ruta-actividad>
                </div>
            </div>

            <div class="ruta-perfil" aria-hidden="true">
                <svg viewBox="0 0 320 72" preserveAspectRatio="none" data-ruta-perfil>
                    <path data-ruta-perfil-linea d="M0 60 L320 60" fill="none" stroke="#152033" stroke-width="2.5"></path>
                </svg>
                <div class="ruta-perfil-escala">
                    <span data-ruta-perfil-inicio>0,0 km</span>
                    <span data-ruta-perfil-fin>0,0 km</span>
                </div>
            </div>

            <div class="ruta-tabs" role="tablist">
                <button type="button" class="activo" role="tab" aria-selected="true" data-ruta-tab="puntos">Puntos de ruta</button>
                <button type="button" role="tab" aria-selected="false" data-ruta-tab="lugares">Lugares Thaki</button>
            </div>

            <div class="ruta-tab-panel" data-ruta-panel-puntos>
                <ol class="ruta-puntos" data-ruta-puntos>
                    <li class="ruta-puntos-vacio" data-ruta-vacio>Todavía no hay paradas. Elige un lugar registrado o haz clic en el mapa.</li>
                </ol>
            </div>

            <div class="ruta-tab-panel" data-ruta-panel-lugares hidden>
                <div class="ruta-lugares-grid" data-ruta-catalogo>
                    @foreach($lugares as $lugar)
                        <button type="button" class="ruta-lugar-card" data-asistente-chip data-nombre="{{ $lugar['nombre'] }}" data-slug="{{ $lugar['slug'] }}">
                            <img src="{{ $lugar['imagen'] }}" alt="" loading="lazy">
                            <span>
                                <strong>{{ $lugar['nombre'] }}</strong>
                                <em>{{ $lugar['region'] }}</em>
                            </span>
                            <b aria-hidden="true">+</b>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="ruta-ficha" data-asistente-ficha hidden>
                <img alt="" data-asistente-foto>
                <div>
                    <span data-asistente-region></span>
                    <strong data-asistente-nombre></strong>
                    <p data-asistente-texto></p>
                    <div class="ruta-ficha-acciones">
                        <a data-asistente-enlace href="#">Ver detalle</a>
                        <button type="button" data-asistente-elegir>Añadir a la ruta</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="ruta-panel-acciones">
            <label class="ruta-add-select">
                <span class="ruta-icono-add" aria-hidden="true">+</span>
                <select data-ruta-agregar>
                    <option value="">Añadir lugar registrado</option>
                    @foreach($lugares as $lugar)
                        <option value="{{ $lugar['slug'] }}">{{ $lugar['nombre'] }}</option>
                    @endforeach
                </select>
            </label>
            <form id="ruta-guardar-form" method="POST" action="{{ route('publico.guardarRuta') }}" data-asistente-form>
                @csrf
                <input type="hidden" name="nombreRuta" value="Ruta personalizada" data-ruta-nombre-hidden>
                <input type="hidden" name="destinos" value="{{ $formulario['destinos'] }}" data-ruta-destinos-hidden>
                <input type="hidden" name="tipoRuta" value="{{ $formulario['tipoRuta'] ?: 'Senderismo' }}" data-ruta-tipo-hidden>
                <input type="hidden" name="idea" value="" data-ruta-idea-hidden>
                <input type="hidden" name="distanciaKm" value="0" data-ruta-distancia-hidden>
                <input type="hidden" name="desnivelM" value="0" data-ruta-desnivel-hidden>
                <input type="hidden" name="paradas" value="0" data-ruta-paradas-hidden>
                <input type="hidden" name="hotel" value="{{ $formulario['hotel'] }}">
                <input type="hidden" name="alimentacion" value="{{ $formulario['alimentacion'] }}">
                <input type="hidden" name="transporte" value="{{ $formulario['transporte'] }}">
                <button class="ruta-guardar" type="submit" data-ruta-guardar disabled>Guardar</button>
            </form>
        </div>
    </aside>

    @unless(session('usuario'))
    <div class="ruta-modal" data-ruta-modal data-ruta-modal-auto>
        <div class="ruta-modal-panel" role="dialog" aria-modal="true" aria-labelledby="ruta-modal-titulo">
            <a class="ruta-modal-cerrar" href="{{ route('publico.inicio') }}" aria-label="Cerrar y volver al inicio">×</a>
            <div class="ruta-modal-logo" aria-label="Thaki Voyage">
                <span class="ruta-modal-marca">THAKI</span>
                <span class="ruta-modal-sub">VOYAGE</span>
            </div>
            <h2 id="ruta-modal-titulo">Para personalizar tu ruta, inicia sesión o regístrate</h2>
            <div class="ruta-auth-stack">
                <a class="ruta-auth-btn" href="{{ route('acceso.ingresar') }}">Iniciar sesión</a>
                <a class="ruta-auth-btn" href="{{ route('acceso.registro') }}">Crear cuenta</a>
            </div>
        </div>
    </div>
    @endunless
</section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
window.THAKI_ASISTENTE = {
    lugares: @json($lugares),
    activo: @json($lugarActivo)
};
</script>
<script src="{{ asset('js/asistente.js') }}?v={{ filemtime(public_path('js/asistente.js')) }}"></script>
@endsection
