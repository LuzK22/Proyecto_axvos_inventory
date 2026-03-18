@extends('adminlte::page')

@section('title', 'Nueva Asignación TI')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-user-plus text-primary mr-2"></i> Nueva Asignación TI</h1>
        <a href="{{ route('tech.assignments.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('tech.assignments.store') }}" id="assignmentForm">
@csrf

<div class="row">

    {{-- ── COLUMNA IZQUIERDA: Colaborador ────────────────────────────── --}}
    <div class="col-md-5">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Datos del Colaborador</h3>
            </div>
            <div class="card-body">

                {{-- Búsqueda de colaborador --}}
                <div class="form-group">
                    <label>Buscar Colaborador <span class="text-danger">*</span></label>
                    <select name="collaborator_id" id="collaborator_id" class="form-control" required>
                        <option value="">Seleccione un colaborador...</option>
                        @foreach($collaborators as $c)
                            <option value="{{ $c->id }}"
                                data-modality="{{ $c->modalidad_trabajo }}"
                                data-name="{{ $c->full_name }}"
                                data-doc="{{ $c->document }}"
                                data-position="{{ $c->position }}"
                                data-area="{{ $c->area }}"
                                data-branch="{{ $c->branch?->name }}"
                                {{ old('collaborator_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }} — CC {{ $c->document }}
                            </option>
                        @endforeach
                    </select>
                    @error('collaborator_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Info del colaborador (se llena con JS) --}}
                <div id="collaboratorInfo" class="d-none">
                    <div class="callout callout-info">
                        <p class="mb-1"><strong><i class="fas fa-id-card mr-1"></i> CC:</strong> <span id="infoDoc">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-briefcase mr-1"></i> Cargo:</strong> <span id="infoPosition">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-building mr-1"></i> Área:</strong> <span id="infoArea">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-map-marker-alt mr-1"></i> Sucursal:</strong> <span id="infoBranch">-</span></p>
                        <p class="mb-0"><strong><i class="fas fa-home mr-1"></i> Modalidad:</strong>
                            <span id="infoModality" class="badge">-</span>
                        </p>
                    </div>
                    {{-- Aviso de modalidad remoto --}}
                    <div id="remoteAlert" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Colaborador REMOTO:</strong> Solo se recomienda asignar portátil, cargador y diadema.
                        Los demás equipos quedan en el puesto a cargo del supervisor.
                    </div>
                </div>

                {{-- Fecha y notas --}}
                <div class="form-group mt-3">
                    <label>Fecha de Asignación <span class="text-danger">*</span></label>
                    <input type="date" name="assignment_date" class="form-control"
                           value="{{ old('assignment_date', date('Y-m-d')) }}" required>
                    @error('assignment_date')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Estado de los equipos, accesorios incluidos...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── COLUMNA DERECHA: Activos ───────────────────────────────────── --}}
    <div class="col-md-7">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-laptop mr-1"></i> Seleccionar Activos TI</h3>
                <div class="card-tools">
                    <span class="badge badge-success" id="selectedCount">0 seleccionados</span>
                </div>
            </div>
            <div class="card-body">

                {{-- Filtro rápido --}}
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="assetSearch" class="form-control"
                           placeholder="Filtrar por código, tipo, marca, serial...">
                </div>

                @error('asset_ids')
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                    </div>
                @enderror

                @if($availableAssets->isEmpty())
                    <div class="text-center text-muted p-4">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        No hay activos TI disponibles para asignar.
                    </div>
                @else
                    <div class="table-responsive" style="max-height:380px;overflow-y:auto;">
                        <table class="table table-sm table-hover" id="assetsTable">
                            <thead class="thead-light sticky-top">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" title="Seleccionar todos">
                                    </th>
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th>Marca / Modelo</th>
                                    <th>Serial</th>
                                    <th>Sucursal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableAssets as $asset)
                                    <tr class="asset-row">
                                        <td>
                                            <input type="checkbox" name="asset_ids[]"
                                                   value="{{ $asset->id }}"
                                                   class="asset-checkbox"
                                                   {{ in_array($asset->id, (array) old('asset_ids', [])) ? 'checked' : '' }}>
                                        </td>
                                        <td><code>{{ $asset->internal_code }}</code></td>
                                        <td>{{ $asset->type?->name }}</td>
                                        <td>{{ $asset->brand }} {{ $asset->model }}</td>
                                        <td><small>{{ $asset->serial }}</small></td>
                                        <td><small>{{ $asset->branch?->name }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- Botones --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-end gap-2">
                <a href="{{ route('tech.assignments.index') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <i class="fas fa-save mr-1"></i> Guardar Asignación
                </button>
            </div>
        </div>
    </div>
</div>

</form>
@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const collaboratorSelect = document.getElementById('collaborator_id');
    const collaboratorInfo   = document.getElementById('collaboratorInfo');
    const remoteAlert        = document.getElementById('remoteAlert');
    const submitBtn          = document.getElementById('submitBtn');
    const checkboxes         = document.querySelectorAll('.asset-checkbox');
    const selectedCount      = document.getElementById('selectedCount');
    const selectAll          = document.getElementById('selectAll');
    const assetSearch        = document.getElementById('assetSearch');

    // ── Mostrar info del colaborador al seleccionar ──────────────────────
    collaboratorSelect.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        if (!opt.value) {
            collaboratorInfo.classList.add('d-none');
            return;
        }
        collaboratorInfo.classList.remove('d-none');
        document.getElementById('infoDoc').textContent      = opt.dataset.doc      || '-';
        document.getElementById('infoPosition').textContent  = opt.dataset.position  || '-';
        document.getElementById('infoArea').textContent      = opt.dataset.area      || '-';
        document.getElementById('infoBranch').textContent    = opt.dataset.branch    || '-';

        const mod = opt.dataset.modality || 'presencial';
        const modEl = document.getElementById('infoModality');
        const labels = { remoto: 'Remoto', hibrido: 'Híbrido', presencial: 'Presencial' };
        const classes = { remoto: 'badge-info', hibrido: 'badge-warning text-dark', presencial: 'badge-success' };
        modEl.textContent  = labels[mod]  || mod;
        modEl.className    = 'badge ' + (classes[mod] || 'badge-secondary');

        remoteAlert.classList.toggle('d-none', mod !== 'remoto');
        updateSubmit();
    });

    // ── Contador de activos seleccionados ────────────────────────────────
    function updateSubmit() {
        const checked = document.querySelectorAll('.asset-checkbox:checked').length;
        selectedCount.textContent = checked + ' seleccionado(s)';
        submitBtn.disabled = !(checked > 0 && collaboratorSelect.value);
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateSubmit));

    // ── Seleccionar todos ────────────────────────────────────────────────
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.asset-row:not([style*="display:none"]) .asset-checkbox')
                .forEach(cb => cb.checked = this.checked);
            updateSubmit();
        });
    }

    // ── Filtro de búsqueda ───────────────────────────────────────────────
    if (assetSearch) {
        assetSearch.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('.asset-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
            });
        });
    }

    // Inicializar estado si hay old() values
    updateSubmit();
    if (collaboratorSelect.value) {
        collaboratorSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@stop
