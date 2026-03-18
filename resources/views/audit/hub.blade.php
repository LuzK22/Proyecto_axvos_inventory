@extends('adminlte::page')

@section('title', 'Auditoría Global')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">
                <i class="fas fa-search-dollar text-primary mr-2"></i>Auditoría Global
            </h1>
            <small class="text-muted">Consulta, filtra y exporta toda la información del inventario</small>
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

{{-- ── Stats globales ──────────────────────────────────────── --}}
<div class="d-flex flex-wrap mb-3" style="gap:.5rem;">
    <div class="audit-chip">
        <span class="audit-chip-num text-dark">{{ number_format($stats['total_assets']) }}</span>
        <span class="audit-chip-label">Total Activos</span>
    </div>
    <div class="audit-chip">
        <span class="audit-chip-num text-primary">{{ $stats['ti_assets'] }}</span>
        <span class="audit-chip-label">Activos TI</span>
    </div>
    <div class="audit-chip">
        <span class="audit-chip-num text-success">{{ $stats['otro_assets'] }}</span>
        <span class="audit-chip-label">Otros Activos</span>
    </div>
    <div class="audit-chip">
        <span class="audit-chip-num text-info">{{ $stats['active_assignments'] }}</span>
        <span class="audit-chip-label">Asignaciones activas</span>
    </div>
    <div class="audit-chip">
        <span class="audit-chip-num text-warning">{{ $stats['active_loans'] }}</span>
        <span class="audit-chip-label">Préstamos activos</span>
    </div>
    @if($stats['overdue_loans'] > 0)
    <div class="audit-chip" style="border-color:#dc3545;">
        <span class="audit-chip-num text-danger">{{ $stats['overdue_loans'] }}</span>
        <span class="audit-chip-label">Préstamos vencidos</span>
    </div>
    @endif
    <div class="audit-chip">
        <span class="audit-chip-num" style="color:#6f42c1;">{{ $stats['collaborators'] }}</span>
        <span class="audit-chip-label">Colaboradores</span>
    </div>
</div>

{{-- ── Tabs ─────────────────────────────────────────────────── --}}
<ul class="nav nav-tabs nav-fill mb-0" id="auditTabs" role="tablist" style="border-bottom:none;">
    @php
        $tabs = [
            'ti'           => ['icon'=>'fa-laptop',      'color'=>'text-primary',   'label'=>'Activos TI'],
            'otros'        => ['icon'=>'fa-boxes',        'color'=>'text-success',   'label'=>'Otros Activos'],
            'prestamos'    => ['icon'=>'fa-handshake',    'color'=>'text-warning',   'label'=>'Préstamos'],
            'asignaciones' => ['icon'=>'fa-user-check',   'color'=>'text-info',      'label'=>'Asignaciones'],
            'log'          => ['icon'=>'fa-history',      'color'=>'text-secondary', 'label'=>'Log de Movimientos'],
        ];
    @endphp
    @foreach($tabs as $key => $t)
        <li class="nav-item">
            <a class="nav-link font-weight-bold {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('audit.hub', array_merge(request()->except(['tab','page']), ['tab' => $key])) }}">
                <i class="fas {{ $t['icon'] }} mr-1 {{ $t['color'] }}"></i>
                {{ $t['label'] }}
            </a>
        </li>
    @endforeach
</ul>

