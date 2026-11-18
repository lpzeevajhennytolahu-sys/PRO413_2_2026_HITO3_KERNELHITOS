@props(['name' => 'password', 'label' => 'Contraseña', 'autocomplete' => 'new-password', 'minimum' => 8])
@php($inputId = 'acceso-'.$name)
<div class="campo-contrasena">
    <label for="{{ $inputId }}">{{ $label }}</label>
    <div class="control-contrasena">
        <input id="{{ $inputId }}" name="{{ $name }}" type="password" @if($minimum) minlength="{{ $minimum }}" @endif maxlength="255" autocomplete="{{ $autocomplete }}" required>
        <button type="button" class="ojo-contrasena" data-password-toggle aria-controls="{{ $inputId }}" aria-pressed="false" aria-label="Mostrar {{ mb_strtolower($label) }}" hidden>
            <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">
                <ellipse class="ojo-globo" cx="12" cy="12" rx="9" ry="6" fill="none" stroke="currentColor" stroke-width="1.8"/>
                <circle class="ojo-pupila" cx="12" cy="12" r="2.6" fill="currentColor"/>
                <path class="ojo-parpado" d="M3 12 Q12 4 21 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
</div>
@once
    @push('styles')<link rel="stylesheet" href="{{ asset('css/password.css') }}?v={{ filemtime(public_path('css/password.css')) }}">@endpush
    @push('scripts')<script src="{{ asset('js/password.js') }}?v={{ filemtime(public_path('js/password.js')) }}" defer></script>@endpush
@endonce
