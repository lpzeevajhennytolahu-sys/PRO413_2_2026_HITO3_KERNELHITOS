<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Escolar - PRO-413 Hito 3 (KERNELHITOS)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="{{ route('escolar.index') }}">
            🎓 Sistema de Gestión Escolar — Grupo KERNELHITOS
        </a>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge {{ $estadoDocker === 'Conectado' ? 'bg-success' : 'bg-danger' }}">
                BD Docker: {{ $estadoDocker }}
            </span>
            <span class="badge {{ $estadoDocente === 'Conectado' ? 'bg-primary' : 'bg-warning text-dark' }}">
                BD Docente (10.0.47.204): {{ $estadoDocente }}
            </span>
            <form action="{{ route('escolar.init') }}" method="POST" class="ms-2">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm fw-bold">
                    ⚡ Inicializar Tablas SQL en Ambos Servidores
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <strong>¡Operación exitosa!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error') || $errorMensaje)
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <strong>Aviso del sistema:</strong> {{ session('error') ?? $errorMensaje }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-warning alert-dismissible fade show shadow-sm">
            <strong>Verifique los datos ingresados:</strong>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- TABLA CONSOLIDADA: ESTRUCTURA DE DATOS (HASH MAP) + POO -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
            <span>📊 Seguimiento Académico Integrado (Cruce simultáneo BD Docker + BD Docente mediante Tabla Hash Asociativa y POO)</span>
            <span class="badge bg-info text-dark">Total registros cruzados: {{ count($fichaIntegrada) }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Código (BD Docker)</th>
                            <th>Estudiante (BD Docker)</th>
                            <th>Asignatura (BD Docker)</th>
                            <th>Promedio (POO)</th>
                            <th>Resultado (Polimorfismo)</th>
                            <th>Docente Asignado (BD Docente)</th>
                            <th>Aula (BD Docente)</th>
                            <th>Horario (BD Docente)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fichaIntegrada as $item)
                            <tr>
                                <td class="fw-bold">{{ $item['codigo_estudiante'] }}</td>
                                <td>{{ $item['estudiante'] }}</td>
                                <td><span class="badge bg-secondary">{{ $item['sigla'] }}</span> {{ $item['asignatura'] }}</td>
                                <td class="fw-bold">{{ $item['promedio'] }}</td>
                                <td>
                                    <span class="badge {{ $item['estado'] === 'APROBADO' ? 'bg-success' : 'bg-danger' }}">
                                        {{ $item['estado'] }}
                                    </span>
                                </td>
                                <td class="text-primary fw-semibold">{{ $item['docente'] }}</td>
                                <td>{{ $item['aula'] }}</td>
                                <td class="small">{{ $item['horario'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No hay registros cargados. Presione el botón amarillo <strong>"⚡ Inicializar Tablas SQL en Ambos Servidores"</strong> arriba a la derecha.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- PANEL IZQUIERDO: SERVIDOR 1 (MYSQL DOCKER) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-success border-top border-4">
                <div class="card-header bg-white fw-bold text-success">
                    🐳 SERVIDOR 1: Base de Datos Docker (Estudiantes, Asignaturas, Inscripciones, Calificaciones)
                </div>
                <div class="card-body">
                    <form action="{{ route('docker.estudiante.store') }}" method="POST" class="row g-2 mb-4 p-3 bg-light rounded border">
                        @csrf
                        <h6 class="fw-bold mb-1">Registrar Estudiante, Inscripción y Calificación</h6>
                        <div class="col-md-4">
                            <input type="text" name="codigo_estudiante" class="form-control form-control-sm" placeholder="Código (EST-2026-03)" value="{{ old('codigo_estudiante') }}" required>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="nombre_completo" class="form-control form-control-sm" placeholder="Nombre completo del estudiante" value="{{ old('nombre_completo') }}" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="correo" class="form-control form-control-sm" placeholder="correo@estudiante.edu.bo" value="{{ old('correo') }}" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="semestre" class="form-control form-control-sm" placeholder="Sem." value="4" min="1" max="10" required>
                        </div>
                        <div class="col-md-4">
                            <select name="asignatura_id" class="form-select form-select-sm" required>
                                @foreach($asignaturas as $asig)
                                    <option value="{{ $asig->id }}">{{ $asig->sigla }} - {{ $asig->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" name="nota_parcial" class="form-control form-control-sm" placeholder="Nota Parcial (0-100)" required>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" name="nota_final" class="form-control form-control-sm" placeholder="Nota Final (0-100)" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">+ Guardar en BD Docker</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Asignatura</th>
                                    <th>Actualizar Notas (Parcial / Final)</th>
                                    <th>Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscripciones as $ins)
                                    <tr>
                                        <td class="small">
                                            <strong>{{ $ins->codigo_estudiante }}</strong><br>{{ $ins->nombre_completo }}
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $ins->sigla }}</span></td>
                                        <td>
                                            <form action="{{ route('docker.calificacion.update', $ins->id) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" step="0.01" name="nota_parcial" value="{{ $ins->nota_parcial }}" class="form-control form-control-sm" style="width:75px" required>
                                                <input type="number" step="0.01" name="nota_final" value="{{ $ins->nota_final }}" class="form-control form-control-sm" style="width:75px" required>
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Actualizar notas">💾</button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('docker.inscripcion.delete', $ins->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar inscripción">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL DERECHO: SERVIDOR 2 (MYSQL RED LOCAL DOCENTE 10.0.47.204) -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-primary border-top border-4">
                <div class="card-header bg-white fw-bold text-primary">
                    🏫 SERVIDOR 2: Base de Datos Docente 10.0.47.204 (Docentes, Gestión, Aulas, Asignaciones)
                </div>
                <div class="card-body">
                    <form action="{{ route('docente.asignacion.store') }}" method="POST" class="row g-2 mb-4 p-3 bg-light rounded border">
                        @csrf
                        <h6 class="fw-bold mb-1">Registrar Docente y Asignar a Curso / Aula</h6>
                        <div class="col-md-4">
                            <input type="text" name="codigo_docente" class="form-control form-control-sm" placeholder="Código (DOC-103)" required>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="nombre_completo" class="form-control form-control-sm" placeholder="Nombre completo del docente" required>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="especialidad" class="form-control form-control-sm" placeholder="Especialidad académica" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="correo" class="form-control form-control-sm" placeholder="docente@institucion.edu.bo" required>
                        </div>
                        <div class="col-md-4">
                            <select name="asignatura_sigla" class="form-select form-select-sm" required>
                                <option value="PRO-413">PRO-413 (Programación III)</option>
                                <option value="BDD-412">BDD-412 (Base de Datos II)</option>
                                <option value="RED-415">RED-415 (Redes I)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="aula_id" class="form-select form-select-sm" required>
                                @foreach($aulas as $au)
                                    <option value="{{ $au->id }}">{{ $au->codigo_aula }} ({{ $au->bloque }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="horario" class="form-control form-control-sm" placeholder="Sab 07:30 - 09:30" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">+ Guardar en Servidor del Docente (10.0.47.204)</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Docente</th>
                                    <th>Curso / Aula</th>
                                    <th>Modificar Horario</th>
                                    <th>Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asignaciones as $asig)
                                    <tr>
                                        <td class="small">
                                            <strong>{{ $asig->codigo_docente }}</strong><br>{{ $asig->docente_nombre }}
                                        </td>
                                        <td class="small">
                                            <span class="badge bg-primary">{{ $asig->asignatura_sigla }}</span><br>
                                            {{ $asig->codigo_aula }} ({{ $asig->codigo_gestion }})
                                        </td>
                                        <td>
                                            <form action="{{ route('docente.asignacion.update', $asig->id) }}" method="POST" class="d-flex gap-1">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="horario" value="{{ $asig->horario }}" class="form-control form-control-sm" required>
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Actualizar horario">💾</button>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('docente.asignacion.delete', $asig->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar asignación">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>