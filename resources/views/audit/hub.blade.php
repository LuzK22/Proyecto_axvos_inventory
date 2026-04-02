@php
$auditSections = [
    'ti'            => ['label'=>'Activos TI',         'icon'=>'fa-laptop',      'color'=>'#1d4ed8','bg'=>'#dbeafe','sub'=>'Inventario de equipos TI'],
    'otros'         => ['label'=>'Otros Activos',      'icon'=>'fa-boxes',       'color'=>'#6d28d9','bg'=>'#ede9fe','sub'=>'Mobiliario y enseres'],
    'asignaciones'  => ['label'=>'Asignaciones',       'icon'=>'fa-user-check',  'color'=>'#15803d','bg'=>'#dcfce7','sub'=>'Entrega de activos a colaboradores'],
    'prestamos'     => ['label'=>'Préstamos',          'icon'=>'fa-handshake',   'color'=>'#92400e','bg'=>'#fef3c7','sub'=>'Préstamos temporales TI + Otros'],
    'log'           => ['label'=>'Log de Movimientos', 'icon'=>'fa-history',     'color'=>'#475569','bg'=>'#f1f5f9','sub'=>'Historial de entregas y devoluciones'],
    'bajas'         => ['label'=>'Bajas',              'icon'=>'fa-archive',     'color'=>'#b91c1c','bg'=>'#fee2e2','sub'=>'Activos dados de baja, donados o vendidos'],
    'exportaciones' => ['label'=>'Exportaciones',      'icon'=>'fa-download',    'color'=>'#166534','bg'=>'#f0fdf4','sub'=>'Registro de descargas de datos'],
    'sesiones'      => ['label'=>'Sesiones',           'icon'=>'fa-user-clock',  'color'=>'#334155','bg'=>'#f1f5f9','sub'=>'Accesos y sesiones de usuarios'],
    'actividad'     => ['label'=>'Actividad',          'icon'=>'fa-shield-alt',  'color'=>'#4338ca','bg'=>'#e0e7ff','sub'=>'Log de acciones ISO 27001'],
];
$auditCfg = $auditSections[$tab] ?? $auditSections['ti'];
@endphp
@extends('adminlte::page')
@section('title', 'Auditoría — ' . $auditCfg['label'])

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center" style="gap:14px;">
        <a href="{{ route('audit.hub') }}" class="btn btn-sm btn-outline-secondary" style="white-space:nowrap;">
            <i class="fas fa-th-large mr-1"></i> Hub
        </a>
        <div class="d-flex align-items-center" style="gap:10px;">
            <span class="audit-hdr-icon" style="background:{{ $auditCfg['bg'] }};">
                <i class="fas {{ $auditCfg['icon'] }}" style="color:{{ $auditCfg['color'] }};"></i>
            </span>
            <div>
                <h1 class="m-0 font-weight-bold" style="font-size:1.25rem;color:#1e293b;letter-spacing:-.01em;">
                    {{ $auditCfg['label'] }}
                </h1>
                <small class="text-muted">{{ $auditCfg['sub'] }}</small>
            </div>
        </div>
    </div>
    @can('audit.export')
    <div class="d-flex align-items-center" style="gap:6px;">
        <form id="exportForm" method="GET" action="{{ route('audit.export') }}" style="display:inline;">
            @foreach(request()->except(['_token']) as $k => $v)
                @if(is_array($v))
                    @foreach($v as $vi)<input type="hidden" name="{{ $k }}[]" value="{{ $vi }}">@endforeach
                @else
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endif
            @endforeach
            <input type="hidden" name="tab" value="{{ $tab }}">
            {{-- cols[] hidden inputs injected by JS when column filter is active --}}
            <div id="exportColInputs"></div>
            <button type="submit" id="exportBtn" class="btn btn-sm btn-success">
                <i class="fas fa-file-csv mr-1"></i> Exportar CSV
            </button>
        </form>
        @if(in_array($tab, ['ti','otros']))
        <a href="{{ $tab === 'ti' ? route('tech.reports.niif-export', request()->only(['branch_id','status_id','type_id','property_type','from','to'])) : route('assets.reports.niif-export', request()->only(['branch_id','status_id','type_id','property_type','from','to'])) }}"
           class="btn btn-sm btn-outline-secondary" title="Exportar NIIF NIC 16">
            <i class="fas fa-calculator mr-1"></i> NIIF
        </a>
        @endif
    </div>
    @endcan
