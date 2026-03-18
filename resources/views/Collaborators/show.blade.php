@extends('adminlte::page')

@section('title', 'Expediente · ' . $collaborator->full_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark" style="font-size:1.15rem;">
            <i class="fas fa-id-card text-primary mr-2"></i>
            Expediente Digital
        </h1>
        <div class="d-flex gap-1">
            @can('tech.assets.assign')
                <a href="{{ route('tech.assignments.create') }}?collaborator_id={{ $collaborator->id }}"
                   class="btn btn-sm btn-primary mr-1">
                    <i class="fas fa-laptop mr-1"></i> Asignar TI
                </a>
            @endcan
            @can('assets.assign')
                <a href="{{ route('assets.assignments.create') }}?collaborator_id={{ $collaborator->id }}"
                   class="btn btn-sm mr-1" style="background:#7c3aed;color:#fff;border:none;">
                    <i class="fas fa-boxes mr-1"></i> Asignar Otros
                </a>
            @endcan
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
    // Permisos del usuario autenticado
    $canTI   = auth()->user()->can('tech.assets.view');
    $canOTRO = auth()->user()->can('assets.view');
    $canAll  = $canTI && $canOTRO;

    // Pestaña activa por defecto: primera que puede ver
    $defaultTab = $canTI ? 'pane-ti' : ($canOTRO ? 'pane-otro' : 'pane-history');

    // Historial filtrado según rol
    $historyTI   = $assignmentHistory->where('asset_category', 'TI');
    $historyOTRO = $assignmentHistory->where('asset_category', 'OTRO');

    // Stats visibles según rol
    $statTI      = $canTI   ? $stats['ti']      : null;
    $statOTRO    = $canOTRO ? $stats['otro']     : null;
    $statLoans   = $canTI   ? $stats['loans']    : null;
    $statHistory = $canAll  ? $stats['history']  : ($canTI ? $historyTI->count() : $historyOTRO->count());

    // Avatar
    $words    = array_filter(explode(' ', $collaborator->full_name));
    $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(array_values($words), 0, 2))));
    $palette  = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#d63384'];
    $bgColor  = $palette[abs(crc32($collaborator->document)) % count($palette)];

    $mod      = $collaborator->modalidad_trabajo ?? 'presencial';
    $modLabel = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
    $modClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
    $modIcon  = match($mod) { 'remoto' => 'home', 'hibrido' => 'random', default => 'building' };
@endphp

