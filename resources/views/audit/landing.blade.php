@extends('adminlte::page')
@section('title', 'Auditoría Global')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 font-weight-bold" style="color:#1e293b;font-size:1.4rem;letter-spacing:-.01em;">
            <i class="fas fa-search-dollar mr-2" style="color:#334155;"></i>
            Auditoría Global
        </h1>
        <small class="text-muted">Inventario · Movimientos · Seguridad — {{ now()->format('d/m/Y') }}</small>
    </div>
</div>
@stop

@section('content')

{{-- ── KPI Strip ───────────────────────────────────────────────────────── --}}
<div class="card shadow-sm mb-4" style="border:1px solid #e2e8f0;border-radius:10px;">
    <div class="card-body py-3">
        <div class="row text-center" style="gap:0;">

            <div class="col audit-kpi" style="border-right:1px solid #e2e8f0;">
                <div class="audit-kpi-num">{{ number_format($stats['total_assets']) }}</div>
                <div class="audit-kpi-label">Activos registrados</div>
            </div>

            <div class="col audit-kpi" style="border-right:1px solid #e2e8f0;">
                <div class="audit-kpi-num text-primary">{{ number_format($stats['ti_assets']) }}</div>
                <div class="audit-kpi-label">Activos TI</div>
            </div>

            <div class="col audit-kpi" style="border-right:1px solid #e2e8f0;">
                <div class="audit-kpi-num" style="color:#6d28d9;">{{ number_format($stats['otro_assets']) }}</div>
                <div class="audit-kpi-label">Otros activos</div>
            </div>

            <div class="col audit-kpi" style="border-right:1px solid #e2e8f0;">
                <div class="audit-kpi-num text-success">{{ number_format($stats['active_assignments']) }}</div>
                <div class="audit-kpi-label">Asignaciones activas</div>
            </div>

            <div class="col audit-kpi" style="border-right:1px solid #e2e8f0;">
                <div class="audit-kpi-num text-warning">{{ number_format($stats['active_loans']) }}</div>
                <div class="audit-kpi-label">Préstamos activos</div>
            </div>

            <div class="col audit-kpi">
                @if($stats['overdue_loans'] > 0)
                    <div class="audit-kpi-num text-danger">{{ number_format($stats['overdue_loans']) }}</div>
                    <div class="audit-kpi-label">
                        <span class="badge badge-danger" style="font-size:.65rem;">Vencidos</span>
                    </div>
                @else
                    <div class="audit-kpi-num text-success">0</div>
                    <div class="audit-kpi-label text-success" style="font-weight:600;">Sin vencidos</div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- ── Módulos de auditoría ────────────────────────────────────────────── --}}