</div>
@stop

@section('content')

<div class="card shadow-sm" style="border-top:3px solid {{ $auditCfg['color'] }};border-radius:8px;">

    {{-- ── Toggle categoría: Préstamos y Bajas ──────────────────────── --}}
    @if(in_array($tab, ['prestamos', 'bajas', 'log']))
    @php
        $catParam  = $tab === 'prestamos' ? 'loan_category' : ($tab === 'log' ? 'log_category' : 'category');
        $catActual = request($catParam, '');
    @endphp
    <div class="px-3 pt-3 pb-2 border-bottom d-flex align-items-center" style="gap:8px;">
        <span class="text-muted small font-weight-bold mr-1" style="white-space:nowrap;">Categoría:</span>
        @foreach(['' => 'TI + Otros', 'TI' => 'Activos TI', 'OTRO' => 'Otros Activos'] as $val => $lbl)
        @php
            $isActive = $catActual === $val;
            $style = $isActive
                ? ($val === 'TI'   ? 'background:#dbeafe;color:#1d4ed8;border-color:#1d4ed8;font-weight:700;'
                  : ($val === 'OTRO' ? 'background:#ede9fe;color:#6d28d9;border-color:#6d28d9;font-weight:700;'
                  : 'background:#f1f5f9;color:#334155;border-color:#334155;font-weight:700;'))
                : 'background:#fff;color:#64748b;border-color:#cbd5e1;';
        @endphp
        <a href="{{ route('audit.hub', array_merge(request()->except(['page', $catParam]), ['tab' => $tab, $catParam => $val])) }}"
           class="btn btn-sm" style="border:1.5px solid;border-radius:20px;font-size:.78rem;padding:3px 14px;{{ $style }}">
            @if($val === 'TI') <i class="fas fa-laptop mr-1"></i>
            @elseif($val === 'OTRO') <i class="fas fa-boxes mr-1"></i>
            @else <i class="fas fa-layer-group mr-1"></i>
            @endif
            {{ $lbl }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── Filtros ──────────────────────────────────────────────────────── --}}
    <div class="card-body py-2 border-bottom bg-light">
        <form method="GET" class="form-inline flex-wrap" style="gap:6px;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            @if($tab === 'prestamos')
                <input type="hidden" name="loan_category" value="{{ request('loan_category') }}">
            @endif
            @if($tab === 'bajas')
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            @if($tab === 'log')
                <input type="hidden" name="log_category" value="{{ request('log_category') }}">
            @endif

            @if(in_array($tab, ['ti', 'otros']))
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Buscar código, marca, serial..."
                       style="min-width:200px;">
                @if($tab === 'ti')
                <select name="type_id" class="form-control form-control-sm">
                    <option value="">Todos los tipos TI</option>
                    @foreach($tiTypes as $t)
                        <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                @else
                <select name="type_id" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    @foreach($otroTypes as $t)
                        <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
                @endif
                <select name="status_id" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                <select name="property_type" class="form-control form-control-sm">
                    <option value="">Todos</option>
                    <option value="PROPIO" {{ request('property_type') === 'PROPIO' ? 'selected' : '' }}>Propio</option>
                    <option value="LEASING" {{ request('property_type') === 'LEASING' ? 'selected' : '' }}>Leasing</option>
                    <option value="ALQUILADO" {{ request('property_type') === 'ALQUILADO' ? 'selected' : '' }}>Alquilado</option>
                </select>
            @endif

            @if($tab === 'prestamos')
                <input type="text" name="collaborator" value="{{ request('collaborator') }}"
                       class="form-control form-control-sm" placeholder="Colaborador / Cédula">
                <select name="loan_status" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    <option value="activo" {{ request('loan_status') === 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="devuelto" {{ request('loan_status') === 'devuelto' ? 'selected' : '' }}>Devuelto</option>
                    <option value="vencido" {{ request('loan_status') === 'vencido' ? 'selected' : '' }}>Vencido</option>
                </select>
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif

            @if($tab === 'asignaciones')
                <input type="text" name="collaborator" value="{{ request('collaborator') }}"
                       class="form-control form-control-sm" placeholder="Colaborador / Cédula">
                <select name="assign_status" class="form-control form-control-sm">
                    <option value="">Todas</option>
                    <option value="activa" {{ request('assign_status') === 'activa' ? 'selected' : '' }}>Activas</option>
                    <option value="devuelta" {{ request('assign_status') === 'devuelta' ? 'selected' : '' }}>Devueltas</option>
                </select>
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif

            @if(in_array($tab, ['bajas', 'sesiones', 'exportaciones']))
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Buscar..." style="min-width:180px;">
                @if($tab === 'bajas')
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @endif
            @endif

            @if($tab === 'log')
                <input type="text" name="collaborator" value="{{ request('collaborator') }}"
                       class="form-control form-control-sm" placeholder="Colaborador">
                <select name="action" class="form-control form-control-sm">
                    <option value="">Todas las acciones</option>
                    <option value="asignado" {{ request('action') === 'asignado' ? 'selected' : '' }}>Asignados</option>
                    <option value="devuelto" {{ request('action') === 'devuelto' ? 'selected' : '' }}>Devueltos</option>
                </select>
                <select name="branch_id" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            @endif

            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" title="Desde">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" title="Hasta">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Filtrar</button>
            <a href="{{ route('audit.hub', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </form>
    </div>

    {{-- Resultados --}}
    <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background:{{ $auditCfg['bg'] }}40;">
        <span class="font-weight-bold" style="color:{{ $auditCfg['color'] }};font-size:.82rem;">
            <i class="fas {{ $auditCfg['icon'] }} mr-1"></i>{{ $auditCfg['label'] }}
        </span>
        <div class="d-flex align-items-center" style="gap:10px;">
            <small class="text-muted">{{ $data->total() }} registro(s) — pág. {{ $data->currentPage() }}/{{ $data->lastPage() }}</small>
            @if(in_array($tab, ['ti','otros','prestamos','asignaciones','log','bajas']))
            <div class="dropdown" id="colSelectorWrap">
                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                        id="colSelectorBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        style="font-size:.74rem;padding:2px 10px;">
                    <i class="fas fa-columns mr-1"></i> Columnas
                </button>
                <div class="dropdown-menu dropdown-menu-right p-0" id="colDropdownMenu"
                     style="min-width:210px;max-height:320px;overflow-y:auto;"
                     aria-labelledby="colSelectorBtn">
                    {{-- populated by JS --}}
                </div>
            </div>
            @endif
        </div>
    </div>
    <div class="card-body p-0">

        {{-- Tab: TI / Otros --}}
        @if(in_array($tab, ['ti', 'otros']))
        @php $sub = ($tab === 'otros'); @endphp
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="auditTable">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th data-col="0" class="pl-3">Código</th>
                        <th data-col="1">Tipo</th>
                        @if($sub)<th data-col="2">Subcategoría</th>@endif
                        <th data-col="{{ $sub ? 3 : 2 }}">Marca</th>
                        <th data-col="{{ $sub ? 4 : 3 }}">Modelo</th>
                        <th data-col="{{ $sub ? 5 : 4 }}">Serial</th>
                        <th data-col="{{ $sub ? 6 : 5 }}">Asset Tag</th>
                        <th data-col="{{ $sub ? 7 : 6 }}">Estado</th>
                        <th data-col="{{ $sub ? 8 : 7 }}">Sucursal</th>
                        <th data-col="{{ $sub ? 9 : 8 }}">Propiedad</th>
                        <th data-col="{{ $sub ? 10 : 9 }}">Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asset)
                    <tr>
                        <td data-col="0" class="pl-3"><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                        <td data-col="1"><small>{{ $asset->type?->name ?? '—' }}</small></td>
                        @if($sub)
                        <td data-col="2">
                            @if($asset->type?->subcategory)
                                <span class="badge badge-light border" style="font-size:.65rem;">{{ $asset->type->subcategory }}</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td data-col="{{ $sub ? 3 : 2 }}"><small>{{ $asset->brand ?? '—' }}</small></td>
                        <td data-col="{{ $sub ? 4 : 3 }}"><small>{{ $asset->model ?? '—' }}</small></td>
                        <td data-col="{{ $sub ? 5 : 4 }}"><small class="text-muted">{{ $asset->serial ?? '—' }}</small></td>
                        <td data-col="{{ $sub ? 6 : 5 }}"><small class="text-muted">{{ $asset->asset_tag ?? '—' }}</small></td>
                        <td data-col="{{ $sub ? 7 : 6 }}">
                            @if($asset->status)
                                <span class="badge badge-pill" style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;font-size:.65rem;">
                                    {{ $asset->status->name }}
                                </span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-col="{{ $sub ? 8 : 7 }}"><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                        <td data-col="{{ $sub ? 9 : 8 }}"><span class="badge badge-{{ $asset->property_type === 'PROPIO' ? 'success' : 'info' }}" style="font-size:.62rem;">{{ $asset->property_type }}</span></td>
                        <td data-col="{{ $sub ? 10 : 9 }}"><small class="text-muted">{{ $asset->created_at?->format('d/m/Y') }}</small></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $sub ? 11 : 10 }}" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2" style="opacity:.2;"></i>
                            No hay registros con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Préstamos --}}
        @if($tab === 'prestamos')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="auditTable">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th data-col="0" class="pl-3">#</th>
                        <th data-col="1">Cat.</th>
                        <th data-col="2">Activo</th>
                        <th data-col="3">Tipo</th>
                        <th data-col="4">Colaborador / Destino</th>
                        <th data-col="5">Sucursal</th>
                        <th data-col="6">Inicio</th>
                        <th data-col="7">Vence</th>
                        <th data-col="8">Devuelto</th>
                        <th data-col="9">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $loan)
                    @php $cat = $loan->asset?->type?->category ?? ''; @endphp
                    <tr class="{{ $loan->status === 'vencido' ? 'table-warning' : '' }}">
                        <td data-col="0" class="pl-3"><small>{{ $loan->id }}</small></td>
                        <td data-col="1">
                            @if($cat === 'TI')
                                <span class="badge badge-primary" style="font-size:.6rem;">TI</span>
                            @elseif($cat === 'OTRO')
                                <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:.6rem;border:1px solid #6d28d9;">OTRO</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-col="2"><code style="font-size:.75rem;">{{ $loan->asset?->internal_code ?? '—' }}</code></td>
                        <td data-col="3"><small>{{ $loan->asset?->type?->name ?? '—' }}</small></td>
                        <td data-col="4">
                            @if($loan->destination_type === 'branch')
                                <small><i class="fas fa-building mr-1 text-muted"></i>{{ $loan->destinationBranch?->name ?? '—' }}</small>
                            @else
                                <small>{{ $loan->collaborator?->full_name ?? '—' }}</small>
                            @endif
                        </td>
                        <td data-col="5"><small>{{ $loan->collaborator?->branch?->name ?? ($loan->destinationBranch?->name ?? '—') }}</small></td>
                        <td data-col="6"><small>{{ $loan->start_date?->format('d/m/Y') }}</small></td>
                        <td data-col="7"><small class="{{ $loan->status === 'vencido' ? 'text-danger font-weight-bold' : '' }}">{{ $loan->end_date?->format('d/m/Y') }}</small></td>
                        <td data-col="8"><small>{{ $loan->returned_at?->format('d/m/Y') ?? '—' }}</small></td>
                        <td data-col="9">
                            @php $lc = match($loan->status) { 'activo' => 'success', 'devuelto' => 'secondary', 'vencido' => 'danger', default => 'light' }; @endphp
                            <span class="badge badge-{{ $lc }}" style="font-size:.65rem;">{{ ucfirst($loan->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No hay préstamos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Asignaciones --}}
        @if($tab === 'asignaciones')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="auditTable">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th data-col="0" class="pl-3">#</th>
                        <th data-col="1">Colaborador</th>
                        <th data-col="2">Sucursal</th>
                        <th data-col="3">Activos</th>
                        <th data-col="4">Fecha</th>
                        <th data-col="5">Estado</th>
                        <th data-col="6">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asn)
                    <tr>
                        <td data-col="0" class="pl-3"><small>{{ $asn->id }}</small></td>
                        <td data-col="1"><small class="font-weight-bold">{{ $asn->collaborator?->full_name ?? '—' }}</small></td>
                        <td data-col="2"><small>{{ $asn->collaborator?->branch?->name ?? '—' }}</small></td>
                        <td data-col="3">
                            <span class="badge badge-secondary" style="font-size:.65rem;">
                                {{ $asn->assignmentAssets->count() }} activo(s)
                            </span>
                        </td>
                        <td data-col="4"><small>{{ $asn->assignment_date?->format('d/m/Y') }}</small></td>
                        <td data-col="5">
                            <span class="badge badge-{{ $asn->status === 'activa' ? 'success' : 'secondary' }}" style="font-size:.65rem;">
                                {{ ucfirst($asn->status) }}
                            </span>
                        </td>
                        <td data-col="6"><small class="text-muted">{{ $asn->assignedBy?->name ?? '—' }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay asignaciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Log de Movimientos --}}
        @if($tab === 'log')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="auditTable">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th data-col="0" class="pl-3">Activo</th>
                        <th data-col="1">Cat.</th>
                        <th data-col="2">Colaborador</th>
                        <th data-col="3">Sucursal</th>
                        <th data-col="4">Fecha Asig.</th>
                        <th data-col="5">Fecha Dev.</th>
                        <th data-col="6">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $aa)
                    @php $logCat = $aa->asset?->type?->category ?? ''; @endphp
                    <tr>
                        <td data-col="0" class="pl-3"><code style="font-size:.75rem;">{{ $aa->asset?->internal_code ?? '—' }}</code></td>
                        <td data-col="1">
                            @if($logCat === 'TI')
                                <span class="badge badge-primary" style="font-size:.6rem;">TI</span>
                            @elseif($logCat === 'OTRO')
                                <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:.6rem;border:1px solid #6d28d9;">OTRO</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-col="2"><small>{{ $aa->assignment?->collaborator?->full_name ?? '—' }}</small></td>
                        <td data-col="3"><small>{{ $aa->assignment?->collaborator?->branch?->name ?? '—' }}</small></td>
                        <td data-col="4"><small>{{ $aa->assigned_at?->format('d/m/Y H:i') ?? $aa->created_at?->format('d/m/Y') }}</small></td>
                        <td data-col="5"><small>{{ $aa->returned_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                        <td data-col="6">
                            @if($aa->returned_at)
                                <span class="badge badge-secondary" style="font-size:.65rem;"><i class="fas fa-undo mr-1"></i>Devuelto</span>
                            @else
                                <span class="badge badge-success" style="font-size:.65rem;"><i class="fas fa-check mr-1"></i>Asignado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Bajas --}}
        @if($tab === 'bajas')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="auditTable">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th data-col="0" class="pl-3">Cód. Inventario</th>
                        <th data-col="1">Cód. Activo Fijo</th>
                        <th data-col="2">Categoría</th>
                        <th data-col="3">Tipo</th>
                        <th data-col="4">Marca / Modelo</th>
                        <th data-col="5">Motivo Baja</th>
                        <th data-col="6">Valor Compra</th>
                        <th data-col="7">Valor en Libros</th>
                        <th data-col="8">Cuenta PUC</th>
                        <th data-col="9">Sucursal</th>
                        <th data-col="10">Fecha Baja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asset)
                    <tr>
                        <td data-col="0" class="pl-3"><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                        <td data-col="1"><small class="text-muted">{{ $asset->fixed_asset_code ?? '—' }}</small></td>
                        <td data-col="2">
                            @if($asset->type?->category === 'TI')
                                <span class="badge badge-primary" style="font-size:.6rem;">TI</span>
                            @elseif($asset->type?->category === 'OTRO')
                                <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:.6rem;border:1px solid #6d28d9;">OTRO</span>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-col="3"><small>{{ $asset->type?->name ?? '—' }}</small></td>
                        <td data-col="4"><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                        <td data-col="5">
                            <span class="badge badge-danger" style="font-size:.65rem;">
                                {{ $asset->status?->name ?? '—' }}
                            </span>
                        </td>
                        <td data-col="6"><small>{{ $asset->purchase_value ? '$'.number_format($asset->purchase_value,0,',','.') : '—' }}</small></td>
                        <td data-col="7"><small class="text-warning">{{ $asset->current_book_value ? '$'.number_format($asset->current_book_value,0,',','.') : '—' }}</small></td>
                        <td data-col="8"><small class="text-muted">{{ $asset->account_code ?? '—' }}</small></td>
                        <td data-col="9"><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                        <td data-col="10"><small class="text-muted">{{ $asset->updated_at?->format('d/m/Y') }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center text-muted py-4">No hay activos dados de baja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Exportaciones --}}
        @if($tab === 'exportaciones')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Usuario</th>
                        <th>Módulo exportado</th>
                        <th>Formato</th>
                        <th>Filas</th>
                        <th>IP</th>
                        <th>Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $exp)
                    <tr>
                        <td class="pl-3">
                            <small class="font-weight-bold">{{ $exp->user?->name ?? 'Sistema' }}</small>
                            @if($exp->user?->email)
                                <br><small class="text-muted" style="font-size:.7rem;">{{ $exp->user->email }}</small>
                            @endif
                        </td>
                        <td><span class="badge badge-light border" style="font-size:.65rem;">{{ $exp->entity_type }}</span></td>
                        <td><span class="badge badge-success" style="font-size:.65rem;">{{ strtoupper($exp->format) }}</span></td>
                        <td><small>{{ number_format($exp->rows_exported) }}</small></td>
                        <td><small class="text-muted">{{ $exp->ip_address ?? '—' }}</small></td>
                        <td>
                            <small>{{ $exp->created_at?->format('d/m/Y') }}</small>
                            <br><small class="text-muted" style="font-size:.7rem;">{{ $exp->created_at?->format('H:i') }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay exportaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Sesiones --}}
        @if($tab === 'sesiones')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Usuario</th>
                        <th>Rol</th>
                        <th>Sucursal</th>
                        <th>IP</th>
                        <th>Dispositivo</th>
                        <th>Inicio Sesión</th>
                        <th>Última Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $s)
                    <tr>
                        <td class="pl-3">
                            <small class="font-weight-bold">{{ $s->user?->name ?? 'Desconocido' }}</small>
                            @if($s->user?->email)
                                <br><small class="text-muted" style="font-size:.7rem;">{{ $s->user->email }}</small>
                            @endif
                        </td>
                        <td>
                            @foreach($s->user?->roles ?? [] as $role)
                                <span class="badge badge-secondary" style="font-size:.62rem;">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td><small>{{ $s->user?->branch?->name ?? '—' }}</small></td>
                        <td><small class="text-monospace">{{ $s->ip_address ?? '—' }}</small></td>
                        <td><small>{{ $s->deviceName() }}</small></td>
                        <td>
                            <small>{{ $s->created_at?->format('d/m/Y') }}</small>
                            <br><small class="text-muted" style="font-size:.7rem;">{{ $s->created_at?->format('H:i') }}</small>
                        </td>
                        <td>
                            <small>{{ $s->last_active_at?->format('d/m/Y') }}</small>
                            <br><small class="text-muted" style="font-size:.7rem;">{{ $s->last_active_at?->format('H:i') }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay sesiones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Actividad del Sistema --}}
        @if($tab === 'actividad')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Usuario</th>
                        <th>Descripción</th>
                        <th>Módulo</th>
                        <th>Objeto</th>
                        <th>Detalles</th>
                        <th>Fecha y Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $activity)
                    <tr>
                        <td class="pl-3">
                            <small class="font-weight-bold">{{ $activity->causer?->name ?? 'Sistema' }}</small>
                            @if($activity->causer?->email)
                                <br><small class="text-muted" style="font-size:.7rem;">{{ $activity->causer->email }}</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $activity->description }}</small>
                        </td>
                        <td>
                            <span class="badge badge-light border" style="font-size:.65rem;">
                                {{ $activity->log_name ?? 'default' }}
                            </span>
                        </td>
                        <td>
                            @if($activity->subject_type)
                                <small class="text-muted">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td style="max-width:220px;">
                            @php
                                $props = collect($activity->properties)->except(['old','attributes'])->filter();
                            @endphp
                            @if($props->isNotEmpty())
                                <small class="text-muted" style="font-size:.7rem;word-break:break-word;">
                                    {{ $props->map(fn($v,$k) => "$k: $v")->implode(' | ') }}
                                </small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td>
                            <small>{{ $activity->created_at?->format('d/m/Y') }}</small>
                            <br><small class="text-muted" style="font-size:.7rem;">{{ $activity->created_at?->format('H:i:s') }}</small>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay registros de actividad.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

    </div>
    @if($data->hasPages())
        <div class="card-footer">{{ $data->links() }}</div>
    @endif
