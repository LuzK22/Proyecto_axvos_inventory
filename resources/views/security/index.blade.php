@extends('adminlte::page')

@section('title', 'Centro de Seguridad')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
            <i class="fas fa-shield-alt mr-2" style="color:#dc3545;"></i> Centro de Seguridad
        </h1>
        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

{{-- ── Códigos de recuperación (solo se muestran una vez al activar o regenerar) ── --}}
@if(session('recovery_codes'))
    <div class="alert alert-warning shadow-sm border border-warning mb-4" role="alert">
        <h5 class="font-weight-bold mb-2">
            <i class="fas fa-key mr-2"></i> Guarda estos códigos de recuperación ahora
        </h5>
        <p class="mb-2 small">
            Úsalos si pierdes acceso a tu app autenticadora. <strong>Cada código solo funciona una vez.</strong>
            Esta es la única vez que se muestran.
        </p>
        <div class="row">
            @foreach(session('recovery_codes') as $code)
                <div class="col-6 col-md-3 mb-1">
                    <code class="d-block text-center bg-white border rounded px-2 py-1 font-weight-bold">{{ $code }}</code>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ── PUNTUACIÓN DE SEGURIDAD ────────────────────────────────────────────── --}}
@php
    $scoreColor = $score >= 80 ? '#28a745' : ($score >= 50 ? '#fd7e14' : '#dc3545');
    $scoreLabel = $score >= 80 ? 'Seguridad alta' : ($score >= 50 ? 'Seguridad media' : 'Seguridad baja');
    $scoreIcon  = $score >= 80 ? 'fa-shield-alt' : ($score >= 50 ? 'fa-exclamation-triangle' : 'fa-times-circle');
