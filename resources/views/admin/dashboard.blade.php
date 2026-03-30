@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;font-size:1.15rem;">
                <i class="fas fa-tachometer-alt mr-2" style="color:#1d4ed8;"></i> Panel de Control
            </h1>
            <small class="text-muted">
                {{ auth()->user()->name }} &mdash; {{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}
            </small>
        </div>
    </div>
@stop

@section('content')

{{-- ═══════════════════════════════════════════════════
     KPI STRIP — fila única de métricas clave
════════════════════════════════════════════════════ --}}
<div class="kpi-strip mb-3">

    <div class="kpi-chip">
        <span class="kpi-chip-val">{{ $totalAssets }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-boxes"></i> Total Activos</span>
    </div>

    @can('tech.assets.view')
    <a href="{{ route('tech.assets.index') }}" class="kpi-chip kpi-chip-link">
        <span class="kpi-chip-val text-primary">{{ $tiAssets }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-laptop"></i> Activos TI</span>
    </a>

    <a href="{{ route('assets.index') }}" class="kpi-chip kpi-chip-link">
        <span class="kpi-chip-val" style="color:#d97706;">{{ $otroAssets }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-chair"></i> Otros Activos</span>
    </a>

    <a href="{{ route('tech.assignments.index') }}" class="kpi-chip kpi-chip-link">
        <span class="kpi-chip-val text-success">{{ $activeAssignments }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-user-check"></i> Asign. TI</span>
    </a>
    @endcan

    <div class="kpi-chip">
        <span class="kpi-chip-val" style="color:#7c3aed;">{{ $otroActiveAssignments }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-clipboard-list"></i> Asign. Otros</span>
    </div>

    <div class="kpi-chip">
        <span class="kpi-chip-val" style="color:#0891b2;">{{ $activeLoans }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-exchange-alt"></i> Préstamos</span>
    </div>

    @if($overdueLoans > 0)
    <div class="kpi-chip" style="border-color:#fca5a5;background:#fff5f5;">
        <span class="kpi-chip-val text-danger">{{ $overdueLoans }}</span>
        <span class="kpi-chip-lbl" style="color:#dc2626;"><i class="fas fa-exclamation-triangle"></i> Vencidos</span>
    </div>
    @elseif($dueSoonLoans > 0)
    <div class="kpi-chip" style="border-color:#fde68a;background:#fffbeb;">
        <span class="kpi-chip-val" style="color:#d97706;">{{ $dueSoonLoans }}</span>
        <span class="kpi-chip-lbl" style="color:#d97706;"><i class="fas fa-clock"></i> Por Vencer</span>
    </div>
    @endif

    @can('collaborators.index')
    <a href="{{ route('collaborators.index') }}" class="kpi-chip kpi-chip-link">
        <span class="kpi-chip-val" style="color:#374151;">{{ $totalCollaborators }}</span>
        <span class="kpi-chip-lbl"><i class="fas fa-users"></i> Colaboradores</span>
    </a>
    @endcan

</div>

{{-- ═══════════════════════════════════════════════════
     FILA CENTRAL — Gráficos + Acciones + Insights
════════════════════════════════════════════════════ --}}
<div class="row mb-3">

    {{-- ── 3 Donuts — más ancho ── --}}
    @can('tech.assets.view')
    <div class="col-lg-7 mb-3 mb-lg-0">
        <div class="card shadow-sm h-100">
            <div class="card-header py-2" style="background:#f8f9fa;border-bottom:1px solid #e9ecef;">
                <h6 class="mb-0 font-weight-bold" style="font-size:.75rem;color:#374151;letter-spacing:.04em;text-transform:uppercase;">
                    <i class="fas fa-chart-pie mr-1" style="color:#1d4ed8;"></i> Distribución por Estado
                </h6>
            </div>
            <div class="card-body py-3 px-1">
                <div class="row text-center no-gutters">

                    <div class="col-4">
                        <p class="donut-title">Activos TI</p>
                        <div class="donut-wrap">
                            @if($tiAssets > 0)
                                <canvas id="chartTiStatus"></canvas>
                                <div class="donut-center">
                                    <span>{{ $tiAssets }}</span>
                                    <small>total</small>
                                </div>
                            @else
                                <div class="donut-empty">Sin datos</div>
                            @endif
                        </div>
                        <div id="legendTiStatus" class="donut-legend mt-2"></div>
                    </div>

                    <div class="col-4">
                        <p class="donut-title">Otros Activos</p>
                        <div class="donut-wrap">
                            @if($otroAssets > 0)
                                <canvas id="chartOtroStatus"></canvas>
                                <div class="donut-center">
                                    <span>{{ $otroAssets }}</span>
                                    <small>total</small>
                                </div>
                            @else
                                <div class="donut-empty">Sin datos</div>
                            @endif
                        </div>
                        <div id="legendOtroStatus" class="donut-legend mt-2"></div>
                    </div>

                    <div class="col-4">
                        <p class="donut-title">Modalidad</p>
                        <div class="donut-wrap">
                            @if($activeAssignments > 0)
                                <canvas id="chartModality"></canvas>
                                <div class="donut-center">
                                    <span>{{ $activeAssignments }}</span>
                                    <small>asign.</small>
                                </div>
                            @else
                                <div class="donut-empty">Sin datos</div>
                            @endif
                        </div>
                        <div id="legendModality" class="donut-legend mt-2"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- ── Columna derecha: Acciones + Sistema ── --}}
    <div class="col-lg-5">
        <div class="card shadow-sm mb-2 mt-lg-3">
            <div class="card-header py-2" style="background:#f8f9fa;border-bottom:1px solid #e9ecef;">
                <h6 class="mb-0 font-weight-bold" style="font-size:.75rem;color:#374151;letter-spacing:.04em;text-transform:uppercase;">
                    <i class="fas fa-bolt mr-1" style="color:#1e3a8a;"></i> Acciones Rápidas
                </h6>
            </div>
            <div class="card-body p-2">
                <div class="row no-gutters" style="margin:0 -3px;">
                    @can('tech.assets.assign')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('tech.assignments.create') }}" class="qa-btn" style="border-color:#1d4ed8;color:#1d4ed8;">
                            <i class="fas fa-user-check"></i><span>Nueva Asignación</span>
                        </a>
                    </div>
                    @endcan
                    @can('tech.assets.create')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('tech.assets.create') }}" class="qa-btn" style="border-color:#059669;color:#059669;">
                            <i class="fas fa-laptop"></i><span>Registrar TI</span>
                        </a>
                    </div>
                    @endcan
                    @can('collaborators.create')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('collaborators.create') }}" class="qa-btn" style="border-color:#0891b2;color:#0891b2;">
                            <i class="fas fa-user-plus"></i><span>Colaborador</span>
                        </a>
                    </div>
                    @endcan
                    @can('tech.history.view')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('tech.history.index') }}" class="qa-btn" style="border-color:#6b7280;color:#6b7280;">
                            <i class="fas fa-history"></i><span>Historial TI</span>
                        </a>
                    </div>
                    @endcan
                    @can('tech.types.index')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('tech.types.index') }}" class="qa-btn" style="border-color:#d97706;color:#d97706;">
                            <i class="fas fa-tags"></i><span>Tipos Activo</span>
                        </a>
                    </div>
                    @endcan
                    @can('admin.settings')
                    <div class="col-4 col-md-2 px-1 mb-1">
                        <a href="{{ route('admin.settings') }}" class="qa-btn" style="border-color:#374151;color:#374151;">
                            <i class="fas fa-cog"></i><span>Configuración</span>
                        </a>
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Estado sistema --}}
        <div class="card shadow-sm" style="border-left:3px solid #16a34a;">
            <div class="card-body py-2 px-3" style="font-size:.74rem;">
                <div class="d-flex align-items-center mb-1">
                    <span style="width:6px;height:6px;border-radius:50%;background:#16a34a;display:inline-block;" class="mr-2"></span>
                    <strong>Sistema Operativo</strong>
                </div>
                <div class="d-flex justify-content-between text-muted">
                    <span>Entorno</span>
                    <strong class="text-dark">{{ strtoupper(config('app.env')) }}</strong>
                </div>
                <div class="d-flex justify-content-between text-muted">
                    <span>Versión</span>
                    <strong class="text-dark">{{ config('app.version', '1.0.0') }}</strong>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════
     PRÉSTAMOS TI POR VENCER — con barra de progreso