</div>
@stop

@section('css')
<style>
/* ── Header icon ─────────────────────────────────────── */
.audit-hdr-icon {
    width: 40px;
    height: 40px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}

/* ── Column selector dropdown ───────────────────────── */
#colDropdownMenu .col-check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    cursor: pointer;
    font-size: .8rem;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    user-select: none;
    transition: background .1s;
}
#colDropdownMenu .col-check-item:last-child { border-bottom: none; }
#colDropdownMenu .col-check-item:hover { background: #f8fafc; }
#colDropdownMenu .col-check-item input[type=checkbox] {
    width: 15px; height: 15px; cursor: pointer; flex-shrink: 0;
}
#colDropdownMenu .col-selector-header {
    padding: 6px 14px 4px;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94a3b8;
    background: #fafbfc;
    border-bottom: 1px solid #e2e8f0;
}
#colDropdownMenu .col-selector-footer {
    padding: 5px 10px;
    background: #fafbfc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 6px;
}
</style>
@stop

@section('js')
<script>
$(function () {

    /* ================================================================
     | Column selector config per tab
     | labels   : display names shown in checkboxes (= VIEW columns)
     | expMap   : view-col-index → [export col indices]. [] = view-only
     | alwaysExp: export col indices always included (not shown in UI)
     ================================================================ */
    var AUDIT_COLS = {
        ti: {
            labels: ['Código','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Ingreso'],
            expMap: [[0],[1],[2],[3],[4],[5],[6],[7],[8],[9]],
            alwaysExp: []
        },
        otros: {
            labels: ['Código','Tipo','Subcategoría','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Ingreso'],
            expMap: [[0],[1],[],[2],[3],[4],[5],[6],[7],[8],[9]],
            alwaysExp: []
        },
        prestamos: {
            labels: ['#','Categoría','Activo','Tipo','Colaborador/Destino','Sucursal','Inicio','Vence','Devuelto','Estado'],
            expMap: [[0],[1],[2],[3],[4],[5],[6],[7],[8],[9]],
            alwaysExp: [10, 11]
        },
        asignaciones: {
            labels: ['#','Colaborador','Sucursal','Activos','Fecha','Estado','Registrado por'],
            expMap: [[0],[1,2],[3],[6],[5],[7],[8]],
            alwaysExp: [4]
        },
        log: {
            labels: ['Activo','Categoría','Colaborador','Sucursal','Fecha Asig.','Fecha Dev.','Acción'],
            expMap: [[2],[1],[4,5],[6],[8],[9],[0]],
            alwaysExp: [3, 7, 10]
        },
        bajas: {
            labels: ['Cód. Inventario','Cód. Activo Fijo','Categoría','Tipo','Marca/Modelo','Motivo Baja','Valor Compra','Valor en Libros','Cuenta PUC','Sucursal','Fecha Baja'],
            expMap: [[0],[1],[2],[3],[4,5],[7],[8],[9],[10],[11],[12]],
            alwaysExp: [6]
        }
    };

    var tab        = '{{ $tab }}';
    var cfg        = AUDIT_COLS[tab];
    var storageKey = 'audit_cols_' + tab;
    var $menu      = $('#colDropdownMenu');
    var $expInputs = $('#exportColInputs');

    if (!cfg) return;

    /* ── Load / save visibility from localStorage ──────────────── */
    function loadVisible() {
        try {
            var s = localStorage.getItem(storageKey);
            if (s) return JSON.parse(s);
        } catch(e) {}
        return cfg.labels.map(function(_, i) { return i; }); // all visible
    }

    function saveVisible(vis) {
        try { localStorage.setItem(storageKey, JSON.stringify(vis)); } catch(e) {}
    }

    /* ── Apply visibility to table ──────────────────────────────── */
    function applyTableVisibility(vis) {
        $('#auditTable [data-col]').each(function() {
            var col = parseInt($(this).attr('data-col'));
            $(this).css('display', vis.indexOf(col) > -1 ? '' : 'none');
        });
    }

    /* ── Build export cols[] from visible view cols ─────────────── */
    function buildExportCols(vis) {
        // If all cols visible → no filtering (pass nothing)
        if (vis.length === cfg.labels.length) return null;

        var expSet = {};
        cfg.alwaysExp.forEach(function(i) { expSet[i] = true; });
        vis.forEach(function(viewIdx) {
            var mapped = cfg.expMap[viewIdx] || [];
            mapped.forEach(function(ei) { expSet[ei] = true; });
        });
        return Object.keys(expSet).map(Number).sort(function(a,b){return a-b;});
    }

    /* ── Inject cols[] hidden inputs into export form ───────────── */
    function syncExportForm(vis) {
        $expInputs.empty();
        var expCols = buildExportCols(vis);
        if (expCols) {
            expCols.forEach(function(i) {
                $expInputs.append('<input type="hidden" name="cols[]" value="' + i + '">');
            });
        }
    }

    /* ── Render dropdown ────────────────────────────────────────── */
    function renderDropdown(vis) {
        $menu.empty();
        $menu.append('<div class="col-selector-header"><i class="fas fa-columns mr-1"></i> Columnas visibles</div>');

        cfg.labels.forEach(function(label, i) {
            var checked = vis.indexOf(i) > -1 ? 'checked' : '';
            var $item = $('<label class="col-check-item">' +
                '<input type="checkbox" value="' + i + '" ' + checked + '> ' +
                '<span>' + label + '</span>' +
                '</label>');
            $menu.append($item);
        });

        $menu.append(
            '<div class="col-selector-footer">' +
            '<button type="button" class="btn btn-xs btn-outline-primary" id="colSelectAll" style="font-size:.74rem;padding:2px 8px;">Todas</button>' +
            '<button type="button" class="btn btn-xs btn-outline-secondary" id="colReset" style="font-size:.74rem;padding:2px 8px;">Resetear</button>' +
            '</div>'
        );
    }

    /* ── On checkbox change ──────────────────────────────────────── */
    $menu.on('change', 'input[type=checkbox]', function() {
        var vis = [];
        $menu.find('input[type=checkbox]:checked').each(function() {
            vis.push(parseInt($(this).val()));
        });
        vis.sort(function(a,b){return a-b;});
        saveVisible(vis);
        applyTableVisibility(vis);
        syncExportForm(vis);
    });

    /* ── "Todas" button ──────────────────────────────────────────── */
    $menu.on('click', '#colSelectAll', function(e) {
        e.stopPropagation();
        var all = cfg.labels.map(function(_, i) { return i; });
        $menu.find('input[type=checkbox]').prop('checked', true);
        saveVisible(all);
        applyTableVisibility(all);
        syncExportForm(all);
    });

    /* ── "Resetear" button ───────────────────────────────────────── */
    $menu.on('click', '#colReset', function(e) {
        e.stopPropagation();
        localStorage.removeItem(storageKey);
        var all = cfg.labels.map(function(_, i) { return i; });
        renderDropdown(all);
        applyTableVisibility(all);
        syncExportForm(all);
    });

    /* ── Keep dropdown open when clicking inside ─────────────────── */
    $menu.on('click', function(e) { e.stopPropagation(); });

    /* ── Init ────────────────────────────────────────────────────── */
    var visible = loadVisible();
    renderDropdown(visible);
    applyTableVisibility(visible);
    syncExportForm(visible);
});
</script>
@stop
