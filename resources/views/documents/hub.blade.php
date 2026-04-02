@extends('adminlte::page')
@section('title', 'Gestor Documental')

@section('content_header')
<div class="d-flex align-items-center justify-content-between">
    <div>
        <h1 class="m-0 font-weight-bold" style="color:#1e293b;font-size:1.35rem;letter-spacing:-.01em;">
            <i class="fas fa-folder-open mr-2" style="color:#334155;font-size:1.1rem;"></i>
            Gestor Documental
        </h1>
        <small class="text-muted">Repositorio de actas · Auditoría ISO 27001 — {{ now()->format('d/m/Y') }}</small>
    </div>
    @can('actas.create')
    <a href="{{ route('actas.create') }}" class="btn btn-sm btn-primary" style="font-size:.8rem;">
        <i class="fas fa-plus mr-1"></i> Nueva Acta
    </a>
    @endcan
</div>
@stop

@section('content')

{{-- ── Acceso rápido global ──────────────────────────────────────────── --}}
<div class="row mb-4">

    <div class="col-md-4 mb-3">
        <a href="{{ route('actas.index') }}" class="doc-quick-card">
            <span class="doc-quick-icon" style="background:#eff6ff;">
                <i class="fas fa-file-alt" style="color:#1d4ed8;"></i>
            </span>
            <div class="doc-quick-body">
                <div class="doc-quick-title">Todas las Actas</div>
                <div class="doc-quick-sub">Repositorio completo TI + Otros</div>
            </div>
            <i class="fas fa-arrow-right doc-quick-arrow"></i>
        </a>
    </div>

    <div class="col-md-4 mb-3">
        <a href="{{ route('actas.index', ['filter' => 'pending']) }}" class="doc-quick-card">
            <span class="doc-quick-icon" style="background:#fef3c7;">
                <i class="fas fa-pen-nib" style="color:#92400e;"></i>
            </span>
            <div class="doc-quick-body">
                <div class="doc-quick-title">Pendientes de Firma</div>
                <div class="doc-quick-sub">Actas en espera de aprobación</div>
            </div>
            <i class="fas fa-arrow-right doc-quick-arrow"></i>
        </a>
    </div>

    <div class="col-md-4 mb-3">
        <a href="{{ route('actas.index', ['filter' => 'signed']) }}" class="doc-quick-card">
            <span class="doc-quick-icon" style="background:#f0fdf4;">
                <i class="fas fa-check-circle" style="color:#15803d;"></i>
            </span>
            <div class="doc-quick-body">
                <div class="doc-quick-title">Archivo General</div>
                <div class="doc-quick-sub">Actas firmadas y auditadas</div>
            </div>
            <i class="fas fa-arrow-right doc-quick-arrow"></i>
        </a>
    </div>

</div>