════════════════════════════════════════════════════ --}}
@if($upcomingLoans->isNotEmpty())
<div class="card shadow-sm mb-3" style="border-top:3px solid #0891b2;">
    <div class="card-header py-2" style="background:#f8f9fa;">
        <h6 class="mb-0 font-weight-bold" style="font-size:.75rem;color:#374151;letter-spacing:.04em;text-transform:uppercase;">
            <i class="fas fa-exchange-alt mr-1" style="color:#0891b2;"></i>
            Préstamos TI Próximos a Vencer
            <span class="badge badge-light ml-1" style="font-size:.68rem;border:1px solid #e5e7eb;">{{ $upcomingLoans->count() }}</span>
        </h6>
    </div>
    <div class="card-body py-2 px-3">
        <div class="row">
            @foreach($upcomingLoans as $loan)
            @php
                $days        = $loan->daysRemaining();
                $totalDays   = max(1, $loan->start_date->diffInDays($loan->end_date));
                $usedDays    = $loan->start_date->diffInDays(now()->min($loan->end_date));
                $pct         = min(100, round($usedDays / $totalDays * 100));
                // Color de la barra según urgencia
                $barColor    = $days < 0 ? '#dc2626' : ($pct >= 85 ? '#f97316' : ($pct >= 60 ? '#eab308' : '#16a34a'));
                $badgeClass  = $days < 0 ? 'badge-danger' : ($days === 0 ? 'badge-warning text-dark' : 'badge-light text-dark');
                $badgeText   = $days < 0 ? 'Vencido' : ($days === 0 ? 'Hoy' : "{$days}d");
            @endphp
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="loan-card" style="border-left:3px solid {{ $barColor }};">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <code style="font-size:.76rem;">{{ $loan->asset->internal_code }}</code>
                            <small class="text-muted d-block" style="font-size:.68rem;">{{ $loan->asset->type?->name }}</small>
                        </div>
                        <span class="badge {{ $badgeClass }}" style="font-size:.68rem;">{{ $badgeText }}</span>
                    </div>
                    <div class="font-weight-bold mb-1" style="font-size:.76rem;color:#374151;">
                        <i class="fas fa-user mr-1" style="color:#9ca3af;font-size:.62rem;"></i>
                        {{ $loan->collaborator->full_name }}
                    </div>
                    <div class="d-flex justify-content-between mb-1" style="font-size:.68rem;color:#6b7280;">
                        <span>{{ $loan->start_date->format('d/m/Y') }}</span>
                        <span>{{ $loan->end_date->format('d/m/Y') }}</span>
                    </div>
                    {{-- Barra de progreso del período del préstamo --}}
                    <div style="height:4px;background:#e5e7eb;border-radius:4px;overflow:hidden;">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:4px;transition:width .4s;"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════
     FILA INFERIOR — Actividad reciente + Asistente IA