<div class="row">

    {{-- COLUMNA IZQUIERDA: Inventario --}}
    <div class="col-lg-4 mb-4">
        <div class="audit-section-card h-100">
            <div class="audit-section-header">
                <span class="audit-section-icon" style="background:#eff6ff;">
                    <i class="fas fa-layer-group" style="color:#1d4ed8;"></i>
                </span>
                <div>
                    <div class="audit-section-title">Inventario</div>
                    <div class="audit-section-sub">{{ $stats['total_assets'] }} activos en total</div>
                </div>
            </div>
            <div class="audit-section-body">

                <a href="{{ route('audit.hub', ['tab' => 'ti']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#dbeafe;">
                        <i class="fas fa-laptop" style="color:#1d4ed8;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Activos TI</strong>
                        <small>{{ $stats['ti_assets'] }} equipos registrados</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'otros']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#ede9fe;">
                        <i class="fas fa-boxes" style="color:#6d28d9;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Otros Activos</strong>
                        <small>{{ $stats['otro_assets'] }} activos registrados</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'bajas']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#fee2e2;">
                        <i class="fas fa-archive" style="color:#b91c1c;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Bajas</strong>
                        <small>Dados de baja, donados o vendidos</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

            </div>
        </div>
    </div>

    {{-- COLUMNA CENTRO: Movimientos --}}
    <div class="col-lg-4 mb-4">
        <div class="audit-section-card h-100">
            <div class="audit-section-header">
                <span class="audit-section-icon" style="background:#f0fdf4;">
                    <i class="fas fa-exchange-alt" style="color:#15803d;"></i>
                </span>
                <div>
                    <div class="audit-section-title">Movimientos</div>
                    <div class="audit-section-sub">Asignaciones, préstamos y log</div>
                </div>
            </div>
            <div class="audit-section-body">

                <a href="{{ route('audit.hub', ['tab' => 'asignaciones']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#dcfce7;">
                        <i class="fas fa-user-check" style="color:#15803d;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Asignaciones</strong>
                        <small>{{ $stats['active_assignments'] }} asignaciones activas</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'prestamos']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#fefce8;">
                        <i class="fas fa-handshake" style="color:#a16207;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Préstamos</strong>
                        <small>
                            {{ $stats['active_loans'] }} activos
                            @if($stats['overdue_loans'] > 0)
                                &nbsp;<span class="badge badge-danger" style="font-size:.62rem;">{{ $stats['overdue_loans'] }} vencidos</span>
                            @endif
                        </small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'log']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#f1f5f9;">
                        <i class="fas fa-history" style="color:#475569;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Log de Movimientos</strong>
                        <small>Historial de entregas y devoluciones</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

            </div>
        </div>
    </div>

    {{-- COLUMNA DERECHA: Sistema y Seguridad --}}
    <div class="col-lg-4 mb-4">
        <div class="audit-section-card h-100">
            <div class="audit-section-header">
                <span class="audit-section-icon" style="background:#f8fafc;">
                    <i class="fas fa-shield-alt" style="color:#334155;"></i>
                </span>
                <div>
                    <div class="audit-section-title">Sistema y Seguridad</div>
                    <div class="audit-section-sub">Trazabilidad ISO 27001</div>
                </div>
            </div>
            <div class="audit-section-body">

                <a href="{{ route('audit.hub', ['tab' => 'actividad']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#e0e7ff;">
                        <i class="fas fa-shield-alt" style="color:#4338ca;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Actividad del Sistema</strong>
                        <small>Log de acciones por usuario</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'sesiones']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#f1f5f9;">
                        <i class="fas fa-user-clock" style="color:#475569;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Sesiones de Usuarios</strong>
                        <small>IP, dispositivo, última actividad</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

                <a href="{{ route('audit.hub', ['tab' => 'exportaciones']) }}" class="audit-module-row">
                    <span class="audit-module-icon" style="background:#f0fdf4;">
                        <i class="fas fa-download" style="color:#15803d;"></i>
                    </span>
                    <span class="audit-module-info">
                        <strong>Exportaciones</strong>
                        <small>Registro de descargas de datos</small>
                    </span>
                    <i class="fas fa-chevron-right audit-module-arrow"></i>
                </a>

            </div>
        </div>
    </div>

</div>

@stop

@section('css')
<style>
/* ── KPI Strip ─────────────────────────────────────────────────── */
.audit-kpi {
    padding: 6px 8px;
}
.audit-kpi-num {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.1;
    color: #1e293b;
    letter-spacing: -.02em;
}
.audit-kpi-label {
    font-size: .72rem;
    color: #64748b;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-weight: 500;
}

/* ── Section cards ─────────────────────────────────────────────── */
.audit-section-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
}
.audit-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    background: #fafbfc;
}
.audit-section-icon {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}
.audit-section-title {
    font-size: .9rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.audit-section-sub {
    font-size: .72rem;
    color: #94a3b8;
    margin-top: 1px;
}
.audit-section-body {
    flex: 1;
}

/* ── Module rows ───────────────────────────────────────────────── */
.audit-module-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 18px;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none !important;
    color: #1e293b !important;
    transition: background .12s ease;
}
.audit-module-row:last-child {
    border-bottom: none;
}
.audit-module-row:hover {
    background: #f8fafc;
    text-decoration: none !important;
}
.audit-module-icon {
    width: 34px;
    height: 34px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .9rem;
}
.audit-module-info {
    flex: 1;
}
.audit-module-info strong {
    display: block;
    font-size: .85rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.2;
}
.audit-module-info small {
    font-size: .75rem;
    color: #64748b;
}
.audit-module-arrow {
    font-size: .65rem;
    color: #cbd5e1;
    flex-shrink: 0;
}
.audit-module-row:hover .audit-module-arrow {
    color: #94a3b8;
}
</style>
@stop
