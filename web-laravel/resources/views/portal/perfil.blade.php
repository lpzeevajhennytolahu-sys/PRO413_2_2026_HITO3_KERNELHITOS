@extends('layouts.portal')
@section('titulo', 'Mi perfil')
@section('portal')
@php
    $nombrePerfil = trim(($perfil['nombres'] ?? '').' '.($perfil['apellidos'] ?? '')) ?: session('usuario.nombre', 'Viajero');
    $inicialesPerfil = collect(preg_split('/\s+/', $nombrePerfil))->filter()->take(2)->map(fn($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
@endphp
<header class="perfil-hero">
    <span class="perfil-avatar" aria-hidden="true">{{ $inicialesPerfil }}</span>
    <div><span class="etiqueta">TU ESPACIO PERSONAL</span><h1>Tu perfil de viajero.</h1><p>{{ $nombrePerfil }} · Actualiza tus datos para organizar tu próxima experiencia.</p></div>
</header>
<x-perfil-progreso :estado="$perfil['completitud'] ?? []" :en-perfil="true"/>
<form class="formulario tarjeta perfil-formulario" method="POST" action="{{ route('portal.perfil.guardar') }}">
    @csrf @method('PUT')
    <fieldset class="perfil-seccion"><legend><span>01</span>Datos personales</legend><div class="rejilla dos">
        @foreach(['nombres' => 'Nombres', 'apellidos' => 'Apellidos'] as $campo => $titulo)
        <label for="{{ $campo }}">{{ $titulo }} *<input id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $perfil[$campo] ?? '') }}" maxlength="100" autocomplete="{{ $campo === 'nombres' ? 'given-name' : 'family-name' }}" required @if($errors->has($campo)) aria-invalid="true" aria-describedby="error-{{ $campo }}" @endif><x-error-campo :name="$campo"/></label>
        @endforeach
        <label for="fechaNacimiento">Fecha de nacimiento *<input id="fechaNacimiento" type="date" name="fechaNacimiento" max="{{ now()->format('Y-m-d') }}" value="{{ old('fechaNacimiento', $perfil['fechaNacimiento'] ?? '') }}" autocomplete="bday"><x-error-campo name="fechaNacimiento"/></label>
        <label for="genero">Género (opcional)
            <select id="genero" name="genero">
                <option value="">Selecciona una opción</option>
                @foreach(['Femenino', 'Masculino'] as $opcion)
                    <option value="{{ $opcion }}" @selected(old('genero', $perfil['genero'] ?? '') === $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
            <x-error-campo name="genero"/>
        </label>
    </div></fieldset>
    @php
        $codigosPais = [
            '+33' => 'France (+33)',
            '+32' => 'Belgique (+32)',
            '+41' => 'Suisse (+41)',
            '+352' => 'Luxembourg (+352)',
            '+377' => 'Monaco (+377)',
            '+34' => 'España (+34)',
            '+39' => 'Italia (+39)',
            '+49' => 'Allemagne (+49)',
            '+31' => 'Pays-Bas (+31)',
            '+351' => 'Portugal (+351)',
            '+44' => 'Royaume-Uni (+44)',
            '+591' => 'Bolivia (+591)',
            '+51' => 'Perú (+51)',
            '+56' => 'Chile (+56)',
            '+54' => 'Argentina (+54)',
            '+57' => 'Colombia (+57)',
            '+593' => 'Ecuador (+593)',
            '+595' => 'Paraguay (+595)',
            '+598' => 'Uruguay (+598)',
            '+55' => 'Brasil (+55)',
            '+52' => 'México (+52)',
            '+1' => 'EE.UU. / Canadá (+1)',
        ];
        $telefonoGuardado = trim((string) old('telefono', $perfil['telefono'] ?? ''));
        $codigoActual = old('codigoPais');
        $numeroActual = old('telefonoNumero');
        if ($codigoActual === null && preg_match('/^(\+\d{1,3})\s*(.*)$/', $telefonoGuardado, $partes)) {
            $codigoActual = $partes[1];
            $numeroActual = preg_replace('/\D+/', '', $partes[2]);
        } elseif ($codigoActual === null) {
            $codigoActual = '+33';
            $numeroActual = preg_replace('/\D+/', '', $telefonoGuardado);
        }
        if (! array_key_exists((string) $codigoActual, $codigosPais)) {
            $codigoActual = '+33';
        }
    @endphp
    <fieldset class="perfil-seccion"><legend><span>02</span>Datos de contacto</legend><div class="rejilla dos">
        <div class="perfil-telefono">
            <span class="perfil-telefono-label">Teléfono *</span>
            <div class="perfil-telefono-campos">
                <label class="perfil-codigo-pais" for="codigoPais">
                    <span class="visually-hidden">Código de país</span>
                    <select id="codigoPais" name="codigoPais" required>
                        @foreach($codigosPais as $codigo => $etiqueta)
                            <option value="{{ $codigo }}" @selected((string) $codigoActual === (string) $codigo)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="perfil-telefono-numero" for="telefonoNumero">
                    <span class="visually-hidden">Número de teléfono</span>
                    <input id="telefonoNumero" type="tel" name="telefonoNumero" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15" placeholder="70123456" value="{{ $numeroActual }}" autocomplete="tel-national" required>
                </label>
            </div>
            <x-error-campo name="codigoPais"/><x-error-campo name="telefonoNumero"/><x-error-campo name="telefono"/>
            <small>Elige el código de país y escribe solo el número, sin espacios ni signos.</small>
        </div>
        <label for="direccion">Dirección *
            <input id="direccion" name="direccion" maxlength="200" minlength="5" value="{{ old('direccion', $perfil['direccion'] ?? '') }}" autocomplete="street-address" title="Solo letras, números, espacios y signos . , # - / °" required>
            <x-error-campo name="direccion"/>
            <small>Solo letras, números, espacios y signos . , # - /</small>
        </label>
        <label for="emailPerfil">Correo de tu cuenta<input id="emailPerfil" type="email" value="{{ $perfil['email'] ?? session('usuario.email') }}" readonly><small>Para modificar el correo, contacta al personal.</small></label>
    </div></fieldset>
    <fieldset class="perfil-seccion"><legend><span>03</span>Documentación</legend><div class="rejilla dos">
        <label for="tipoDocumento">Tipo de documento *
            <select id="tipoDocumento" name="tipoDocumento" required>
                <option value="">Selecciona una opción</option>
                @foreach([
                    'Pasaporte' => 'Pasaporte',
                    'Carte d\'identité' => 'Carte d\'identité',
                    'Titre de séjour' => 'Titre de séjour',
                    'DNI' => 'DNI',
                    'NIE' => 'NIE',
                    'CI' => 'CI (cédula de identidad)',
                ] as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(old('tipoDocumento', $perfil['tipoDocumento'] ?? '') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
            <x-error-campo name="tipoDocumento"/>
        </label>
        <label for="numeroDocumento">Número de documento *<input id="numeroDocumento" name="numeroDocumento" maxlength="30" value="{{ old('numeroDocumento', $perfil['numeroDocumento'] ?? '') }}"><x-error-campo name="numeroDocumento"/></label>
    </div></fieldset>
    <div class="perfil-acciones"><p>Puedes guardar tus avances.<br>Completa los campos con * para enviar tu pedido de viaje.</p><button type="submit" class="boton" data-submitting-label="Guardando cambios…">Guardar mi perfil ↗</button></div>
</form>
@endsection