{{-- ══ HEADER CARD ════════════════════════════════════════════ --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-3">
        <div class="d-flex align-items-center flex-wrap" style="gap:1rem;">

            {{-- Avatar --}}
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold flex-shrink-0"
                 style="width:68px;height:68px;font-size:1.5rem;background:{{ $bgColor }};">
                {{ $initials }}
            </div>

            {{-- Info --}}
            <div class="flex-grow-1">
                <h4 class="mb-0 font-weight-bold">
                    {{ $collaborator->full_name }}
                    <span class="badge {{ $collaborator->active ? 'badge-success' : 'badge-secondary' }} ml-1">
                        {{ $collaborator->active ? 'Activo' : 'Inactivo' }}
                    </span>
                </h4>
                <div class="text-muted mt-1" style="font-size:.9rem;">
                    @if($collaborator->position)
                        <span class="mr-3"><i class="fas fa-briefcase mr-1"></i>{{ $collaborator->position }}</span>
                    @endif
                    @if($collaborator->area)
                        <span class="mr-3"><i class="fas fa-layer-group mr-1"></i>{{ $collaborator->area }}</span>
                    @endif
                    <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $collaborator->branch?->name ?? 'Sin sucursal' }}</span>
                </div>
                <div class="mt-1">
                    <span class="badge {{ $modClass }} mr-1"><i class="fas fa-{{ $modIcon }} mr-1"></i>{{ $modLabel }}</span>
                    @if($collaborator->email)
                        <small class="text-muted mr-3"><i class="fas fa-envelope mr-1"></i>{{ $collaborator->email }}</small>
                    @endif
                    @if($collaborator->phone)
                        <small class="text-muted mr-3"><i class="fas fa-phone mr-1"></i>{{ $collaborator->phone }}</small>
                    @endif
                    <small class="text-muted"><i class="fas fa-id-card mr-1"></i>CC {{ $collaborator->document }}</small>
                </div>
            </div>

            {{-- Stats chips — solo los que el rol puede ver --}}
            <div class="d-flex flex-wrap" style="gap:.5rem;">
                @if($canTI)
                <div class="text-center px-3 py-2 rounded border" style="min-width:72px;background:#e8f4fd;">
                    <div class="font-weight-bold text-primary" style="font-size:1.3rem;line-height:1;">{{ $statTI }}</div>
                    <small class="text-muted">Activos TI</small>
                </div>
                @endif
                @if($canOTRO)
                <div class="text-center px-3 py-2 rounded border" style="min-width:72px;background:#e8f8ef;">
                    <div class="font-weight-bold text-success" style="font-size:1.3rem;line-height:1;">{{ $statOTRO }}</div>
                    <small class="text-muted">Otros</small>
                </div>
                @endif
                @if($canTI)
                <div class="text-center px-3 py-2 rounded border" style="min-width:72px;background:#fff8e1;">
                    <div class="font-weight-bold text-warning" style="font-size:1.3rem;line-height:1;">{{ $statLoans }}</div>
                    <small class="text-muted">Préstamos</small>
                </div>
                @endif
                <div class="text-center px-3 py-2 rounded border" style="min-width:72px;background:#f3f0ff;">
                    <div class="font-weight-bold" style="font-size:1.3rem;line-height:1;color:#6f42c1;">{{ $statHistory }}</div>
                    <small class="text-muted">Historial</small>
                </div>
            </div>
        </div>

        @if($collaborator->modalidad_trabajo === 'remoto')
            <div class="alert alert-info alert-sm mt-2 mb-0 py-2">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Colaborador Remoto:</strong> Se asigna preferiblemente portátil, cargador y diadema.
            </div>
        @endif
    </div>
</div>

{{-- ══ TABS ════════════════════════════════════════════════════ --}}
<ul class="nav nav-tabs nav-fill mb-0" id="expTabs" role="tablist" style="border-bottom:none;">

    @if($canTI)
    <li class="nav-item">
        <a class="nav-link font-weight-bold {{ $defaultTab === 'pane-ti' ? 'active' : '' }}"
           id="tab-ti" data-toggle="tab" href="#pane-ti" role="tab">
            <i class="fas fa-laptop mr-1 text-primary"></i> Activos TI
            @if($statTI > 0)<span class="badge badge-primary ml-1">{{ $statTI }}</span>@endif
        </a>
    </li>
    @endif

    @if($canOTRO)
    <li class="nav-item">
        <a class="nav-link font-weight-bold {{ $defaultTab === 'pane-otro' ? 'active' : '' }}"
           id="tab-otro" data-toggle="tab" href="#pane-otro" role="tab">
            <i class="fas fa-chair mr-1 text-success"></i> Otros Activos
            @if($statOTRO > 0)<span class="badge badge-success ml-1">{{ $statOTRO }}</span>@endif
        </a>
    </li>
    @endif

    @if($canTI)
    <li class="nav-item">
        <a class="nav-link font-weight-bold" id="tab-loans" data-toggle="tab" href="#pane-loans" role="tab">
            <i class="fas fa-handshake mr-1 text-warning"></i> Préstamos TI
            @if($statLoans > 0)<span class="badge badge-warning text-dark ml-1">{{ $statLoans }}</span>@endif
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link font-weight-bold {{ $defaultTab === 'pane-history' ? 'active' : '' }}"
           id="tab-history" data-toggle="tab" href="#pane-history" role="tab">
            <i class="fas fa-history mr-1 text-secondary"></i> Historial
            @if($statHistory > 0)<span class="badge badge-secondary ml-1">{{ $statHistory }}</span>@endif
        </a>
    </li>