════════════════════════════════════════════════════ --}}
<div class="row mt-3">

    {{-- Actividad reciente --}}
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm h-100" style="border-top:3px solid #1d4ed8;">
            <div class="card-header py-2" style="background:#f8f9fa;">
                <h6 class="mb-0 font-weight-bold" style="font-size:.75rem;color:#374151;letter-spacing:.04em;text-transform:uppercase;">
                    <i class="fas fa-clock mr-1" style="color:#1d4ed8;"></i> Actividad Reciente
                </h6>
                <div class="card-tools">
                    @can('tech.history.view')
                        <a href="{{ route('tech.history.index') }}" class="btn btn-xs btn-outline-secondary">Ver todo</a>
                    @endcan
                </div>
            </div>
            <div class="card-body p-0">
                @if($recentActivities->isEmpty())
                    <div class="text-center py-3 text-muted" style="font-size:.8rem;">
                        <i class="fas fa-inbox fa-lg mb-1 d-block" style="opacity:.25;"></i>
                        Sin actividad registrada aún.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" style="font-size:.77rem;">
                            <thead style="background:#f8f9fa;font-size:.67rem;text-transform:uppercase;letter-spacing:.04em;">
                                <tr>
                                    <th class="pl-3">Tipo</th>
                                    <th>Activo</th>
                                    <th>Colaborador</th>
                                    <th class="d-none d-md-table-cell">Sede</th>
                                    <th class="text-right pr-3">Hace</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentActivities->take(6) as $aa)
                                <tr>
                                    <td class="pl-3">
                                        @if($aa->isReturned())
                                            <span class="badge badge-secondary" style="font-size:.63rem;"><i class="fas fa-undo mr-1"></i>Devol.</span>
                                        @else
                                            <span class="badge badge-primary" style="font-size:.63rem;"><i class="fas fa-plus mr-1"></i>Asign.</span>
                                        @endif
                                    </td>
                                    <td><code style="font-size:.74rem;">{{ $aa->asset->internal_code }}</code></td>
                                    <td>
                                        @if($aa->assignment?->collaborator)
                                            <a href="{{ route('collaborators.show', $aa->assignment->collaborator) }}" class="text-dark font-weight-bold">
                                                {{ $aa->assignment->collaborator->full_name }}
                                            </a>
                                        @else
                                            <span class="text-dark font-weight-bold">
                                                {{ $aa->assignment?->area?->name ? 'Área: ' . $aa->assignment->area->name : 'Sin colaborador' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-muted d-none d-md-table-cell">
                                        {{ $aa->assignment?->collaborator?->branch?->name ?? $aa->assignment?->area?->branch?->name ?? '-' }}
                                    </td>
                                    <td class="text-muted text-right pr-3">
                                        {{ ($aa->isReturned() ? $aa->returned_at : $aa->assigned_at)?->diffForHumans() }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- AXI — Asistente IA --}}
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm h-100" style="border-top:3px solid #7c3aed;">
            <div class="card-header py-2" style="background:#f8f9fa;">
                <h6 class="mb-0 font-weight-bold" style="font-size:.75rem;color:#374151;letter-spacing:.04em;text-transform:uppercase;">
                    <i class="fas fa-robot mr-1" style="color:#7c3aed;"></i> AXI — Asistente IA
                </h6>
                <div class="card-tools">
                    <a href="{{ route('ai.hub') }}" class="btn btn-xs btn-outline-secondary" style="font-size:.65rem;">
                        Ver más
                    </a>
                </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-3 px-3">

                {{-- Imagen C: robot de pie con AX en el pecho --}}
                <img src="{{ asset('img/axi/axi-full.png') }}"
                     alt="AXI"
                     style="max-height:150px;max-width:100%;object-fit:contain;margin-bottom:10px;"
                     onerror="this.style.display='none';document.getElementById('axi-dash-fallback').style.display='block';">
                {{-- Fallback si la imagen no está guardada aún --}}
                <div id="axi-dash-fallback" style="display:none;" class="mb-3">
                    <div class="ai-icon-wrap">
                        <i class="fas fa-robot" style="font-size:2.2rem;color:#7c3aed;opacity:.85;"></i>
                        <span class="ai-pulse"></span>
                    </div>
                </div>

                <p class="font-weight-bold mb-1" style="font-size:.88rem;color:#374151;">
                    Próximamente — <span style="color:#7c3aed;">AXI</span>
                </p>
                <p class="text-muted mb-3" style="font-size:.74rem;line-height:1.5;">
                    Consulta tu inventario en lenguaje natural.<br>
                    <em>"¿Qué laptops están disponibles en Bogotá?"</em>
                </p>

                <div class="ai-input-preview w-100">
                    <input type="text"
                           class="form-control form-control-sm"
                           placeholder="Escribe tu consulta aquí..."
                           disabled
                           style="border-radius:20px;border:1.5px solid #ddd8fe;background:#faf5ff;color:#374151;font-size:.74rem;cursor:not-allowed;">
                    <button class="ai-send-btn" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <small class="text-muted mt-2" style="font-size:.65rem;">
                    Integración con IA en desarrollo
                </small>
            </div>
        </div>
    </div>

</div>

@stop

@section('css')
<style>
/* ── KPI Strip ──────────────────────────────────────────────── */
.kpi-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.kpi-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 16px;
    min-width: 88px;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    text-decoration: none;
    transition: box-shadow .15s, transform .15s;
}
.kpi-chip-link:hover {
    box-shadow: 0 3px 10px rgba(0,0,0,.1);
    transform: translateY(-2px);
    text-decoration: none;
}
.kpi-chip-val {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.1;
}
.kpi-chip-lbl {
    font-size: .64rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.kpi-chip-lbl i { font-size: .58rem; margin-right: 2px; }

/* ── Donut charts ────────────────────────────────────────────── */
.donut-title {
    font-size: .7rem;
    font-weight: 600;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 5px;
}
.donut-wrap {
    position: relative;
    width: 110px;
    height: 110px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}
.donut-wrap canvas { width: 110px !important; height: 110px !important; }
.donut-center {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}
.donut-center span { font-size: 1.2rem; font-weight: 700; color: #1e293b; line-height: 1; }
.donut-center small { font-size: .6rem; color: #6b7280; text-transform: uppercase; }
.donut-empty {
    font-size: .72rem; color: #9ca3af;
    width: 110px; height: 110px;
    display: flex; align-items: center; justify-content: center;
    border: 2px dashed #e5e7eb; border-radius: 50%;
}
.donut-legend {
    display: flex; flex-wrap: wrap; justify-content: center; gap: 2px 5px;
}
.dl-item { display: inline-flex; align-items: center; gap: 3px; font-size: .63rem; color: #374151; }
.dl-dot  { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* ── Quick Action Buttons ────────────────────────────────────── */
.qa-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    text-align: center;
    padding: 7px 4px;
    border: 1.5px solid;
    border-radius: 8px;
    background: transparent;
    text-decoration: none;
    font-size: .66rem;
    font-weight: 600;
    height: 54px;
    width: 100%;
    transition: background .15s;
}
.qa-btn i { font-size: .9rem; }
.qa-btn:hover { text-decoration: none; background: rgba(0,0,0,.04); }

/* ── Loan Cards ──────────────────────────────────────────────── */
.loan-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

/* ── AI Assistant placeholder ────────────────────────────────── */
.ai-icon-wrap {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.ai-pulse {
    position: absolute;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 2px solid #7c3aed;
    opacity: .25;
    animation: ai-ring 2s ease-out infinite;
}
@keyframes ai-ring {
    0%   { transform: scale(.7); opacity: .35; }
    100% { transform: scale(1.4); opacity: 0; }
}
.ai-input-preview {
    position: relative;
    display: flex;
    align-items: center;
}
.ai-input-preview .form-control {
    padding-right: 36px;
}
.ai-send-btn {
    position: absolute;
    right: 6px;
    background: none;
    border: none;
    color: #c4b5fd;
    font-size: .8rem;
    cursor: not-allowed;
    padding: 0;
}

/* ── Cards generales ─────────────────────────────────────────── */
.card { border-radius: 10px; }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
if (typeof Chart === 'undefined') {
    console.warn('Chart.js no cargó; se omiten gráficos de dashboard.');
}

const STATUS_COLORS = {
    'Disponible':    '#16a34a',
    'Asignado':      '#1d4ed8',
    'Mantenimiento': '#d97706',
    'En Garantía':   '#0ea5e9',
    'En Traslado':   '#0891b2',
    'En Bodega':     '#64748b',
    'Baja':          '#dc2626',
    'Donado':        '#374151',
    'Vendido':       '#111827',
    'Préstamo':      '#7c3aed',
};
const MODALITY_COLORS  = { 'remoto':'#1d4ed8', 'presencial':'#16a34a', 'hibrido':'#d97706' };
const MODALITY_LABELS  = { 'remoto':'Remoto',  'presencial':'Presencial', 'hibrido':'Mixto' };

const CHART_OPTS = {
    cutoutPercentage: 68,
    legend: { display: false },
    tooltips: {
        callbacks: {
            label: function(item, data) {
                return ' ' + data.labels[item.index] + ': ' + data.datasets[item.datasetIndex].data[item.index];
            }
        }
    },
    animation: { animateScale: true }
};

function buildLegend(id, labels, colors) {
    const el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = labels.map((l, i) =>
        `<span class="dl-item"><span class="dl-dot" style="background:${colors[i]};"></span>${l}</span>`
    ).join('');
}

@if($tiAssets > 0)
(function () {
    if (typeof Chart === 'undefined') return;
    const data = @json($tiByStatus);
    const labels = Object.keys(data), values = Object.values(data);
    const colors = labels.map(l => STATUS_COLORS[l] || '#6b7280');
    new Chart(document.getElementById('chartTiStatus'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: CHART_OPTS
    });
    buildLegend('legendTiStatus', labels, colors);
})();
@endif

@if($otroAssets > 0)
(function () {
    if (typeof Chart === 'undefined') return;
    const data = @json($otroByStatus);
    const labels = Object.keys(data), values = Object.values(data);
    const colors = labels.map(l => STATUS_COLORS[l] || '#6b7280');
    new Chart(document.getElementById('chartOtroStatus'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: CHART_OPTS
    });
    buildLegend('legendOtroStatus', labels, colors);
})();
@endif

@if($activeAssignments > 0)
(function () {
    if (typeof Chart === 'undefined') return;
    const raw = @json($assignmentsByModality);
    const labels = Object.keys(raw).map(k => MODALITY_LABELS[k] || k);
    const values = Object.values(raw);
    const colors = Object.keys(raw).map(k => MODALITY_COLORS[k] || '#6b7280');
    new Chart(document.getElementById('chartModality'), {
        type: 'doughnut',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }] },
        options: CHART_OPTS
    });
    buildLegend('legendModality', labels, colors);
})();
@endif
</script>
@stop
