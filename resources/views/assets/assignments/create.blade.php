@extends('adminlte::page')
@section('title', 'Nueva Asignación — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-plus-circle mr-2" style="color:#7c3aed;"></i>Nueva Asignación — Otros Activos
        </h1>
        <small class="text-muted">Asigna mobiliario, enseres u otros activos a un colaborador, jefe, área o pool</small>
    </div>
    <a href="{{ route('assets.assignments.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<form method="POST" action="{{ route('assets.assignments.store') }}" id="assignForm">
@csrf

<div class="row">
    {{-- ── Columna principal ──────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- ── DESTINO ────────────────────────────────────────────────── --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-map-marker-alt mr-1 text-primary"></i> Destino del Activo
                </h6>
            </div>
            <div class="card-body">

                {{-- Selector de tipo de destino (4 opciones) --}}
                <div class="mb-3">
                    <div class="row g-2" id="destinationButtons">
                        {{-- Colaborador --}}
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-primary"
                                    onclick="setDestination('collaborator')" id="btn-collaborator">
                                <i class="fas fa-user d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Colaborador</span>
                            </button>
                        </div>
                        {{-- Jefe / Responsable --}}
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('jefe')" id="btn-jefe">
                                <i class="fas fa-user-tie d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Jefe / Responsable</span>
                            </button>
                        </div>
                        {{-- Área --}}
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('area')" id="btn-area">
                                <i class="fas fa-map-marker-alt d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Área</span>
                            </button>
                        </div>
                        {{-- Pool --}}
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('pool')" id="btn-pool">
                                <i class="fas fa-sync-alt d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Pool Compartido</span>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="destination_type" id="destinationType" value="collaborator">
                </div>

                {{-- Descripción dinámica del destino seleccionado --}}
                <div class="alert alert-light border py-2 mb-3 d-flex align-items-center" id="destDescription">
                    <i class="fas fa-user mr-2 text-primary"></i>
                    <small id="destDescText">El activo queda bajo responsabilidad directa del colaborador.</small>
                </div>

                {{-- Panel: Colaborador / Jefe (mismo input, diferente label) --}}
                <div id="panelCollaborator">
                    <label class="text-muted small mb-1" id="collaboratorLabel">
                        Colaborador <span class="text-danger">*</span>
                    </label>
                    <select name="collaborator_id" class="form-control form-control-sm">
                        <option value="">— Seleccionar —</option>
                        @foreach($collaborators as $c)
                            <option value="{{ $c->id }}" {{ old('collaborator_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }}
                                @if($c->position) — {{ $c->position }}@endif
                                @if($c->branch) ({{ $c->branch->name }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Panel: Área --}}
                <div id="panelArea" style="display:none;">
                    <label class="text-muted small mb-1">
                        Área / Espacio <span class="text-danger">*</span>
                    </label>
                    <select name="area_id" class="form-control form-control-sm">
                        <option value="">— Seleccionar área —</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" {{ old('area_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->name }}
                                @if($a->branch) — {{ $a->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        ¿El área no existe?
                        <a href="{{ route('areas.create') }}" target="_blank">Crear nueva área</a>
                    </small>
                </div>

                {{-- Panel: Pool (área opcional) --}}
                <div id="panelPool" style="display:none;">
                    <label class="text-muted small mb-1">
                        Área del Pool <small class="text-muted">(opcional)</small>
                    </label>
                    <select name="area_id" class="form-control form-control-sm">
                        <option value="">— Sin área específica —</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}">
                                {{ $a->name }}
                                @if($a->branch) — {{ $a->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">El activo estará en uso rotativo entre varios usuarios.</small>
                </div>

            </div>
        </div>

        {{-- ── ACTIVOS A ASIGNAR ──────────────────────────────────────── --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i>
                    Activos a asignar
                    <span class="badge badge-secondary ml-1" id="selectedCount">0 seleccionados</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2 border-bottom">
                    <input type="text" id="assetSearch" class="form-control form-control-sm"
                           placeholder="Buscar por código, tipo, nombre...">
                </div>
                <div style="max-height:360px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.72rem;text-transform:uppercase;position:sticky;top:0;">
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Subcategoría</th>
                                <th>Nombre / Marca</th>
                            </tr>
                        </thead>
                        <tbody id="assetTableBody">
                            @forelse($assets as $asset)
                            <tr class="asset-row"
                                data-search="{{ strtolower($asset->internal_code . ' ' . $asset->type?->name . ' ' . $asset->type?->subcategory . ' ' . $asset->brand . ' ' . $asset->model) }}">
                                <td class="text-center">
                                    <input type="checkbox" name="assets[]" value="{{ $asset->id }}"
                                           class="asset-check"
                                           {{ in_array($asset->id, old('assets', [])) ? 'checked' : '' }}>
                                </td>
                                <td><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                                <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                                <td>
                                    @if($asset->type?->subcategory)
                                        <span class="badge badge-light border text-muted" style="font-size:.68rem;">
                                            {{ $asset->type->subcategory }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-2x mb-2 d-block" style="opacity:.3;"></i>
                                    No hay Otros Activos disponibles para asignar.
                                    <div class="mt-2">
                                        <a href="{{ route('assets.create') }}" class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-plus mr-1"></i> Registrar activo
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Columna lateral ─────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-calendar mr-1 text-primary"></i> Detalles de la Asignación
                </h6>
            </div>
            <div class="card-body">

                <div class="form-group mb-2">
                    <label class="text-muted small mb-1">Fecha de asignación <span class="text-danger">*</span></label>
                    <input type="date" name="assignment_date"
                           class="form-control form-control-sm"
                           value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                </div>

                <div class="form-group mb-0">
                    <label class="text-muted small mb-1">Notas (opcional)</label>
                    <textarea name="notes" rows="3"
                              class="form-control form-control-sm"
                              placeholder="Observaciones sobre la asignación...">{{ old('notes') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Resumen del destino seleccionado --}}
        <div class="card border-left-primary mb-3">
            <div class="card-body py-2 px-3">
                <p class="mb-1 font-weight-bold small text-uppercase text-muted">Destino seleccionado</p>
                <div id="destSummary" class="d-flex align-items-center">
                    <i class="fas fa-user mr-2" style="color:#7c3aed;"></i>
                    <span id="destSummaryText">Colaborador</span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-check mr-1"></i> Crear Asignación
        </button>

        <div class="card mt-3 shadow-sm border-left-warning">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-file-contract mr-1 text-warning"></i>
                    Se generará el <strong>Acta de Entrega OTRO</strong> automáticamente al guardar.
                </small>
            </div>
        </div>

    </div>
</div>

</form>
@stop

@section('js')
<script>
/* ── Configuración de destinos ─────────────────────────────── */
const destinations = {
    collaborator: {
        icon:  'fa-user',
        label: 'Colaborador',
        desc:  'El activo queda bajo responsabilidad directa del colaborador.',
        panels: ['panelCollaborator'],
    },
    jefe: {
        icon:  'fa-user-tie',
        label: 'Jefe / Responsable de Área',
        desc:  'El activo queda con el jefe o responsable del área (ej: monitor para trabajo remoto).',
        panels: ['panelCollaborator'],
    },
    area: {
        icon:  'fa-map-marker-alt',
        label: 'Área Compartida',
        desc:  'El activo queda en el espacio físico del área, disponible para todos sus miembros.',
        panels: ['panelArea'],
    },
    pool: {
        icon:  'fa-sync-alt',
        label: 'Pool Compartido',
        desc:  'Activo de uso rotativo. No tiene un responsable fijo — se presta según necesidad.',
        panels: ['panelPool'],
    },
};

function setDestination(type) {
    const cfg = destinations[type];
    if (!cfg) return;

    // Actualizar input hidden
    document.getElementById('destinationType').value = type;

    // Actualizar botones
    Object.keys(destinations).forEach(k => {
        const btn = document.getElementById('btn-' + k);
        if (btn) {
            btn.className = 'btn btn-block dest-btn ' + (k === type ? 'btn-primary' : 'btn-outline-secondary');
        }
    });

    // Ocultar todos los paneles
    ['panelCollaborator', 'panelArea', 'panelPool'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Mostrar paneles del destino activo
    cfg.panels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = '';
    });

    // Actualizar label del colaborador si aplica
    const collLabel = document.getElementById('collaboratorLabel');
    if (collLabel) {
        collLabel.innerHTML = (type === 'jefe')
            ? 'Jefe / Responsable <span class="text-danger">*</span>'
            : 'Colaborador <span class="text-danger">*</span>';
    }

    // Actualizar descripción y resumen
    document.getElementById('destDescText').textContent = cfg.desc;
    document.getElementById('destSummaryText').textContent = cfg.label;
    const icon = document.querySelector('#destDescription i');
    if (icon) icon.className = 'fas ' + cfg.icon + ' mr-2 text-primary';
    const sumIcon = document.querySelector('#destSummary i');
    if (sumIcon) sumIcon.className = 'fas ' + cfg.icon + ' mr-2';
}

/* ── Contador de activos seleccionados ─────────────────────── */
function updateCount() {
    const n = document.querySelectorAll('.asset-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
}
document.querySelectorAll('.asset-check').forEach(cb => cb.addEventListener('change', updateCount));
updateCount();

/* ── Búsqueda en tabla de activos ──────────────────────────── */
document.getElementById('assetSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

// Inicializar con estado por defecto
setDestination('collaborator');
</script>
@stop