@endphp
<div class="card shadow-sm mb-4" style="border-left: 5px solid {{ $scoreColor }};">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="fa-stack fa-lg">
                    <i class="fas fa-circle fa-stack-2x" style="color:{{ $scoreColor }};opacity:.15;"></i>
                    <i class="fas {{ $scoreIcon }} fa-stack-1x" style="color:{{ $scoreColor }};"></i>
                </span>
            </div>
            <div class="col">
                <div class="font-weight-bold" style="color:{{ $scoreColor }};">{{ $scoreLabel }}</div>
                <div class="progress mt-1" style="height:8px;width:220px;">
                    <div class="progress-bar" role="progressbar"
                         style="width:{{ $score }}%;background:{{ $scoreColor }};"
                         aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <span class="h4 font-weight-bold mb-0" style="color:{{ $scoreColor }};">{{ $score }}<small class="text-muted font-weight-normal">/100</small></span>
            </div>
            <div class="col-md-5 text-muted small mt-2 mt-md-0">
                @if($score < 100)
                    <strong>Mejora tu puntuación:</strong>
                    @if(!$user->hasTwoFactorEnabled()) <span class="d-block"><i class="fas fa-plus-circle text-success mr-1"></i> Activa 2FA (+50 pts)</span> @endif
                    @if($user->passwordExpired(90)) <span class="d-block"><i class="fas fa-plus-circle text-success mr-1"></i> Actualiza tu contraseña (+30 pts)</span> @endif
                    @if(!$user->hasTwoFactorRecoveryCodes()) <span class="d-block"><i class="fas fa-plus-circle text-success mr-1"></i> Genera códigos de recuperación (+20 pts)</span> @endif
                @else
                    <i class="fas fa-check-circle text-success mr-1"></i> ¡Tu cuenta está completamente protegida!
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">

    {{-- ── COLUMNA IZQUIERDA ───────────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- ■ Autenticación de Dos Factores (2FA) ─────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#dc3545;" id="2fa">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-mobile-alt mr-2 text-danger"></i> Autenticación de Dos Factores (2FA)
                </h3>
                <div class="card-tools">
                    @if($user->hasTwoFactorEnabled())
                        <span class="badge badge-success px-2 py-1">
                            <i class="fas fa-check mr-1"></i> Activo
                        </span>
                    @else
                        <span class="badge badge-danger px-2 py-1">
                            <i class="fas fa-times mr-1"></i> No configurado
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($user->hasTwoFactorEnabled())
                    <p class="text-muted mb-3">
                        <i class="fas fa-shield-alt text-success mr-1"></i>
                        Tu cuenta está protegida con un segundo factor de autenticación (TOTP).
                        Configurado el <strong>{{ $user->two_factor_confirmed_at?->format('d/m/Y') }}</strong>.
                    </p>

                    {{-- Códigos de recuperación --}}
                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><i class="fas fa-key mr-1"></i> Códigos de recuperación</strong>
                                <div class="small text-muted mt-1">
                                    @php $codesCount = count($user->twoFactorRecoveryCodes()); @endphp
                                    @if($codesCount > 0)
                                        Tienes <strong>{{ $codesCount }}</strong> código(s) disponible(s).
                                        Úsalos si pierdes acceso a tu app autenticadora.
                                    @else
                                        <span class="text-danger font-weight-bold">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            No tienes códigos de recuperación. Genera nuevos para poder recuperar el acceso.
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('security.2fa.recovery-codes') }}" class="ml-3">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                        onclick="return confirm('¿Regenerar códigos? Los anteriores quedarán invalidados.')">
                                    <i class="fas fa-sync-alt mr-1"></i> Regenerar
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Desactivar 2FA --}}
                    <button class="btn btn-outline-danger btn-sm" data-toggle="collapse" data-target="#disableForm">
                        <i class="fas fa-times-circle mr-1"></i> Desactivar 2FA
                    </button>
                    <div class="collapse mt-3" id="disableForm">
                        <div class="card card-body bg-light border-danger">
                            <p class="text-danger small mb-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Esto eliminará la protección adicional de tu cuenta. Confirma con tu código TOTP actual.
                            </p>
                            <form method="POST" action="{{ route('2fa.disable') }}">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" name="code" class="form-control"
                                           placeholder="Código de 6 dígitos" maxlength="6"
                                           inputmode="numeric" autocomplete="one-time-code">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-danger">Desactivar</button>
                                    </div>
                                </div>
                                @error('code')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </form>
                        </div>
                    </div>

                @else
                    <p class="text-muted mb-3">
                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                        Tu cuenta no tiene 2FA activo. Configúralo para proteger tu acceso con un segundo factor.
                    </p>
                    <a href="{{ route('2fa.setup') }}" class="btn btn-danger">
                        <i class="fas fa-shield-alt mr-2"></i> Configurar 2FA ahora
                    </a>
                @endif
            </div>
        </div>

        {{-- ■ Contraseña ────────────────────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#3a57e8;" id="password">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-key mr-2" style="color:#3a57e8;"></i> Contraseña
                </h3>
                <div class="card-tools">
                    @if($user->passwordExpired(90))
                        <span class="badge badge-warning px-2 py-1">
                            <i class="fas fa-clock mr-1"></i> Desactualizada
                        </span>
                    @else
                        <span class="badge badge-success px-2 py-1">
                            <i class="fas fa-check mr-1"></i> Al día
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @php
                    $pwRef    = $user->password_changed_at ?? $user->created_at;
                    $pwDays   = $pwRef ? (int) $pwRef->diffInDays(now()) : null;
                    $pwExpiry = 90 - ($pwDays ?? 0);
                @endphp

                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-muted small">
                            @if($user->password_changed_at)
                                Última actualización: <strong>{{ $user->password_changed_at->format('d/m/Y') }}</strong>
                                (hace {{ $pwDays }} días)
                            @else
                                Sin cambios desde la creación de la cuenta ({{ $user->created_at->format('d/m/Y') }}).
                            @endif
                        </div>
                        @if($pwExpiry > 0)
                            <div class="small mt-1 {{ $pwExpiry < 15 ? 'text-danger font-weight-bold' : 'text-muted' }}">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Expira en {{ $pwExpiry }} día(s) (política de 90 días)
                            </div>
                        @else
                            <div class="small text-danger font-weight-bold mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Contraseña expirada — actualízala ahora
                            </div>
                        @endif
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('profile.edit') }}#update-password-form"
                           class="btn btn-sm {{ $user->passwordExpired(90) ? 'btn-danger' : 'btn-outline-primary' }}">
                            <i class="fas fa-lock mr-1"></i> Cambiar contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ■ Sesiones Activas ──────────────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#6f42c1;" id="sessions">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-desktop mr-2" style="color:#6f42c1;"></i> Sesiones Activas
                </h3>
                <div class="card-tools">
                    <span class="badge badge-secondary px-2 py-1">{{ $sessions->count() }} sesión(es)</span>
                </div>
            </div>
            <div class="card-body p-0">
                @forelse($sessions as $session)
                    <div class="d-flex align-items-center px-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}
                                {{ $session->is_current ? 'bg-light' : '' }}">
                        <div class="mr-3">
                            @php
                                $ua = $session->user_agent ?? '';
                                $icon = match(true) {
                                    str_contains($ua, 'Mobile') || str_contains($ua, 'Android') || str_contains($ua, 'iPhone') => 'fa-mobile-alt',
                                    default => 'fa-desktop',
                                };
                            @endphp
                            <i class="fas {{ $icon }} fa-lg text-muted"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="font-weight-bold small">
                                {{ $session->deviceName() }}
                                @if($session->is_current)
                                    <span class="badge badge-success ml-1">Esta sesión</span>
                                @endif
                            </div>
                            <div class="text-muted" style="font-size:0.8rem;">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $session->ip_address ?? 'IP desconocida' }}
                                &nbsp;&middot;&nbsp;
                                <i class="fas fa-clock mr-1"></i>
                                {{ $session->last_active_at ? $session->last_active_at->diffForHumans() : 'Sin actividad registrada' }}
                            </div>
                        </div>
                        @if(!$session->is_current)
                            <form method="POST" action="{{ route('security.sessions.revoke', $session->id) }}" class="ml-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('¿Cerrar esta sesión remota?')"
                                        title="Cerrar sesión">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-desktop fa-2x mb-2 d-block"></i>
                        No hay sesiones registradas aún.
                    </div>
                @endforelse

                @if($sessions->where('is_current', false)->count() > 0)
                    <div class="px-3 py-2 border-top bg-light">
                        <form method="POST" action="{{ route('security.sessions.revoke-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('¿Cerrar TODAS las otras sesiones? Esta sesión se mantiene activa.')">
                                <i class="fas fa-power-off mr-1"></i>
                                Cerrar todas las otras sesiones ({{ $sessions->where('is_current', false)->count() }})
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- ■ Actividad Reciente ───────────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#28a745;" id="activity">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-history mr-2 text-success"></i> Actividad Reciente de Seguridad
                </h3>
            </div>
            <div class="card-body p-0">
                @forelse($recentActivity as $log)
                    @php
                        $icon = match(true) {
                            str_contains($log->description, '2FA activado')    => ['fa-shield-alt', 'text-success'],
                            str_contains($log->description, '2FA desactivado') => ['fa-shield-alt', 'text-danger'],
                            str_contains($log->description, 'verificado')      => ['fa-check-circle', 'text-info'],
                            str_contains($log->description, 'revocad')         => ['fa-sign-out-alt', 'text-warning'],
                            str_contains($log->description, 'recuperación')    => ['fa-key', 'text-warning'],
                            default                                             => ['fa-info-circle', 'text-secondary'],
                        };
                    @endphp
                    <div class="d-flex align-items-start px-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="mr-2 mt-1">
                            <i class="fas {{ $icon[0] }} {{ $icon[1] }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold">{{ $log->description }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                                &middot; {{ $log->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-history fa-2x mb-2 d-block"></i>
                        Sin actividad registrada aún.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── COLUMNA DERECHA ─────────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- ■ Resumen de seguridad ────────────────────────────────────────── --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background:#0d1b2a;">
                <h3 class="card-title font-weight-bold text-white">
                    <i class="fas fa-list-check mr-2" style="color:#00b4d8;"></i> Resumen
                </h3>
            </div>
            <div class="list-group list-group-flush">

                {{-- 2FA --}}
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="small"><i class="fas fa-mobile-alt mr-2 text-muted"></i> 2FA</span>
                    @if($user->hasTwoFactorEnabled())
                        <span class="badge badge-success">Activo</span>
                    @else
                        <span class="badge badge-danger">Inactivo</span>
                    @endif
                </div>

                {{-- Contraseña --}}
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="small"><i class="fas fa-key mr-2 text-muted"></i> Contraseña</span>
                    @if($user->passwordExpired(90))
                        <span class="badge badge-warning">Expirada</span>
                    @else
                        <span class="badge badge-success">Al día</span>
                    @endif
                </div>

                {{-- Códigos de recuperación --}}
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="small"><i class="fas fa-life-ring mr-2 text-muted"></i> Cód. recuperación</span>
                    @php $codesCount = count($user->twoFactorRecoveryCodes()) @endphp
                    @if($user->hasTwoFactorEnabled())
                        @if($codesCount > 0)
                            <span class="badge badge-success">{{ $codesCount }} disponibles</span>
                        @else
                            <span class="badge badge-danger">Sin códigos</span>
                        @endif
                    @else
                        <span class="badge badge-secondary">N/A</span>
                    @endif
                </div>

                {{-- Sesiones --}}
                <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                    <span class="small"><i class="fas fa-desktop mr-2 text-muted"></i> Sesiones activas</span>
                    <span class="badge badge-{{ $sessions->count() > 2 ? 'warning' : 'info' }}">{{ $sessions->count() }}</span>
                </div>

            </div>
        </div>

        {{-- ■ Navegación rápida ───────────────────────────────────────────── --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-link mr-2 text-secondary"></i> Navegación
                </h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="#2fa" class="list-group-item list-group-item-action">
                    <i class="fas fa-mobile-alt mr-2 text-danger"></i> Dos Factores (2FA)
                </a>
                <a href="#password" class="list-group-item list-group-item-action">
                    <i class="fas fa-key mr-2" style="color:#3a57e8;"></i> Contraseña
                </a>
                <a href="#sessions" class="list-group-item list-group-item-action">
                    <i class="fas fa-desktop mr-2" style="color:#6f42c1;"></i> Sesiones activas
                </a>
                <a href="#activity" class="list-group-item list-group-item-action">
                    <i class="fas fa-history mr-2 text-success"></i> Actividad reciente
                </a>
            </div>
        </div>

        {{-- ■ Consejos de seguridad ───────────────────────────────────────── --}}
        <div class="card shadow-sm mb-4" style="border-top:3px solid #00b4d8;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-lightbulb mr-2" style="color:#00b4d8;"></i> Buenas prácticas
                </h3>
            </div>
            <div class="card-body p-3">
                <ul class="mb-0 pl-3" style="font-size:0.82rem;line-height:1.8;">
                    <li>Activa <strong>2FA</strong> para proteger tu cuenta con un segundo factor.</li>
                    <li>Cambia tu contraseña cada <strong>90 días</strong>.</li>
                    <li>Guarda tus <strong>códigos de recuperación</strong> en un lugar seguro (fuera del sistema).</li>
                    <li>Revisa las <strong>sesiones activas</strong> regularmente y cierra las que no reconozcas.</li>
                    <li>Nunca compartas tu código TOTP con nadie.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

@stop

@section('js')
<script>
// Auto-dismiss flash alerts after 8s
setTimeout(function() {
    document.querySelectorAll('.alert-success, .alert-info').forEach(function(el) {
        if (el.classList.contains('alert-dismissible')) {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }
    });
}, 8000);
</script>
@stop
