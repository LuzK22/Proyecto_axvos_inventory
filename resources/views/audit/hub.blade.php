@extends('adminlte::page')
@section('title', 'Auditoría Global')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-search mr-2 text-primary"></i>Auditoría Global
        </h1>
        <small class="text-muted">Vista consolidada de todos los activos, asignaciones y movimientos</small>
    </div>
    @can('audit.export')
    <div class="d-flex flex-wrap" style="gap:6px;">

        {{-- Exporta el tab activo --}}
        <a href="{{ route('audit.export', array_merge(request()->all(), ['tab' => $tab])) }}"
           class="btn btn-sm btn-success">
            <i class="fas fa-file-csv mr-1"></i>
            Exportar {{ match($tab) {
                'otros'         => 'Otros Activos',
                'prestamos'     => 'Préstamos',
                'asignaciones'  => 'Asignaciones',
                'log'           => 'Log Movimientos',
                'actividad'     => 'Actividad',
                'bajas'         => 'Bajas',
                'exportaciones' => 'Exportaciones',
                'sesiones'      => 'Sesiones',
                default         => 'Activos TI',
            } }}
        </a>

        {{-- NIIF TI --}}
        <a href="{{ route('tech.reports.niif-export', request()->only(['branch_id','status_id','type_id','property_type','from','to'])) }}"
           class="btn btn-sm btn-outline-success"
           title="Activos TI con valor compra, depreciación anual, valor en libros y cuenta PUC">
            <i class="fas fa-calculator mr-1"></i> NIIF TI
        </a>

        {{-- NIIF Otros Activos --}}
        <a href="{{ route('assets.reports.niif-export', request()->only(['branch_id','status_id','type_id','property_type','from','to'])) }}"
           class="btn btn-sm btn-outline-success"
           title="Otros activos con valor compra, depreciación anual, valor en libros y cuenta PUC">
            <i class="fas fa-calculator mr-1"></i> NIIF Otros
        </a>

        {{-- Bajas TI + OTRO --}}
        <a href="{{ route('audit.export', array_merge(request()->only(['from','to','branch_id']), ['tab' => 'bajas'])) }}"
           class="btn btn-sm btn-outline-danger"
           title="Activos TI y OTRO con estado Baja, Donado o Vendido + valor en libros">
            <i class="fas fa-archive mr-1"></i> Bajas
        </a>

        {{-- Sesiones ISO 27001 --}}
        <a href="{{ route('audit.export', array_merge(request()->only(['from','to']), ['tab' => 'sesiones'])) }}"
           class="btn btn-sm btn-outline-secondary"
           title="Sesiones de usuarios: IP, dispositivo, inicio y última actividad (ISO 27001)">
            <i class="fas fa-user-clock mr-1"></i> Sesiones
        </a>

    </div>
    @endcan
</div>
@stop

@section('content')

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0" style="border-bottom:2px solid #dee2e6;">
    @foreach([
        ['ti',             'fa-laptop',        'Activos TI'],
        ['otros',          'fa-boxes',         'Otros Activos'],
        ['prestamos',      'fa-handshake',     'Préstamos'],
        ['asignaciones',   'fa-user-check',    'Asignaciones'],
        ['log',            'fa-history',       'Log Movimientos'],
        ['bajas',          'fa-archive',       'Bajas'],
        ['exportaciones',  'fa-download',      'Exportaciones'],
        ['sesiones',       'fa-user-clock',    'Sesiones'],
        ['actividad',      'fa-shield-alt',    'Actividad'],
    ] as [$key, $icon, $label])
    <li class="nav-item">
        <a class="nav-link {{ $tab === $key ? 'active font-weight-bold' : 'text-muted' }}"
           href="{{ route('audit.hub', array_merge(request()->except('page'), ['tab' => $key])) }}">
            <i class="fas {{ $icon }} mr-1"></i> {{ $label }}
        </a>
    </li>
    @endforeach
</ul>