{{-- ── Repositorio por categoría ────────────────────────────────────── --}}
<div class="row">

    {{-- TI --}}
    <div class="col-lg-6 mb-4">
        <div class="doc-repo-card">
            <div class="doc-repo-header" style="border-left:3px solid #1d4ed8;">
                <span class="doc-repo-icon" style="background:#eff6ff;">
                    <i class="fas fa-laptop" style="color:#1d4ed8;"></i>
                </span>
                <div>
                    <div class="doc-repo-title">Activos TI</div>
                    <div class="doc-repo-sub">Documentos de equipos tecnológicos</div>
                </div>
            </div>
            <div class="doc-repo-body">

                <a href="{{ route('actas.index', ['category' => 'TI', 'type' => 'entrega']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#dbeafe;border:1.5px solid #93c5fd;">
                        <i class="fas fa-hand-holding" style="color:#1d4ed8;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Actas de Entrega</span>
                    <span class="doc-row-type" style="color:#1d4ed8;">Entrega</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'TI', 'type' => 'devolucion']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#fef3c7;border:1.5px solid #fcd34d;">
                        <i class="fas fa-undo" style="color:#92400e;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Actas de Devolución</span>
                    <span class="doc-row-type" style="color:#92400e;">Devolución</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'TI', 'type' => 'prestamo']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#e0f2fe;border:1.5px solid #7dd3fc;">
                        <i class="fas fa-handshake" style="color:#0369a1;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Cartas de Préstamo</span>
                    <span class="doc-row-type" style="color:#0369a1;">Préstamo</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'TI', 'filter' => 'signed']) }}" class="doc-row doc-row-archive">
                    <span class="doc-row-dot" style="background:#f1f5f9;border:1.5px solid #cbd5e1;">
                        <i class="fas fa-archive" style="color:#475569;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name" style="color:#64748b;">Archivo TI</span>
                    <span class="doc-row-type" style="color:#94a3b8;">Firmadas</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

            </div>
        </div>
    </div>

    {{-- OTROS --}}
    <div class="col-lg-6 mb-4">
        <div class="doc-repo-card">
            <div class="doc-repo-header" style="border-left:3px solid #6d28d9;">
                <span class="doc-repo-icon" style="background:#ede9fe;">
                    <i class="fas fa-boxes" style="color:#6d28d9;"></i>
                </span>
                <div>
                    <div class="doc-repo-title">Otros Activos</div>
                    <div class="doc-repo-sub">Mobiliario, equipos de oficina y enseres</div>
                </div>
            </div>
            <div class="doc-repo-body">

                <a href="{{ route('actas.index', ['category' => 'OTRO', 'type' => 'entrega']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#ede9fe;border:1.5px solid #c4b5fd;">
                        <i class="fas fa-hand-holding" style="color:#6d28d9;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Actas de Entrega</span>
                    <span class="doc-row-type" style="color:#6d28d9;">Entrega</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'OTRO', 'type' => 'devolucion']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#fef3c7;border:1.5px solid #fcd34d;">
                        <i class="fas fa-undo" style="color:#92400e;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Actas de Devolución</span>
                    <span class="doc-row-type" style="color:#92400e;">Devolución</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'OTRO', 'type' => 'prestamo']) }}" class="doc-row">
                    <span class="doc-row-dot" style="background:#e0f2fe;border:1.5px solid #7dd3fc;">
                        <i class="fas fa-handshake" style="color:#0369a1;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name">Cartas de Préstamo</span>
                    <span class="doc-row-type" style="color:#0369a1;">Préstamo</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

                <a href="{{ route('actas.index', ['category' => 'OTRO', 'filter' => 'signed']) }}" class="doc-row doc-row-archive">
                    <span class="doc-row-dot" style="background:#f1f5f9;border:1.5px solid #cbd5e1;">
                        <i class="fas fa-archive" style="color:#475569;font-size:.7rem;"></i>
                    </span>
                    <span class="doc-row-name" style="color:#64748b;">Archivo Otros</span>
                    <span class="doc-row-type" style="color:#94a3b8;">Firmadas</span>
                    <i class="fas fa-chevron-right doc-row-arrow"></i>
                </a>

            </div>
        </div>
    </div>

</div>

@stop

@section('css')
<style>

/* ── Quick access cards (top row) ──────────────────────────────── */
.doc-quick-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none !important;
    color: #1e293b !important;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    transition: box-shadow .15s ease, border-color .15s ease;
    height: 100%;
}
.doc-quick-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,.09);
    border-color: #94a3b8;
    text-decoration: none !important;
}
.doc-quick-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.05rem;
}
.doc-quick-body { flex: 1; }
.doc-quick-title {
    font-size: .88rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.doc-quick-sub {
    font-size: .73rem;
    color: #64748b;
    margin-top: 2px;
}
.doc-quick-arrow {
    font-size: .7rem;
    color: #cbd5e1;
    flex-shrink: 0;
    transition: color .12s, transform .12s;
}
.doc-quick-card:hover .doc-quick-arrow {
    color: #64748b;
    transform: translateX(3px);
}

/* ── Repository cards ──────────────────────────────────────────── */
.doc-repo-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.doc-repo-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #fafbfc;
    border-bottom: 1px solid #f1f5f9;
    padding-left: 16px;
}
.doc-repo-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .95rem;
}
.doc-repo-title {
    font-size: .88rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.doc-repo-sub {
    font-size: .71rem;
    color: #94a3b8;
    margin-top: 1px;
}

/* ── Document rows ─────────────────────────────────────────────── */
.doc-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 18px;
    border-bottom: 1px solid #f8fafc;
    text-decoration: none !important;
    color: #1e293b !important;
    transition: background .1s ease;
}
.doc-row:last-child { border-bottom: none; }
.doc-row:hover {
    background: #f8fafc;
    text-decoration: none !important;
}
.doc-row-archive { background: #fafbfc; }
.doc-row-archive:hover { background: #f1f5f9; }

.doc-row-dot {
    width: 30px;
    height: 30px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.doc-row-name {
    flex: 1;
    font-size: .84rem;
    font-weight: 600;
    color: #334155;
}
.doc-row-type {
    font-size: .71rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    white-space: nowrap;
}
.doc-row-arrow {
    font-size: .62rem;
    color: #cbd5e1;
    flex-shrink: 0;
    transition: color .1s, transform .1s;
}
.doc-row:hover .doc-row-arrow {
    color: #94a3b8;
    transform: translateX(2px);
}
</style>
@stop