</ul>

<div class="tab-content">

    {{-- ── Tab: Activos TI (solo Auxiliar_TI, Gestor_General, Admin) ── --}}
    @if($canTI)
    <div class="tab-pane fade {{ $defaultTab === 'pane-ti' ? 'show active' : '' }}" id="pane-ti" role="tabpanel">
        <div class="card card-outline card-primary" style="border-top-left-radius:0;border-top-right-radius:0;">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="font-weight-bold"><i class="fas fa-laptop mr-1"></i> Activos TI asignados actualmente</span>
                @can('tech.assets.assign')
                    @if($tiItems->isNotEmpty())
                        @php $firstAssignId = $tiItems->first()?->assignment_id; @endphp
                        @if($firstAssignId)
                            <a href="{{ route('tech.assignments.return', $firstAssignId) }}"
                               class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-undo mr-1"></i> Registrar Devolución
                            </a>
                        @endif
                    @endif
                @endcan
            </div>
            <div class="card-body p-0">
                @if($tiItems->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-laptop fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        Sin activos TI asignados actualmente.
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.8rem;text-transform:uppercase;">
                            <tr>
                                <th class="pl-3">Código</th><th>Tipo</th><th>Marca / Modelo</th>
                                <th>Serial</th><th>Estado</th><th>Desde</th><th>Asignación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tiItems as $aa)
                            <tr>
                                <td class="pl-3 align-middle"><code class="text-primary">{{ $aa->asset->internal_code }}</code></td>
                                <td class="align-middle">{{ $aa->asset->type?->name ?? '—' }}</td>
                                <td class="align-middle">{{ $aa->asset->brand }} {{ $aa->asset->model }}</td>
                                <td class="align-middle"><small class="text-muted">{{ $aa->asset->serial ?? '—' }}</small></td>
                                <td class="align-middle">
                                    @if($aa->asset->status)
                                        <span class="badge badge-pill"
                                              style="background:{{ $aa->asset->status->color ?? '#6c757d' }};color:#fff;">
                                            {{ $aa->asset->status->name }}
                                        </span>
                                    @else —@endif
                                </td>
                                <td class="align-middle"><small>{{ $aa->assigned_at?->format('d/m/Y') ?? '—' }}</small></td>
                                <td class="align-middle">
                                    <a href="{{ route('tech.assignments.show', $aa->assignment_id) }}"
                                       class="badge badge-light border text-dark">#{{ $aa->assignment_id }}</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Tab: Otros Activos (solo Gestor_Activos, Gestor_General, Admin) ── --}}
    @if($canOTRO)
    <div class="tab-pane fade {{ $defaultTab === 'pane-otro' ? 'show active' : '' }}" id="pane-otro" role="tabpanel">
        <div class="card card-outline card-success" style="border-top-left-radius:0;border-top-right-radius:0;">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="font-weight-bold"><i class="fas fa-chair mr-1"></i> Otros activos asignados actualmente</span>
                @can('assets.assign')
                    @if($otroItems->isNotEmpty())
                        @php $firstOtroId = $otroItems->first()?->assignment_id; @endphp
                        @if($firstOtroId)
                            <a href="{{ route('assets.assignments.return', $firstOtroId) }}"
                               class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-undo mr-1"></i> Registrar Devolución
                            </a>
                        @endif
                    @endif
                @endcan
            </div>
            <div class="card-body p-0">
                @if($otroItems->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chair fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        Sin otros activos asignados actualmente.
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.8rem;text-transform:uppercase;">
                            <tr>
                                <th class="pl-3">Código</th><th>Tipo</th><th>Marca / Modelo</th>
                                <th>Serial</th><th>Estado</th><th>Desde</th><th>Asignación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($otroItems as $aa)
                            <tr>
                                <td class="pl-3 align-middle"><code class="text-success">{{ $aa->asset->internal_code }}</code></td>
                                <td class="align-middle">{{ $aa->asset->type?->name ?? '—' }}</td>
                                <td class="align-middle">{{ $aa->asset->brand }} {{ $aa->asset->model }}</td>
                                <td class="align-middle"><small class="text-muted">{{ $aa->asset->serial ?? '—' }}</small></td>
                                <td class="align-middle">
                                    @if($aa->asset->status)
                                        <span class="badge badge-pill"
                                              style="background:{{ $aa->asset->status->color ?? '#6c757d' }};color:#fff;">
                                            {{ $aa->asset->status->name }}
                                        </span>
                                    @else —@endif
                                </td>
                                <td class="align-middle"><small>{{ $aa->assigned_at?->format('d/m/Y') ?? '—' }}</small></td>
                                <td class="align-middle">
                                    <a href="{{ route('assets.assignments.show', $aa->assignment_id) }}"
                                       class="badge badge-light border text-dark">#{{ $aa->assignment_id }}</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Tab: Préstamos TI (solo Auxiliar_TI, Gestor_General, Admin) ── --}}
    @if($canTI)
    <div class="tab-pane fade" id="pane-loans" role="tabpanel">
        <div class="card card-outline card-warning" style="border-top-left-radius:0;border-top-right-radius:0;">
            <div class="card-header py-2">
                <span class="font-weight-bold"><i class="fas fa-handshake mr-1"></i> Préstamos TI activos</span>
            </div>
            <div class="card-body p-0">
                @if($activeLoans->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-handshake fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        Sin préstamos activos actualmente.
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.8rem;text-transform:uppercase;">
                            <tr>
                                <th class="pl-3">Activo</th><th>Tipo</th><th>Inicio</th>
                                <th>Vence</th><th>Días restantes</th><th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeLoans as $loan)
                            @php
                                $days     = $loan->daysRemaining();
                                $isOver   = $days < 0;
                                $barColor = $isOver ? '#dc3545' : ($days <= 2 ? '#fd7e14' : ($days <= 7 ? '#ffc107' : '#28a745'));
                                $total    = $loan->start_date->diffInDays($loan->end_date) ?: 1;
                                $pct      = min(100, round(($loan->start_date->diffInDays(now()) / $total) * 100));
                            @endphp
                            <tr>
                                <td class="pl-3 align-middle">
                                    <code>{{ $loan->asset->internal_code }}</code>
                                    <br><small class="text-muted">{{ $loan->asset->brand }} {{ $loan->asset->model }}</small>
                                </td>
                                <td class="align-middle"><small>{{ $loan->asset->type?->name ?? '—' }}</small></td>
                                <td class="align-middle"><small>{{ $loan->start_date->format('d/m/Y') }}</small></td>
                                <td class="align-middle"><small>{{ $loan->end_date->format('d/m/Y') }}</small></td>
                                <td class="align-middle" style="min-width:120px;">
                                    <div class="d-flex align-items-center" style="gap:.4rem;">
                                        <div class="flex-grow-1">
                                            <div style="height:6px;background:#e9ecef;border-radius:4px;overflow:hidden;">
                                                <div style="height:100%;width:{{ $pct }}%;background:{{ $barColor }};border-radius:4px;"></div>
                                            </div>
                                        </div>
                                        <small style="color:{{ $barColor }};font-weight:600;white-space:nowrap;">
                                            {{ $isOver ? abs($days).'d vencido' : $days.'d' }}
                                        </small>
                                    </div>
                                </td>
                                <td class="align-middle"><small class="text-muted">{{ Str::limit($loan->notes, 50) ?? '—' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── Tab: Historial — filtrado por rol ───────────────────────── --}}
    <div class="tab-pane fade {{ $defaultTab === 'pane-history' ? 'show active' : '' }}" id="pane-history" role="tabpanel">
        <div class="card card-outline card-secondary" style="border-top-left-radius:0;border-top-right-radius:0;">
            <div class="card-header py-2">
                <span class="font-weight-bold">
                    <i class="fas fa-history mr-1"></i> Historial de asignaciones
                    @if(!$canAll)
                        <span class="badge badge-light border ml-1" style="font-size:.7rem;">
                            {{ $canTI ? 'Solo TI' : 'Solo Otros Activos' }}
                        </span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">
                @php
                    // Filtrar historial según permisos
                    $visibleHistory = $canAll ? $assignmentHistory
                                   : ($canTI ? $historyTI : $historyOTRO);
                @endphp

                @if($visibleHistory->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-history fa-2x mb-2 d-block" style="opacity:.3;"></i>
                        Sin historial de asignaciones.
                    </div>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.8rem;text-transform:uppercase;">
                            <tr>
                                <th class="pl-3">#</th>
                                <th>Categoría</th>
                                <th>Fecha</th>
                                <th>Activos</th>
                                <th>Estado</th>
                                <th>Registrado por</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visibleHistory as $a)
                            <tr>
                                <td class="pl-3 align-middle"><small class="text-muted">{{ $a->id }}</small></td>
                                <td class="align-middle">
                                    @if($a->asset_category === 'TI')
                                        <span class="badge" style="background:#0d6efd;color:#fff;font-size:.65rem;">
                                            <i class="fas fa-laptop mr-1"></i>TI
                                        </span>
                                    @else
                                        <span class="badge" style="background:#7c3aed;color:#fff;font-size:.65rem;">
                                            <i class="fas fa-boxes mr-1"></i>Otros
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle"><small>{{ $a->assignment_date->format('d/m/Y') }}</small></td>
                                <td class="align-middle">
                                    <small>
                                        @foreach($a->assignmentAssets->take(3) as $aa)
                                            <span class="badge badge-light border mr-1">{{ $aa->asset->internal_code }}</span>
                                        @endforeach
                                        @if($a->assignmentAssets->count() > 3)
                                            <span class="text-muted">+{{ $a->assignmentAssets->count() - 3 }} más</span>
                                        @endif
                                    </small>
                                </td>
                                <td class="align-middle">
                                    <span class="badge {{ $a->status === 'activa' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ ucfirst($a->status) }}
                                    </span>
                                </td>
                                <td class="align-middle"><small>{{ $a->assignedBy?->name ?? '—' }}</small></td>
                                <td class="align-middle text-center">
                                    {{-- Ruta según categoría --}}
                                    @if($a->asset_category === 'TI')
                                        <a href="{{ route('tech.assignments.show', $a) }}"
                                           class="btn btn-xs btn-info" title="Ver asignación TI">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('assets.assignments.show', $a) }}"
                                           class="btn btn-xs btn-info" title="Ver asignación Otros">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

</div>{{-- /tab-content --}}

@stop

@section('js')
<script>
// Restaurar pestaña activa desde sessionStorage
const TAB_KEY  = 'collab_tab_{{ $collaborator->id }}';
const savedTab = sessionStorage.getItem(TAB_KEY);
if (savedTab) {
    const trigger = document.querySelector(`a[href="${savedTab}"]`);
    if (trigger) $(trigger).tab('show');
}
document.querySelectorAll('#expTabs a[data-toggle="tab"]').forEach(el => {
    el.addEventListener('shown.bs.tab', e => {
        sessionStorage.setItem(TAB_KEY, e.target.getAttribute('href'));
    });
});
setTimeout(() => {
    document.querySelectorAll('.alert.show').forEach(el => el.classList.remove('show'));
}, 4000);
</script>
@stop
