@extends('adminlte::page')

@section('title', 'Expediente - ' . $collaborator->full_name)

@section('css')
<style>
    :root{
        --corp-primary:#0f4c81;
        --corp-primary-soft:#e8f1fb;
        --corp-accent:#0ea5a4;
        --corp-border:#dbe3ee;
        --corp-muted:#667085;
        --corp-bg:#f6f8fb;
    }
    .exp-shell{background:var(--corp-bg);border:1px solid var(--corp-border);border-radius:.75rem;}
    .exp-hero{background:linear-gradient(135deg,#0f4c81 0%,#1d5f97 50%,#0e7490 100%);color:#fff;border-radius:.75rem .75rem 0 0;}
    .exp-hero .meta{opacity:.9;font-size:.85rem}
    .exp-tabs{border-bottom:1px solid var(--corp-border);background:#fff}
    .exp-tabs .nav-link{border:0;color:#344054;font-weight:600;padding:.85rem .95rem}
    .exp-tabs .nav-link.active{color:var(--corp-primary);border-bottom:3px solid var(--corp-primary);background:#f8fbff}
    .exp-tabs .nav-link.tab-ti.active{color:#0b4a8b;border-bottom-color:#0b4a8b;background:#eaf3ff}
    .exp-tabs .nav-link.tab-otro.active{color:#0f766e;border-bottom-color:#0f766e;background:#e7fbf7}
    .exp-tabs .nav-link.tab-loans-ti.active{color:#9a3412;border-bottom-color:#f59e0b;background:#fff7e8}
    .exp-tabs .nav-link.tab-loans-otro.active{color:#6d28d9;border-bottom-color:#7c3aed;background:#f3ecff}
    .exp-tabs .nav-link.tab-hist.active{color:#334155;border-bottom-color:#64748b;background:#f3f6fa}
    .toolbar{background:#fff;border-bottom:1px solid var(--corp-border)}
    .toolbar label{font-size:.78rem;color:#334155;margin-bottom:0}
    .tbl-wrap{background:#fff}
    .tbl-head th{background:#f1f5f9;color:#334155;font-size:.73rem;text-transform:uppercase;letter-spacing:.03em;border-bottom:1px solid var(--corp-border)}
    .badge-soft{background:var(--corp-primary-soft);color:var(--corp-primary);border:1px solid #c7dcf7}
    .detail-btn{border-radius:.45rem}
    .pane-ti{border-top:3px solid #0b4a8b}
    .pane-otro{border-top:3px solid #0f766e}
    .pane-loans-ti{border-top:3px solid #f59e0b}
    .pane-loans-otro{border-top:3px solid #7c3aed}
    .pane-hist{border-top:3px solid #64748b}
    .toolbar-ti{background:linear-gradient(180deg,#f0f6ff,#ffffff)}
    .toolbar-otro{background:linear-gradient(180deg,#ebfdf9,#ffffff)}
    .toolbar-loans-ti{background:linear-gradient(180deg,#fff8ed,#ffffff)}
    .toolbar-loans-otro{background:linear-gradient(180deg,#f5eeff,#ffffff)}
    .toolbar-hist{background:linear-gradient(180deg,#f6f8fb,#ffffff)}
</style>
@stop

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark" style="font-size:1.1rem;">
        <i class="fas fa-id-card text-primary mr-2"></i> Expediente de Colaborador
    </h1>
    <div>
        @can('collaborators.edit')
            <a href="{{ route('collaborators.edit', $collaborator) }}" class="btn btn-sm btn-warning mr-1">
                <i class="fas fa-edit mr-1"></i> Editar
            </a>
        @endcan
        <a href="{{ route('collaborators.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

@php
    $u = auth()->user();
    $isAdminOrGeneral = $u->hasAnyRole(['Admin', 'Gestor_General']);
    $isTi = $u->hasRole('Auxiliar_TI');
    $isOtros = $u->hasAnyRole(['Gestor_Activos', 'Auxiliar_Activos']);

    if ($isAdminOrGeneral) { $canTI = true; $canOTRO = true; }
    elseif ($isTi) { $canTI = true; $canOTRO = false; }
    elseif ($isOtros) { $canTI = false; $canOTRO = true; }
    else { $canTI = $u->can('tech.assets.view'); $canOTRO = $u->can('assets.view'); }

    $defaultTab = $canTI ? 'ti' : ($canOTRO ? 'otro' : 'hist');
    $historyTI = $assignmentHistory->where('asset_category', 'TI');
    $historyOTRO = $assignmentHistory->where('asset_category', 'OTRO');
    $visibleHistory = ($canTI && $canOTRO) ? $assignmentHistory : ($canTI ? $historyTI : $historyOTRO);

    $modalidad = match($collaborator->modalidad_trabajo ?? 'presencial') {
        'remoto' => 'Remoto',
        'hibrido' => 'Hibrido',
        default => 'Presencial',
    };

@endphp

<div class="exp-shell">
    <div class="exp-hero p-3">
        <div class="row">
            <div class="col-md-7">
                <h3 class="mb-1" style="font-size:1.2rem;">{{ $collaborator->full_name }}</h3>
                <div class="meta">{{ $collaborator->position ?? 'Sin cargo' }} | {{ $collaborator->area ?? 'Sin area' }}</div>
                <div class="meta mt-2">
                    Documento: {{ $collaborator->document }} |
                    Correo: {{ $collaborator->email ?? '-' }} |
                    Telefono: {{ $collaborator->phone ?? '-' }}
                </div>
            </div>
            <div class="col-md-5 text-md-right mt-2 mt-md-0">
                <span class="badge badge-light mr-1">Sede: {{ $collaborator->branch?->name ?? '-' }}</span>
                <span class="badge badge-light mr-1">Modalidad: {{ $modalidad }}</span>
                <span class="badge {{ $collaborator->active ? 'badge-success' : 'badge-secondary' }}">
                    {{ $collaborator->active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
    </div>

    <div class="exp-tabs">
        <ul class="nav" id="expTabs" role="tablist">
            @if($canTI)<li class="nav-item"><a class="nav-link tab-ti {{ $defaultTab==='ti'?'active':'' }}" data-toggle="tab" href="#pane-ti"><i class="fas fa-laptop mr-1"></i>Activos TI</a></li>@endif
            @if($canOTRO)<li class="nav-item"><a class="nav-link tab-otro {{ $defaultTab==='otro'?'active':'' }}" data-toggle="tab" href="#pane-otro"><i class="fas fa-boxes mr-1"></i>Otros Activos</a></li>@endif
            @if($canTI)<li class="nav-item"><a class="nav-link tab-loans-ti" data-toggle="tab" href="#pane-loans-ti"><i class="fas fa-handshake mr-1"></i>Prestamos TI</a></li>@endif
            @if($canOTRO)<li class="nav-item"><a class="nav-link tab-loans-otro" data-toggle="tab" href="#pane-loans-otro"><i class="fas fa-handshake mr-1"></i>Prestamos Otros</a></li>@endif
            <li class="nav-item"><a class="nav-link tab-hist {{ $defaultTab==='hist'?'active':'' }}" data-toggle="tab" href="#pane-hist"><i class="fas fa-history mr-1"></i>Historial</a></li>
        </ul>
    </div>

    <div class="tab-content">
        @if($canTI)
        <div class="tab-pane fade pane-ti {{ $defaultTab==='ti'?'show active':'' }}" id="pane-ti">
            <div class="toolbar toolbar-ti p-2" data-col-control="ti">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted font-weight-bold">Vista de columnas</small>
                    <button type="button" class="btn btn-xs btn-outline-primary" data-toggle-col-filter="ti">
                        <i class="fas fa-filter mr-1"></i> Filtrar columnas
                    </button>
                </div>
                <div class="mt-2 d-none" data-col-filter-panel="ti">
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="ti" data-preset="basic">Basica</button>
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="ti" data-preset="all">Completa</button>
                    <label class="ml-2"><input type="checkbox" data-col="code" checked> Codigo</label>
                    <label class="ml-2"><input type="checkbox" data-col="tag" checked> Tag</label>
                    <label class="ml-2"><input type="checkbox" data-col="fixed"> Codigo Fijo</label>
                    <label class="ml-2"><input type="checkbox" data-col="branch"> Sede</label>
                    <label class="ml-2"><input type="checkbox" data-col="type" checked> Tipo</label>
                    <label class="ml-2"><input type="checkbox" data-col="brand" checked> Marca</label>
                    <label class="ml-2"><input type="checkbox" data-col="model" checked> Modelo</label>
                    <label class="ml-2"><input type="checkbox" data-col="serial" checked> Serial</label>
                    <label class="ml-2"><input type="checkbox" data-col="status" checked> Estado</label>
                    <label class="ml-2"><input type="checkbox" data-col="detail" checked> Ver Activo</label>
                </div>
            </div>
            <div class="tbl-wrap table-responsive" data-col-table="ti">
                <table class="table table-sm table-hover mb-0">
                    <thead class="tbl-head"><tr>
                        <th class="pl-3 col-code">Codigo</th><th class="col-tag">Tag</th><th class="col-fixed">Codigo Fijo</th><th class="col-branch">Sede</th><th class="col-type">Tipo</th><th class="col-brand">Marca</th><th class="col-model">Modelo</th><th class="col-serial">Serial</th><th class="col-status">Estado</th><th class="col-detail text-center">Detalle</th>
                    </tr></thead>
                    <tbody>
                    @forelse($tiItems as $it)
                        @php $a = $it->asset; @endphp
                        <tr>
                            <td class="pl-3 col-code"><span class="badge badge-soft">{{ $a->internal_code ?? '-' }}</span></td>
                            <td class="col-tag">{{ $a->asset_tag ?? '-' }}</td>
                            <td class="col-fixed">{{ $a->fixed_asset_code ?? '-' }}</td>
                            <td class="col-branch">{{ $a->branch?->name ?? '-' }}</td>
                            <td class="col-type">{{ $a->type?->name ?? '-' }}</td>
                            <td class="col-brand">{{ $a->brand ?? '-' }}</td>
                            <td class="col-model">{{ $a->model ?? '-' }}</td>
                            <td class="col-serial">{{ $a->serial ?? '-' }}</td>
                            <td class="col-status"><span class="badge badge-light border">{{ $a->status?->name ?? '-' }}</span></td>
                            <td class="col-detail text-center"><a href="{{ url('/tech/assets/'.$a->id) }}" class="btn btn-xs btn-outline-primary detail-btn"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Sin activos TI asignados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($canOTRO)
        <div class="tab-pane fade pane-otro {{ $defaultTab==='otro'?'show active':'' }}" id="pane-otro">
            <div class="toolbar toolbar-otro p-2" data-col-control="otro">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted font-weight-bold">Vista de columnas</small>
                    <button type="button" class="btn btn-xs btn-outline-success" data-toggle-col-filter="otro">
                        <i class="fas fa-filter mr-1"></i> Filtrar columnas
                    </button>
                </div>
                <div class="mt-2 d-none" data-col-filter-panel="otro">
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="otro" data-preset="basic">Basica</button>
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="otro" data-preset="all">Completa</button>
                    <label class="ml-2"><input type="checkbox" data-col="code" checked> Codigo</label>
                    <label class="ml-2"><input type="checkbox" data-col="tag" checked> Tag</label>
                    <label class="ml-2"><input type="checkbox" data-col="type" checked> Tipo</label>
                    <label class="ml-2"><input type="checkbox" data-col="brand" checked> Marca</label>
                    <label class="ml-2"><input type="checkbox" data-col="model" checked> Modelo</label>
                    <label class="ml-2"><input type="checkbox" data-col="serial" checked> Serial</label>
                    <label class="ml-2"><input type="checkbox" data-col="status" checked> Estado</label>
                    <label class="ml-2"><input type="checkbox" data-col="detail" checked> Ver Activo</label>
                </div>
            </div>
            <div class="tbl-wrap table-responsive" data-col-table="otro">
                <table class="table table-sm table-hover mb-0">
                    <thead class="tbl-head"><tr><th class="pl-3 col-code">Codigo</th><th class="col-tag">Tag</th><th class="col-type">Tipo</th><th class="col-brand">Marca</th><th class="col-model">Modelo</th><th class="col-serial">Serial</th><th class="col-status">Estado</th><th class="col-detail text-center">Detalle</th></tr></thead>
                    <tbody>
                    @forelse($otroItems as $it)
                        @php $a = $it->asset; @endphp
                        <tr>
                            <td class="pl-3 col-code"><span class="badge badge-soft">{{ $a->internal_code ?? '-' }}</span></td>
                            <td class="col-tag">{{ $a->asset_tag ?? '-' }}</td>
                            <td class="col-type">{{ $a->type?->name ?? '-' }}</td>
                            <td class="col-brand">{{ $a->brand ?? '-' }}</td>
                            <td class="col-model">{{ $a->model ?? '-' }}</td>
                            <td class="col-serial">{{ $a->serial ?? '-' }}</td>
                            <td class="col-status"><span class="badge badge-light border">{{ $a->status?->name ?? '-' }}</span></td>
                            <td class="col-detail text-center"><a href="{{ url('/assets/'.$a->id) }}" class="btn btn-xs btn-outline-success detail-btn"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Sin otros activos asignados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($canTI)
        <div class="tab-pane fade pane-loans-ti" id="pane-loans-ti">
            <div class="toolbar toolbar-loans-ti p-2" data-col-control="loans-ti">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted font-weight-bold">Vista de columnas</small>
                    <button type="button" class="btn btn-xs btn-outline-warning" data-toggle-col-filter="loans-ti">
                        <i class="fas fa-filter mr-1"></i> Filtrar columnas
                    </button>
                </div>
                <div class="mt-2 d-none" data-col-filter-panel="loans-ti">
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="loans-ti" data-preset="basic">Basica</button>
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="loans-ti" data-preset="all">Completa</button>
                    <label class="ml-2"><input type="checkbox" data-col="code" checked> Codigo</label>
                    <label class="ml-2"><input type="checkbox" data-col="type" checked> Tipo</label>
                    <label class="ml-2"><input type="checkbox" data-col="brand_model" checked> Marca/Modelo</label>
                    <label class="ml-2"><input type="checkbox" data-col="start" checked> Inicio</label>
                    <label class="ml-2"><input type="checkbox" data-col="end" checked> Vence</label>
                    <label class="ml-2"><input type="checkbox" data-col="days" checked> Dias</label>
                    <label class="ml-2"><input type="checkbox" data-col="notes" checked> Notas</label>
                    <label class="ml-2"><input type="checkbox" data-col="detail" checked> Ver Activo</label>
                </div>
            </div>
            <div class="tbl-wrap table-responsive" data-col-table="loans-ti">
                <table class="table table-sm table-hover mb-0">
                    <thead class="tbl-head"><tr><th class="pl-3 col-code">Codigo</th><th class="col-type">Tipo</th><th class="col-brand_model">Marca / Modelo</th><th class="col-start">Inicio</th><th class="col-end">Vence</th><th class="col-days">Dias</th><th class="col-notes">Notas</th><th class="col-detail text-center">Detalle</th></tr></thead>
                    <tbody>
                    @forelse($activeTiLoans as $l)
                        @php $a = $l->asset; $days = $l->daysRemaining(); @endphp
                        <tr>
                            <td class="pl-3 col-code"><span class="badge badge-soft">{{ $a->internal_code ?? '-' }}</span></td>
                            <td class="col-type">{{ $a->type?->name ?? '-' }}</td>
                            <td class="col-brand_model">{{ ($a->brand ?? '-') . ' ' . ($a->model ?? '') }}</td>
                            <td class="col-start">{{ $l->start_date->format('d/m/Y') }}</td>
                            <td class="col-end">{{ $l->end_date->format('d/m/Y') }}</td>
                            <td class="col-days"><span class="badge {{ $days < 0 ? 'badge-danger' : ($days <= 2 ? 'badge-warning' : 'badge-success') }}">{{ $days }}</span></td>
                            <td class="col-notes">{{ Str::limit($l->notes, 60) ?? '-' }}</td>
                            <td class="col-detail text-center"><a href="{{ url('/tech/assets/'.$a->id) }}" class="btn btn-xs btn-outline-primary detail-btn"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Sin prestamos TI activos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @if($canOTRO)
        <div class="tab-pane fade pane-loans-otro" id="pane-loans-otro">
            <div class="toolbar toolbar-loans-otro p-2" data-col-control="loans-otro">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted font-weight-bold">Vista de columnas</small>
                    <button type="button" class="btn btn-xs btn-outline-secondary" style="border-color:#7c3aed;color:#7c3aed;" data-toggle-col-filter="loans-otro">
                        <i class="fas fa-filter mr-1"></i> Filtrar columnas
                    </button>
                </div>
                <div class="mt-2 d-none" data-col-filter-panel="loans-otro">
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="loans-otro" data-preset="basic">Basica</button>
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="loans-otro" data-preset="all">Completa</button>
                    <label class="ml-2"><input type="checkbox" data-col="code" checked> Codigo</label>
                    <label class="ml-2"><input type="checkbox" data-col="type" checked> Tipo</label>
                    <label class="ml-2"><input type="checkbox" data-col="brand_model" checked> Marca/Modelo</label>
                    <label class="ml-2"><input type="checkbox" data-col="start" checked> Inicio</label>
                    <label class="ml-2"><input type="checkbox" data-col="end" checked> Vence</label>
                    <label class="ml-2"><input type="checkbox" data-col="days" checked> Dias</label>
                    <label class="ml-2"><input type="checkbox" data-col="notes" checked> Notas</label>
                    <label class="ml-2"><input type="checkbox" data-col="detail" checked> Ver Activo</label>
                </div>
            </div>
            <div class="tbl-wrap table-responsive" data-col-table="loans-otro">
                <table class="table table-sm table-hover mb-0">
                    <thead class="tbl-head"><tr><th class="pl-3 col-code">Codigo</th><th class="col-type">Tipo</th><th class="col-brand_model">Marca / Modelo</th><th class="col-start">Inicio</th><th class="col-end">Vence</th><th class="col-days">Dias</th><th class="col-notes">Notas</th><th class="col-detail text-center">Detalle</th></tr></thead>
                    <tbody>
                    @forelse($activeOtroLoans as $l)
                        @php $a = $l->asset; $days = $l->daysRemaining(); @endphp
                        <tr>
                            <td class="pl-3 col-code"><span class="badge badge-soft">{{ $a->internal_code ?? '-' }}</span></td>
                            <td class="col-type">{{ $a->type?->name ?? '-' }}</td>
                            <td class="col-brand_model">{{ ($a->brand ?? '-') . ' ' . ($a->model ?? '') }}</td>
                            <td class="col-start">{{ $l->start_date->format('d/m/Y') }}</td>
                            <td class="col-end">{{ $l->end_date->format('d/m/Y') }}</td>
                            <td class="col-days"><span class="badge {{ $days < 0 ? 'badge-danger' : ($days <= 2 ? 'badge-warning' : 'badge-success') }}">{{ $days }}</span></td>
                            <td class="col-notes">{{ Str::limit($l->notes, 60) ?? '-' }}</td>
                            <td class="col-detail text-center"><a href="{{ url('/assets/'.$a->id) }}" class="btn btn-xs btn-outline-success detail-btn"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Sin prestamos de otros activos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="tab-pane fade pane-hist {{ $defaultTab==='hist'?'show active':'' }}" id="pane-hist">
            <div class="toolbar toolbar-hist p-2" data-col-control="hist">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted font-weight-bold">Vista de columnas</small>
                    <button type="button" class="btn btn-xs btn-outline-dark" data-toggle-col-filter="hist">
                        <i class="fas fa-filter mr-1"></i> Filtrar columnas
                    </button>
                </div>
                <div class="mt-2 d-none" data-col-filter-panel="hist">
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="hist" data-preset="basic">Basica</button>
                    <button class="btn btn-xs btn-outline-secondary" data-col-preset="hist" data-preset="all">Completa</button>
                    <label class="ml-2"><input type="checkbox" data-col="id" checked> ID</label>
                    <label class="ml-2"><input type="checkbox" data-col="cat" checked> Categoria</label>
                    <label class="ml-2"><input type="checkbox" data-col="date" checked> Entrega</label>
                    <label class="ml-2"><input type="checkbox" data-col="status" checked> Estado</label>
                    <label class="ml-2"><input type="checkbox" data-col="user" checked> Registrado por</label>
                    <label class="ml-2"><input type="checkbox" data-col="detail" checked> Detalle</label>
                </div>
            </div>
            <div class="tbl-wrap table-responsive" data-col-table="hist">
                <table class="table table-sm table-hover mb-0">
                    <thead class="tbl-head"><tr><th class="pl-3 col-id">ID</th><th class="col-cat">Categoria</th><th class="col-date">Entrega</th><th class="col-status">Estado</th><th class="col-user">Registrado por</th><th class="col-detail text-center">Detalle</th></tr></thead>
                    <tbody>
                    @forelse($visibleHistory as $a)
                        <tr>
                            <td class="pl-3 col-id">#{{ $a->id }}</td>
                            <td class="col-cat"><span class="badge {{ $a->asset_category==='TI'?'badge-primary':'badge-info' }}">{{ $a->asset_category }}</span></td>
                            <td class="col-date">{{ $a->assignment_date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="col-status"><span class="badge {{ $a->status === 'activa' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($a->status) }}</span></td>
                            <td class="col-user">{{ $a->assignedBy?->name ?? '-' }}</td>
                            <td class="col-detail text-center">
                                @if($a->asset_category==='TI')
                                    <a href="{{ route('tech.assignments.show',$a) }}" class="btn btn-xs btn-info detail-btn"><i class="fas fa-eye"></i></a>
                                @else
                                    <a href="{{ route('assets.assignments.show',$a) }}" class="btn btn-xs btn-info detail-btn"><i class="fas fa-eye"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin historial de asignaciones.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
const tabKey='collab_tab_{{ $collaborator->id }}';
const prevTab=sessionStorage.getItem(tabKey);
if(prevTab){const t=document.querySelector(`a[href="${prevTab}"]`);if(t)$(t).tab('show');}
document.querySelectorAll('#expTabs a[data-toggle="tab"]').forEach(a=>a.addEventListener('shown.bs.tab',e=>sessionStorage.setItem(tabKey,e.target.getAttribute('href'))));

function applyCols(scope){
    const c=document.querySelector(`[data-col-control="${scope}"]`);
    const t=document.querySelector(`[data-col-table="${scope}"]`);
    if(!c||!t)return;
    c.querySelectorAll('input[data-col]').forEach(i=>t.querySelectorAll(`.col-${i.dataset.col}`).forEach(el=>el.style.display=i.checked?'':'none'));
}
function saveCols(scope){
    const c=document.querySelector(`[data-col-control="${scope}"]`); if(!c)return;
    const state={}; c.querySelectorAll('input[data-col]').forEach(i=>state[i.dataset.col]=i.checked);
    localStorage.setItem(`collab_cols_${scope}`, JSON.stringify(state));
}
function loadCols(scope){
    const c=document.querySelector(`[data-col-control="${scope}"]`); if(!c)return;
    const raw=localStorage.getItem(`collab_cols_${scope}`); if(!raw)return;
    try{const state=JSON.parse(raw); c.querySelectorAll('input[data-col]').forEach(i=>{if(state[i.dataset.col]!==undefined)i.checked=!!state[i.dataset.col];});}catch(e){}
}
function preset(scope,name){
    const c=document.querySelector(`[data-col-control="${scope}"]`); if(!c)return;
    const basicMap={
        'ti':['code','tag','type','brand','model','serial','status','detail'],
        'otro':['code','tag','type','brand','model','serial','status','detail'],
        'loans-ti':['code','type','brand_model','start','end','days','notes','detail'],
        'loans-otro':['code','type','brand_model','start','end','days','notes','detail'],
        'hist':['id','cat','date','status','user','detail']
    };
    const basic=basicMap[scope] || [];
    c.querySelectorAll('input[data-col]').forEach(i=>i.checked=(name==='all')?true:basic.includes(i.dataset.col));
    saveCols(scope); applyCols(scope);
}
['ti','otro','loans-ti','loans-otro','hist'].forEach(scope=>{
    const c=document.querySelector(`[data-col-control="${scope}"]`); if(!c)return;
    loadCols(scope); applyCols(scope);
    c.querySelectorAll('input[data-col]').forEach(i=>i.addEventListener('change',()=>{saveCols(scope);applyCols(scope);}));
});
document.querySelectorAll('[data-col-preset]').forEach(b=>b.addEventListener('click',()=>preset(b.dataset.colPreset,b.dataset.preset)));

document.querySelectorAll('[data-toggle-col-filter]').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const scope = btn.dataset.toggleColFilter;
        const panel = document.querySelector(`[data-col-filter-panel="${scope}"]`);
        if(panel) panel.classList.toggle('d-none');
    });
});

setTimeout(()=>document.querySelectorAll('.alert.show').forEach(el=>el.classList.remove('show')),4000);
</script>
@stop
