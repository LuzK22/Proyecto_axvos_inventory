@extends('adminlte::page')
@section('title', 'Nueva Asignacion - Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-plus-circle mr-2" style="color:#7c3aed;"></i>Nueva Asignacion - Otros Activos
        </h1>
        <small class="text-muted">Asigna mobiliario, enseres u otros activos a un colaborador, jefe, area o pool</small>
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
    <div class="col-lg-8">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-map-marker-alt mr-1 text-primary"></i> Destino del Activo
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="row g-2" id="destinationButtons">
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-primary"
                                    onclick="setDestination('collaborator')" id="btn-collaborator">
                                <i class="fas fa-user d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Colaborador</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('jefe')" id="btn-jefe">
                                <i class="fas fa-user-tie d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Jefe / Responsable</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('area')" id="btn-area">
                                <i class="fas fa-map-marker-alt d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Area</span>
                            </button>
                        </div>
                        <div class="col-6 col-md-3">
                            <button type="button" class="btn btn-block dest-btn btn-outline-secondary"
                                    onclick="setDestination('pool')" id="btn-pool">
                                <i class="fas fa-sync-alt d-block mb-1" style="font-size:1.2rem;"></i>
                                <span style="font-size:.8rem;">Pool Compartido</span>
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="destination_type" id="destinationType" value="{{ old('destination_type', $destinationType ?? 'collaborator') }}">
                </div>

                <div class="alert alert-light border py-2 mb-3 d-flex align-items-center" id="destDescription">
                    <i class="fas fa-user mr-2 text-primary"></i>
                    <small id="destDescText">El activo queda bajo responsabilidad directa del colaborador.</small>
                </div>

                <div id="panelCollaborator">
                    <label class="text-muted small mb-1" id="collaboratorLabel">
                        Colaborador <span class="text-danger">*</span>
                    </label>
                    <select name="collaborator_id" class="form-control form-control-sm" id="collaboratorSelect">
                        <option value="">- Seleccionar -</option>
                        @foreach($collaborators as $c)
                            <option value="{{ $c->id }}" {{ (string) old('collaborator_id', $selectedCollaboratorId ?? '') === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }}
                                @if($c->position) - {{ $c->position }}@endif
                                @if($c->branch) ({{ $c->branch->name }})@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="panelArea" style="display:none;">
                    <label class="text-muted small mb-1">
                        Area / Espacio <span class="text-danger">*</span>
                    </label>
                    <select name="area_id" class="form-control form-control-sm" id="areaSelect">
                        <option value="">- Seleccionar area -</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" {{ old('area_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->name }}
                                @if($a->branch) - {{ $a->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">
                        El area no existe?
                        <a href="{{ route('areas.create') }}" target="_blank">Crear nueva area</a>
                    </small>
                </div>

                <div id="panelPool" style="display:none;">
                    <label class="text-muted small mb-1">
                        Area del Pool <small class="text-muted">(opcional)</small>
                    </label>
                    <select name="area_id" class="form-control form-control-sm" id="poolAreaSelect">
                        <option value="">- Sin area especifica -</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" {{ old('area_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->name }}
                                @if($a->branch) - {{ $a->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">El activo estara en uso rotativo entre varios usuarios.</small>
                </div>
            </div>
        </div>

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
                           placeholder="Buscar por codigo, tipo, nombre...">
                </div>
                <div style="max-height:360px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead style="background:#f4f6f9;font-size:.72rem;text-transform:uppercase;position:sticky;top:0;">
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Codigo</th>
                                <th>Tipo</th>
                                <th>Subcategoria</th>
                                <th>Nombre / Marca</th>
                            </tr>
                        </thead>
                        <tbody id="assetTableBody">
                            @forelse($assets as $asset)
                            <tr class="asset-row" data-search="{{ strtolower($asset->internal_code . ' ' . $asset->type?->name . ' ' . $asset->type?->subcategory . ' ' . $asset->brand . ' ' . $asset->model) }}">
                                <td class="text-center">
                                    <input type="checkbox" name="assets[]" value="{{ $asset->id }}"
                                           class="asset-check"
                                           {{ in_array($asset->id, old('assets', [])) ? 'checked' : '' }}>
                                </td>
                                <td><code style="font-size:.75rem;">{{ $asset->internal_code }}</code></td>
                                <td><small>{{ $asset->type?->name ?? '-' }}</small></td>
                                <td>
                                    @if($asset->type?->subcategory)
                                        <span class="badge badge-light border text-muted" style="font-size:.68rem;">
                                            {{ $asset->type->subcategory }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
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

    <div class="col-lg-4">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-calendar mr-1 text-primary"></i> Detalles de la Asignacion
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label class="text-muted small mb-1">Fecha de asignacion <span class="text-danger">*</span></label>
                    <input type="date" name="assignment_date" class="form-control form-control-sm"
                           value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="text-muted small mb-1">Notas (opcional)</label>
                    <textarea name="notes" rows="3" class="form-control form-control-sm"
                              placeholder="Observaciones sobre la asignacion...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

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
            <i class="fas fa-check mr-1"></i> Crear Asignacion
        </button>

        <div class="card mt-3 shadow-sm border-left-warning">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-file-contract mr-1 text-warning"></i>
                    Se generara el <strong>Acta de Entrega OTRO</strong> automaticamente al guardar.
                </small>
            </div>
        </div>
    </div>
</div>
</form>
@stop

@section('js')
<script>
const destinations = {
    collaborator: {
        icon: 'fa-user',
        label: 'Colaborador',
        desc: 'El activo queda bajo responsabilidad directa del colaborador.',
        panels: ['panelCollaborator'],
    },
    jefe: {
        icon: 'fa-user-tie',
        label: 'Jefe / Responsable de Area',
        desc: 'El activo queda con el jefe o responsable del area.',
        panels: ['panelCollaborator'],
    },
    area: {
        icon: 'fa-map-marker-alt',
        label: 'Area Compartida',
        desc: 'El activo queda en el espacio fisico del area.',
        panels: ['panelArea'],
    },
    pool: {
        icon: 'fa-sync-alt',
        label: 'Pool Compartido',
        desc: 'Activo de uso rotativo sin responsable fijo.',
        panels: ['panelPool'],
    },
};

function setDestination(type) {
    const cfg = destinations[type];
    if (!cfg) return;

    document.getElementById('destinationType').value = type;

    Object.keys(destinations).forEach(k => {
        const btn = document.getElementById('btn-' + k);
        if (btn) {
            btn.className = 'btn btn-block dest-btn ' + (k === type ? 'btn-primary' : 'btn-outline-secondary');
        }
    });

    ['panelCollaborator', 'panelArea', 'panelPool'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    cfg.panels.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = '';
    });

    const collLabel = document.getElementById('collaboratorLabel');
    if (collLabel) {
        collLabel.innerHTML = (type === 'jefe')
            ? 'Jefe / Responsable <span class="text-danger">*</span>'
            : 'Colaborador <span class="text-danger">*</span>';
    }

    const areaSelect = document.getElementById('areaSelect');
    const poolAreaSelect = document.getElementById('poolAreaSelect');
    if (areaSelect) areaSelect.disabled = type !== 'area';
    if (poolAreaSelect) poolAreaSelect.disabled = type !== 'pool';

    document.getElementById('destDescText').textContent = cfg.desc;
    document.getElementById('destSummaryText').textContent = cfg.label;
    const icon = document.querySelector('#destDescription i');
    if (icon) icon.className = 'fas ' + cfg.icon + ' mr-2 text-primary';
    const sumIcon = document.querySelector('#destSummary i');
    if (sumIcon) sumIcon.className = 'fas ' + cfg.icon + ' mr-2';
}

function updateCount() {
    const n = document.querySelectorAll('.asset-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
}

document.querySelectorAll('.asset-check').forEach(cb => cb.addEventListener('change', updateCount));
updateCount();

document.getElementById('assetSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

setDestination(document.getElementById('destinationType').value || 'collaborator');
</script>
@stop
