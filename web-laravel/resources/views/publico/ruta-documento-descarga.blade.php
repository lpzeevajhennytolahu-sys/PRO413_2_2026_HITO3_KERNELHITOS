@php
    $titulo = $ruta['nombreRuta'] ?? 'Ruta personalizada';
    $paradas = $ruta['paradasLista'] ?? [];
    $presupuesto = ! empty($ruta['presupuesto'])
        ? number_format((float) $ruta['presupuesto'], 2, ',', '.').' '.($ruta['moneda'] ?? 'USD')
        : 'Por definir';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }} · Thaki Voyage</title>
    <style>
        body { font: 14px/1.5 Arial, sans-serif; color: #182f3b; max-width: 760px; margin: 28px auto; padding: 0 18px 40px; }
        h1 { font: 28px/1.2 Georgia, serif; color: #173c4f; margin: 8px 0 6px; }
        h2 { font: bold 15px/1.3 Arial, sans-serif; color: #173c4f; margin: 22px 0 8px; }
        .marca { letter-spacing: 1.5px; font-size: 12px; color: #896a50; }
        .meta { color: #53636c; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 16px; }
        th, td { border: 1px solid #d3dadb; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { width: 34%; background: #edf0ed; }
        ol { padding-left: 22px; }
        li { margin: 4px 0; }
        .nota { margin-top: 24px; font-size: 12px; color: #53636c; border-top: 1px solid #b5c1c5; padding-top: 10px; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <p class="marca">THAKI VOYAGE · DOCUMENTO DE RUTA</p>
    <h1>{{ $titulo }}</h1>
    <p class="meta">Guardado el {{ $ruta['guardadaEn'] ?? now()->timezone('America/La_Paz')->format('d/m/Y H:i') }} · Bolivia</p>

    <h2>Actividad</h2>
    <p>{{ $ruta['tipoRuta'] ?? 'Viaje a medida' }}</p>

    <h2>Paradas</h2>
    <ol>
        @forelse($paradas as $parada)
            <li>{{ $parada }}</li>
        @empty
            <li>Sin paradas registradas</li>
        @endforelse
    </ol>

    <h2>Resumen</h2>
    <table>
        <tbody>
            <tr><th>Presupuesto</th><td>{{ $presupuesto }}</td></tr>
            <tr><th>Moneda</th><td>{{ $ruta['moneda'] ?? 'USD' }}</td></tr>
            <tr><th>Inicio</th><td>{{ $ruta['fechaInicio'] ?? '—' }}</td></tr>
            <tr><th>Regreso</th><td>{{ $ruta['fechaFin'] ?? '—' }}</td></tr>
            <tr><th>Viajeros</th><td>{{ $ruta['personas'] ?? '—' }}</td></tr>
            <tr><th>Distancia</th><td>{{ isset($ruta['distanciaKm']) ? number_format((float) $ruta['distanciaKm'], 2, ',', '.').' km' : '—' }}</td></tr>
            <tr><th>Desnivel</th><td>{{ isset($ruta['desnivelM']) ? number_format((float) $ruta['desnivelM'], 0, ',', '.').' m' : '—' }}</td></tr>
            <tr><th>Paradas</th><td>{{ $ruta['paradas'] ?? count($paradas) }}</td></tr>
        </tbody>
    </table>

    @if(!empty($ruta['idea']))
        <h2>Comentario</h2>
        <p style="white-space:pre-wrap">{{ $ruta['idea'] }}</p>
    @endif

    <p class="nota">Documento generado para coordinar la ruta con el asesor Thaki Voyage. Puedes abrirlo e imprimirlo o guardarlo como PDF desde el navegador.</p>
</body>
</html>
