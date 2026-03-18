@extends('adminlte::page')
@section('title', 'Nueva Asignación — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark">
        <i class="fas fa-plus-circle mr-2" style="color:#7c3aed;"></i>Nueva Asignación — Otros Activos
    </h1>
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
    {{-- Columna principal --}}
    <div class="col-lg-8">

        {{-- Destinatario --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-user-tag mr-1 text-primary"></i> Destinatario
                </h6>
            </div>
            <div class="card-body">
                {{-- Toggle Colaborador / Área --}}
                <div class="mb-3">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" id="btnColaborador"
                                class="btn btn-primary recipient-toggle"
                                onclick="setRecipient('collaborator')">
                            <i class="fas fa-user mr-1"></i> Colaborador
                        </button>
                        <button type="button" id="btnArea"
                                class="btn btn-outline-secondary recipient-toggle"
                                onclick="setRecipient('area')">
                            <i class="fas fa-map-marker-alt mr-1"></i> Área / Espacio
                        </button>
                    </div>
                    <input type="hidden" name="recipient_type" id="recipientType" value="collaborator">
                </div>

                {{-- Panel Colaborador --}}
                <div id="panelColaborador">
                    <label class="text-muted small mb-1">Colaborador</label>
                    <select name="collaborator_id" class="form-control form-control-sm">
                        <option value="">— Seleccionar colaborador —</option>
                        @foreach($collaborators as $c)
                            <option value="{{ $c->id }}" {{ old('collaborator_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }}
                                @if($c->branch) — {{ $c->branch->name }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Panel Área --}}
                <div id="panelArea" style="display:none;">
                    <label class="text-muted small mb-1">Área / Espacio</label>
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
                        ¿No existe el área?
                        <a href="{{ route('areas.create') }}" target="_blank">Crear nueva área</a>
                    </small>
                </div>
            </div>
        </div>

        {{-- Activos a asignar --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i>
                    Activos a asignar
                    <span class="badge badge-secondary ml-1" id="selectedCount">0 seleccionados</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="px-3 py-2">
                    <input type="text" id="assetSearch" class="form-control form-control-sm"
                           placeholder="Buscar por código, tipo, marca...">
                </div>
                <div style="max-height:320px;overflow-y:auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead style="background:#f4f6f9;font-size:.75rem;text-transform:uppercase;">
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                        </tr>
                    </thead>
                    <tbody id="assetTableBody">
                        @forelse($assets as $asset)
                        <tr class="asset-row" data-search="{{ strtolower($asset->internal_code.' '.$asset->type?->name.' '.$asset->brand.' '.$asset->model) }}">
                            <td class="text-center">
                                <input type="checkbox" name="assets[]" value="{{ $asset->id }}"
                                       class="asset-check"
                                       {{ in_array($asset->id, old('assets', [])) ? 'checked' : '' }}>
                            </td>
                            <td><code style="font-size:.78rem;">{{ $asset->internal_code }}</code></td>
                            <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                            <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-2x mb-2 d-block" style="opacity:.3;"></i>
                                No hay activos de Otros Activos disponibles
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>

    {{-- Columna lateral --}}
    <div class="col-lg-4">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-calendar mr-1 text-primary"></i> Detalles
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label class="text-muted small mb-1">Fecha de asignación</label>
                    <input type="date" name="assignment_date"
                           class="form-control form-control-sm"
                           value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                </div>
                <div class="form-group mb-0">
                    <label class="text-muted small mb-1">Notas (opcional)</label>
                    <textarea name="notes" rows="3"
                              class="form-control form-control-sm"
                              placeholder="Observaciones de la asignación...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-check mr-1"></i> Crear Asignación
        </button>
    </div>
</div>

</form>
@stop

@section('js')
<script>
/* ── Toggle colaborador / área ─────────────────────── */
function setRecipient(type) {
    document.getElementById('recipientType').value = type;
    document.getElementById('panelColaborador').style.display = type === 'collaborator' ? '' : 'none';
    document.getElementById('panelArea').style.display        = type === 'area'         ? '' : 'none';
    document.getElementById('btnColaborador').className = 'btn btn-sm recipient-toggle ' + (type === 'collaborator' ? 'btn-primary' : 'btn-outline-secondary');
    document.getElementById('btnArea').className        = 'btn btn-sm recipient-toggle ' + (type === 'area'         ? 'btn-primary' : 'btn-outline-secondary');
}

/* ── Contador de activos seleccionados ─────────────── */
function updateCount() {
    const n = document.querySelectorAll('.asset-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
}
document.querySelectorAll('.asset-check').forEach(cb => cb.addEventListener('change', updateCount));
updateCount();

/* ── Búsqueda en tabla de activos ──────────────────── */
document.getElementById('assetSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>
@stop