<div class="card shadow-sm" style="border-top-left-radius:0;border-top-right-radius:0;">

    {{-- ── Filtros ──────────────────────────────────────────────────────── --}}
    <div class="card-body py-2 border-bottom bg-light">
        <form method="GET" class="form-inline flex-wrap" style="gap:6px;">
            <input type="hidden" name="tab" value="{{ $tab }}">

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
                <select name="category" class="form-control form-control-sm">
                    <option value="">TI + Otros Activos</option>
                    <option value="TI"   {{ request('category') === 'TI'   ? 'selected' : '' }}>Solo TI</option>
                    <option value="OTRO" {{ request('category') === 'OTRO' ? 'selected' : '' }}>Solo Otros</option>
                </select>
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
    <div class="card-header py-2">
        <small class="text-muted">{{ $data->total() }} registro(s) — Página {{ $data->currentPage() }} de {{ $data->lastPage() }}</small>
    </div>
    <div class="card-body p-0">

        {{-- Tab: TI / Otros --}}
        @if(in_array($tab, ['ti', 'otros']))
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Código</th>
                        <th>Tipo</th>
                        @if($tab === 'otros')<th>Subcategoría</th>@endif
                        <th>Marca / Modelo</th>
                        <th>Serial</th>
                        <th>Estado</th>
                        <th>Sucursal</th>
                        <th>Propiedad</th>
                        <th>Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asset)
                    <tr>
                        <td class="pl-3"><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                        <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                        @if($tab === 'otros')
                        <td>
                            @if($asset->type?->subcategory)
                                <span class="badge badge-light border" style="font-size:.65rem;">{{ $asset->type->subcategory }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                        <td><small class="text-muted">{{ $asset->serial ?? '—' }}</small></td>
                        <td>
                            @if($asset->status)
                                <span class="badge badge-pill" style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;font-size:.65rem;">
                                    {{ $asset->status->name }}
                                </span>
                            @endif
                        </td>
                        <td><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                        <td><span class="badge badge-{{ $asset->property_type === 'PROPIO' ? 'success' : 'info' }}" style="font-size:.62rem;">{{ $asset->property_type }}</span></td>
                        <td><small class="text-muted">{{ $asset->created_at?->format('d/m/Y') }}</small></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $tab === 'otros' ? 9 : 8 }}" class="text-center text-muted py-4">
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
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Activo</th>
                        <th>Tipo</th>
                        <th>Colaborador</th>
                        <th>Sucursal</th>
                        <th>Inicio</th>
                        <th>Vence</th>
                        <th>Devuelto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $loan)
                    <tr class="{{ $loan->status === 'vencido' ? 'table-warning' : '' }}">
                        <td class="pl-3"><small>{{ $loan->id }}</small></td>
                        <td><code style="font-size:.75rem;">{{ $loan->asset?->internal_code ?? '—' }}</code></td>
                        <td><small>{{ $loan->asset?->type?->name ?? '—' }}</small></td>
                        <td><small>{{ $loan->collaborator?->full_name ?? '—' }}</small></td>
                        <td><small>{{ $loan->collaborator?->branch?->name ?? '—' }}</small></td>
                        <td><small>{{ $loan->start_date?->format('d/m/Y') }}</small></td>
                        <td><small>{{ $loan->end_date?->format('d/m/Y') }}</small></td>
                        <td><small>{{ $loan->returned_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                        <td>
                            @php
                                $lc = match($loan->status) { 'activo' => 'success', 'devuelto' => 'secondary', 'vencido' => 'danger', default => 'light' };
                            @endphp
                            <span class="badge badge-{{ $lc }}" style="font-size:.65rem;">{{ ucfirst($loan->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No hay préstamos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Asignaciones --}}
        @if($tab === 'asignaciones')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Colaborador</th>
                        <th>Sucursal</th>
                        <th>Activos</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asn)
                    <tr>
                        <td class="pl-3"><small>{{ $asn->id }}</small></td>
                        <td><small class="font-weight-bold">{{ $asn->collaborator?->full_name ?? '—' }}</small></td>
                        <td><small>{{ $asn->collaborator?->branch?->name ?? '—' }}</small></td>
                        <td>
                            <span class="badge badge-secondary" style="font-size:.65rem;">
                                {{ $asn->assignmentAssets->count() }} activo(s)
                            </span>
                        </td>
                        <td><small>{{ $asn->assignment_date?->format('d/m/Y') }}</small></td>
                        <td>
                            <span class="badge badge-{{ $asn->status === 'activa' ? 'success' : 'secondary' }}" style="font-size:.65rem;">
                                {{ ucfirst($asn->status) }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $asn->assignedBy?->name ?? '—' }}</small></td>
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
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Activo</th>
                        <th>Colaborador</th>
                        <th>Sucursal</th>
                        <th>Fecha Asig.</th>
                        <th>Fecha Dev.</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $aa)
                    <tr>
                        <td class="pl-3"><code style="font-size:.75rem;">{{ $aa->asset?->internal_code ?? '—' }}</code></td>
                        <td><small>{{ $aa->assignment?->collaborator?->full_name ?? '—' }}</small></td>
                        <td><small>{{ $aa->assignment?->collaborator?->branch?->name ?? '—' }}</small></td>
                        <td><small>{{ $aa->assigned_at?->format('d/m/Y H:i') ?? $aa->created_at?->format('d/m/Y') }}</small></td>
                        <td><small>{{ $aa->returned_at?->format('d/m/Y H:i') ?? '—' }}</small></td>
                        <td>
                            @if($aa->returned_at)
                                <span class="badge badge-secondary" style="font-size:.65rem;"><i class="fas fa-undo mr-1"></i>Devuelto</span>
                            @else
                                <span class="badge badge-success" style="font-size:.65rem;"><i class="fas fa-check mr-1"></i>Asignado</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        {{-- Tab: Bajas --}}
        @if($tab === 'bajas')
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Cód. Inventario</th>
                        <th>Cód. Activo Fijo</th>
                        <th>Categoría</th>
                        <th>Tipo</th>
                        <th>Marca / Modelo</th>
                        <th>Estado</th>
                        <th>Valor Compra</th>
                        <th>Valor en Libros</th>
                        <th>Cuenta PUC</th>
                        <th>Sucursal</th>
                        <th>Fecha Baja</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $asset)
                    <tr>
                        <td class="pl-3"><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                        <td><small class="text-muted">{{ $asset->fixed_asset_code ?? '—' }}</small></td>
                        <td>
                            <span class="badge badge-{{ $asset->type?->category === 'TI' ? 'primary' : 'secondary' }}" style="font-size:.62rem;">
                                {{ $asset->type?->category ?? '—' }}
                            </span>
                        </td>
                        <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                        <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                        <td>
                            <span class="badge badge-danger" style="font-size:.65rem;">
                                {{ $asset->status?->name ?? '—' }}
                            </span>
                        </td>
                        <td><small>{{ $asset->purchase_value ? '$'.number_format($asset->purchase_value,0,',','.') : '—' }}</small></td>
                        <td><small class="text-warning">{{ $asset->current_book_value ? '$'.number_format($asset->current_book_value,0,',','.') : '—' }}</small></td>
                        <td><small class="text-muted">{{ $asset->account_code ?? '—' }}</small></td>
                        <td><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                        <td><small class="text-muted">{{ $asset->updated_at?->format('d/m/Y') }}</small></td>
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