<div class="card card-outline card-primary mb-0"
     style="border-top-left-radius:0;border-top-right-radius:0;">

    {{-- ═══════════════════════════════════════════════════════
         FILTROS según tab activo
    ════════════════════════════════════════════════════════════ --}}
    <div class="card-body py-2 px-3" style="background:#fafbfc;border-bottom:1px solid #dee2e6;">
        <form id="filterForm" method="GET" action="{{ route('audit.hub') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="row align-items-end" style="gap:.25rem 0;">

                {{-- ── Activos TI & Otros: filtros de activo ──────── --}}
                @if(in_array($tab, ['ti', 'otros']))
                    <div class="col-md-3 pr-1">
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Código, marca, modelo, serial..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="type_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todos los tipos</option>
                            @foreach($tab === 'ti' ? $tiTypes : $otroTypes as $t)
                                <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="status_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todos los estados</option>
                            @foreach($statuses as $s)
                                <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="branch_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todas las sucursales</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="property_type" class="form-control form-control-sm auto-submit">
                            <option value="">Propiedad: todas</option>
                            <option value="PROPIO"    {{ request('property_type') === 'PROPIO'    ? 'selected' : '' }}>Propio</option>
                            <option value="LEASING"   {{ request('property_type') === 'LEASING'   ? 'selected' : '' }}>Leasing</option>
                            <option value="ALQUILADO" {{ request('property_type') === 'ALQUILADO' ? 'selected' : '' }}>Alquilado</option>
                            <option value="OTRO"      {{ request('property_type') === 'OTRO'      ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>
                    {{-- Rango fechas ingreso --}}
                    <div class="col-md-2 pr-1 mt-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Desde</span>
                            </div>
                            <input type="date" name="from" class="form-control form-control-sm"
                                   value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-2 px-1 mt-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Hasta</span>
                            </div>
                            <input type="date" name="to" class="form-control form-control-sm"
                                   value="{{ request('to') }}">
                        </div>
                    </div>
                @endif

                {{-- ── Préstamos ────────────────────────────────────── --}}
                @if($tab === 'prestamos')
                    <div class="col-md-3 pr-1">
                        <input type="text" name="collaborator" class="form-control form-control-sm"
                               placeholder="Colaborador o cédula..."
                               value="{{ request('collaborator') }}">
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="loan_status" class="form-control form-control-sm auto-submit">
                            <option value="">Todos los estados</option>
                            <option value="activo"   {{ request('loan_status') === 'activo'   ? 'selected' : '' }}>Activo</option>
                            <option value="vencido"  {{ request('loan_status') === 'vencido'  ? 'selected' : '' }}>Vencido</option>
                            <option value="devuelto" {{ request('loan_status') === 'devuelto' ? 'selected' : '' }}>Devuelto</option>
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="branch_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todas las sucursales</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Desde</span>
                            </div>
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-2 px-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Hasta</span>
                            </div>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                        </div>
                    </div>
                @endif

                {{-- ── Asignaciones ─────────────────────────────────── --}}
                @if($tab === 'asignaciones')
                    <div class="col-md-3 pr-1">
                        <input type="text" name="collaborator" class="form-control form-control-sm"
                               placeholder="Colaborador o cédula..."
                               value="{{ request('collaborator') }}">
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="assign_status" class="form-control form-control-sm auto-submit">
                            <option value="">Todos los estados</option>
                            <option value="activa"   {{ request('assign_status') === 'activa'   ? 'selected' : '' }}>Activa</option>
                            <option value="devuelta" {{ request('assign_status') === 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="modality" class="form-control form-control-sm auto-submit">
                            <option value="">Modalidad: todas</option>
                            <option value="presencial" {{ request('modality') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                            <option value="remoto"     {{ request('modality') === 'remoto'     ? 'selected' : '' }}>Remoto</option>
                            <option value="hibrido"    {{ request('modality') === 'hibrido'    ? 'selected' : '' }}>Híbrido</option>
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="branch_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todas las sucursales</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Desde</span>
                            </div>
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-1 pl-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Hasta</span>
                            </div>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                        </div>
                    </div>
                @endif

                {{-- ── Log de movimientos ───────────────────────────── --}}
                @if($tab === 'log')
                    <div class="col-md-3 pr-1">
                        <input type="text" name="collaborator" class="form-control form-control-sm"
                               placeholder="Colaborador o cédula..."
                               value="{{ request('collaborator') }}">
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="action" class="form-control form-control-sm auto-submit">
                            <option value="">Todos los eventos</option>
                            <option value="asignado" {{ request('action') === 'asignado' ? 'selected' : '' }}>Asignación</option>
                            <option value="devuelto" {{ request('action') === 'devuelto' ? 'selected' : '' }}>Devolución</option>
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <select name="branch_id" class="form-control form-control-sm auto-submit">
                            <option value="">Todas las sucursales</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 px-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Desde</span>
                            </div>
                            <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                        </div>
                    </div>
                    <div class="col-md-2 px-1">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white" style="font-size:.7rem;">Hasta</span>
                            </div>
                            <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                        </div>
                    </div>
                @endif

                {{-- ── Acciones comunes: buscar / limpiar / exportar ── --}}
                <div class="col-md-1 pl-1 {{ in_array($tab, ['ti','otros']) ? 'mt-1' : '' }}">
                    <div class="d-flex" style="gap:.25rem;">
                        <button type="submit" class="btn btn-sm btn-primary" title="Aplicar filtros">
                            <i class="fas fa-filter"></i>
                        </button>
                        <a href="{{ route('audit.hub', ['tab' => $tab]) }}"
                           class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>

                {{-- Exportar (mismos filtros actuales) --}}
                @can('audit.export')
                <div class="col-12 mt-2 d-flex justify-content-end">
                    <a href="{{ route('audit.export', request()->all()) }}"
                       class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv mr-1"></i>
                        Exportar CSV (filtros aplicados)
                    </a>
                </div>
                @endcan

            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         TABLA DE DATOS
    ════════════════════════════════════════════════════════════ --}}
    <div class="card-body p-0">

        @if($data->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity:.25;"></i>
                <p class="mb-1">No se encontraron resultados con los filtros aplicados.</p>
                <a href="{{ route('audit.hub', ['tab' => $tab]) }}"
                   class="btn btn-sm btn-outline-primary mt-2">
                    <i class="fas fa-times mr-1"></i> Limpiar filtros
                </a>
            </div>
        @else

            {{-- ── Activos TI & Otros ───────────────────────────── --}}
            @if(in_array($tab, ['ti', 'otros']))
                <table class="table table-hover table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.78rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Estado</th>
                            <th>Sucursal</th>
                            <th>Propiedad</th>
                            <th>Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $a)
                            <tr>
                                <td class="pl-3 align-middle">
                                    <code class="{{ $tab === 'ti' ? 'text-primary' : 'text-success' }}">
                                        {{ $a->internal_code }}
                                    </code>
                                </td>
                                <td class="align-middle">{{ $a->type?->name }}</td>
                                <td class="align-middle">
                                    {{ $a->brand }} {{ $a->model }}
                                </td>
                                <td class="align-middle"><small class="text-muted">{{ $a->serial }}</small></td>
                                <td class="align-middle">
                                    @if($a->status)
                                        <span class="badge badge-pill"
                                              style="background:{{ $a->status->color ?? '#6c757d' }};color:#fff;">
                                            {{ $a->status->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle"><small>{{ $a->branch?->name }}</small></td>
                                <td class="align-middle">
                                    <small class="text-muted">{{ $a->property_type }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $a->created_at?->format('d/m/Y') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- ── Préstamos ────────────────────────────────────── --}}
            @if($tab === 'prestamos')
                <table class="table table-hover table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.78rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">#</th>
                            <th>Activo</th>
                            <th>Colaborador</th>
                            <th>Sucursal</th>
                            <th>Inicio</th>
                            <th>Vence</th>
                            <th>Vigencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $loan)
                            @php
                                $days = $loan->daysRemaining();
                                $over = $days < 0;
                                $barColor = $over ? '#dc3545' : ($days <= 2 ? '#fd7e14' : ($days <= 7 ? '#ffc107' : '#28a745'));
                                $total = max(1, $loan->start_date->diffInDays($loan->end_date));
                                $pct   = min(100, round($loan->start_date->diffInDays(now()) / $total * 100));
                            @endphp
                            <tr>
                                <td class="pl-3 align-middle"><small>{{ $loan->id }}</small></td>
                                <td class="align-middle">
                                    <code class="text-primary">{{ $loan->asset?->internal_code }}</code>
                                    <br><small class="text-muted">{{ $loan->asset?->type?->name }}</small>
                                </td>
                                <td class="align-middle">
                                    {{ $loan->collaborator?->full_name }}
                                    <br><small class="text-muted">{{ $loan->collaborator?->document }}</small>
                                </td>
                                <td class="align-middle"><small>{{ $loan->collaborator?->branch?->name }}</small></td>
                                <td class="align-middle"><small>{{ $loan->start_date->format('d/m/Y') }}</small></td>
                                <td class="align-middle"><small>{{ $loan->end_date->format('d/m/Y') }}</small></td>
                                <td class="align-middle" style="min-width:110px;">
                                    @if($loan->status === 'activo')
                                        <div style="height:5px;background:#e9ecef;border-radius:3px;margin-bottom:2px;">
                                            <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:3px;"></div>
                                        </div>
                                        <small style="color:{{ $barColor }};font-size:.7rem;">
                                            {{ $over ? abs($days).'d vencido' : $days.'d restantes' }}
                                        </small>
                                    @else
                                        <small class="text-muted">
                                            {{ $loan->returned_at?->format('d/m/Y') }}
                                        </small>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @php
                                        $lc = match($loan->status) {
                                            'activo'   => 'badge-primary',
                                            'vencido'  => 'badge-danger',
                                            'devuelto' => 'badge-secondary',
                                            default    => 'badge-light',
                                        };
                                    @endphp
                                    <span class="badge {{ $lc }}">{{ ucfirst($loan->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- ── Asignaciones ─────────────────────────────────── --}}
            @if($tab === 'asignaciones')
                <table class="table table-hover table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.78rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">#</th>
                            <th>Colaborador</th>
                            <th>Sucursal</th>
                            <th>Activos</th>
                            <th>Modalidad</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Registrado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $a)
                            <tr>
                                <td class="pl-3 align-middle">
                                    <a href="{{ route('tech.assignments.show', $a) }}"
                                       class="badge badge-light border text-dark">#{{ $a->id }}</a>
                                </td>
                                <td class="align-middle">
                                    {{ $a->collaborator?->full_name }}
                                    <br><small class="text-muted">{{ $a->collaborator?->document }}</small>
                                </td>
                                <td class="align-middle"><small>{{ $a->collaborator?->branch?->name }}</small></td>
                                <td class="align-middle">
                                    @foreach($a->assignmentAssets->take(3) as $aa)
                                        <span class="badge badge-light border" style="font-size:.65rem;">
                                            {{ $aa->asset?->internal_code }}
                                        </span>
                                    @endforeach
                                    @if($a->assignmentAssets->count() > 3)
                                        <small class="text-muted">+{{ $a->assignmentAssets->count()-3 }}</small>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @php
                                        $mc = match($a->work_modality ?? 'presencial') {
                                            'remoto'  => 'badge-info',
                                            'hibrido' => 'badge-warning text-dark',
                                            default   => 'badge-success',
                                        };
                                    @endphp
                                    <span class="badge {{ $mc }}">{{ ucfirst($a->work_modality ?? 'presencial') }}</span>
                                </td>
                                <td class="align-middle"><small>{{ $a->assignment_date?->format('d/m/Y') }}</small></td>
                                <td class="align-middle">
                                    <span class="badge {{ $a->status === 'activa' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ ucfirst($a->status) }}
                                    </span>
                                </td>
                                <td class="align-middle"><small>{{ $a->assignedBy?->name ?? '—' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- ── Log de movimientos ───────────────────────────── --}}
            @if($tab === 'log')
                <table class="table table-hover table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.78rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Evento</th>
                            <th>Activo</th>
                            <th>Colaborador</th>
                            <th>Sucursal</th>
                            <th>Asig. #</th>
                            <th>Fecha</th>
                            <th>Devolución</th>
                            <th>Devuelto por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $aa)
                            <tr>
                                <td class="pl-3 align-middle">
                                    @if($aa->returned_at)
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-undo mr-1"></i>Devolución
                                        </span>
                                    @else
                                        <span class="badge badge-primary">
                                            <i class="fas fa-arrow-right mr-1"></i>Asignación
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <code class="text-primary">{{ $aa->asset?->internal_code }}</code>
                                    <br><small class="text-muted">{{ $aa->asset?->type?->name }}</small>
                                </td>
                                <td class="align-middle">
                                    {{ $aa->assignment?->collaborator?->full_name }}
                                    <br><small class="text-muted">{{ $aa->assignment?->collaborator?->document }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $aa->assignment?->collaborator?->branch?->name }}</small>
                                </td>
                                <td class="align-middle">
                                    <a href="{{ route('tech.assignments.show', $aa->assignment_id) }}"
                                       class="badge badge-light border text-dark">
                                        #{{ $aa->assignment_id }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $aa->created_at?->format('d/m/Y H:i') }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $aa->returned_at?->format('d/m/Y H:i') ?? '—' }}</small>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $aa->returnedBy?->name ?? '—' }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        @endif
    </div>

    {{-- Paginación --}}
    @if($data->hasPages())
        <div class="card-footer py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Mostrando {{ $data->firstItem() }}–{{ $data->lastItem() }}
                    de <strong>{{ $data->total() }}</strong> registros
                </small>
                {{ $data->links() }}
            </div>
        </div>
    @else
        <div class="card-footer py-1">
            <small class="text-muted">{{ $data->total() }} registro(s)</small>
        </div>
    @endif
</div>

@stop

@section('css')
<style>
/* ── Chips de stats ───────────────────────────────────────── */
.audit-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: .4rem .9rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    min-width: 80px;
    text-align: center;
}
.audit-chip-num {
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1;
}
.audit-chip-label {
    font-size: .65rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-top: 2px;
    white-space: nowrap;
}
</style>
@stop

@section('js')
<script>
// Auto-submit en selects con clase .auto-submit
document.querySelectorAll('.auto-submit').forEach(el => {
    el.addEventListener('change', () => document.getElementById('filterForm').submit());
});

// Debounce en inputs de texto
let _t;
document.querySelectorAll('#filterForm input[type="text"]').forEach(el => {
    el.addEventListener('input', () => {
        clearTimeout(_t);
        _t = setTimeout(() => document.getElementById('filterForm').submit(), 400);
    });
});

// Auto-submit en fechas
document.querySelectorAll('#filterForm input[type="date"]').forEach(el => {
    el.addEventListener('change', () => document.getElementById('filterForm').submit());
});
</script>
@stop
