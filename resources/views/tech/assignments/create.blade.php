@extends('adminlte::page')

@section('title', 'Nueva Asignacion TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0"><i class="fas fa-user-plus text-primary mr-2"></i> Nueva Asignacion TI</h1>
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
    <div class="col-md-5">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Datos del Colaborador</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Buscar Colaborador <span class="text-danger">*</span></label>
                    <input type="text" id="collaboratorSearch" class="form-control form-control-sm mb-2"
                           placeholder="Buscar por nombre...">
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
                                {{ (string) old('collaborator_id', request('collaborator_id')) === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->full_name }} - CC {{ $c->document }}
                            </option>
                        @endforeach
                    </select>
                    @error('collaborator_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div id="collaboratorInfo" class="d-none">
                    <div class="callout callout-info">
                        <p class="mb-1"><strong><i class="fas fa-id-card mr-1"></i> CC:</strong> <span id="infoDoc">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-briefcase mr-1"></i> Cargo:</strong> <span id="infoPosition">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-building mr-1"></i> Area:</strong> <span id="infoArea">-</span></p>
                        <p class="mb-1"><strong><i class="fas fa-map-marker-alt mr-1"></i> Sucursal:</strong> <span id="infoBranch">-</span></p>
                        <p class="mb-0"><strong><i class="fas fa-home mr-1"></i> Modalidad:</strong>
                            <span id="infoModality" class="badge">-</span>
                        </p>
                    </div>
                    <div id="remoteAlert" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Colaborador remoto:</strong> se recomienda portatil, cargador y diadema.
                    </div>
                    <div id="templateSuggestion" class="alert alert-light border d-none mb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><i class="fas fa-layer-group mr-1 text-primary"></i> Plantilla sugerida</strong>
                                <div id="templateName" class="small font-weight-bold mt-1"></div>
                                <div id="templateItems" class="small text-muted"></div>
                            </div>
                            <span id="templateBadge" class="badge badge-primary">Modalidad</span>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-3">
                    <label>Destino de la Asignacion <span class="text-danger">*</span></label>
                    <select name="destination_type" id="destination_type" class="form-control mb-2" required>
                        <option value="collaborator" {{ old('destination_type', 'collaborator') === 'collaborator' ? 'selected' : '' }}>Colaborador</option>
                        <option value="jefe" {{ old('destination_type') === 'jefe' ? 'selected' : '' }}>Jefe / Responsable</option>
                        <option value="area" {{ old('destination_type') === 'area' ? 'selected' : '' }}>Area Compartida</option>
                        <option value="pool" {{ old('destination_type') === 'pool' ? 'selected' : '' }}>Pool Compartido</option>
                    </select>
                    <select name="area_id" id="area_id" class="form-control d-none">
                        <option value="">Seleccione area...</option>
                        @foreach($areas as $area)
                            <option value="{{ $area->id }}" {{ (string) old('area_id') === (string) $area->id ? 'selected' : '' }}>
                                {{ $area->name }}{{ $area->branch ? ' - ' . $area->branch->name : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('destination_type')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                    @error('area_id')
                        <small class="text-danger d-block">{{ $message }}</small>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label>Fecha de Asignacion <span class="text-danger">*</span></label>
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

    <div class="col-md-7">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-laptop mr-1"></i> Seleccionar Activos TI</h3>
                <div class="card-tools">
                    <span class="badge badge-success" id="selectedCount">0 seleccionados</span>
                </div>
            </div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" id="assetSearch" class="form-control"
                           placeholder="Filtrar por codigo, tipo, marca, serial...">
                </div>
                <div class="mb-3">
                    <select id="assetSubcategoryFilter" class="form-control form-control-sm">
                        <option value="">Todas las subcategorias</option>
                        @foreach($availableAssets->pluck('type.subcategory')->filter()->unique()->sort()->values() as $sub)
                            <option value="{{ strtolower($sub) }}">{{ $sub }}</option>
                        @endforeach
                    </select>
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
                                    <th width="40"><input type="checkbox" id="selectAll" title="Seleccionar todos"></th>
                                    <th>Codigo</th>
                                    <th>Tipo</th>
                                    <th>Marca / Modelo</th>
                                    <th>Serial</th>
                                    <th>Sucursal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableAssets as $asset)
                                    <tr class="asset-row" data-subcategory="{{ strtolower($asset->type?->subcategory ?? '') }}">
                                        <td>
                                            <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}"
                                                   class="asset-checkbox"
                                                   {{ in_array($asset->id, (array) old('asset_ids', [])) ? 'checked' : '' }}>
                                        </td>
                                        <td><code>{{ $asset->internal_code }}</code></td>
                                        <td>
                                            {{ $asset->type?->name }}
                                            @if($asset->type?->subcategory)
                                                <br><small class="text-muted">{{ $asset->type->subcategory }}</small>
                                            @endif
                                        </td>
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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-end gap-2">
                <a href="{{ route('tech.assignments.index') }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    <i class="fas fa-save mr-1"></i> Guardar Asignacion
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
    const collaboratorSearch = document.getElementById('collaboratorSearch');
    const collaboratorInfo = document.getElementById('collaboratorInfo');
    const remoteAlert = document.getElementById('remoteAlert');
    const templateSuggestion = document.getElementById('templateSuggestion');
    const templateName = document.getElementById('templateName');
    const templateItems = document.getElementById('templateItems');
    const submitBtn = document.getElementById('submitBtn');
    const checkboxes = document.querySelectorAll('.asset-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');
    const assetSearch = document.getElementById('assetSearch');
    const assetSubcategoryFilter = document.getElementById('assetSubcategoryFilter');
    const templateTypeId = {{ $modalityAssignmentType?->id ?? 'null' }};
    const destinationType = document.getElementById('destination_type');
    const areaSelect = document.getElementById('area_id');

    function syncDestinationFields() {
        const requiresArea = ['area', 'pool'].includes(destinationType.value);
        areaSelect.classList.toggle('d-none', !requiresArea);
        areaSelect.required = requiresArea;
        if (!requiresArea) areaSelect.value = '';
    }

    function updateSubmit() {
        const checked = document.querySelectorAll('.asset-checkbox:checked').length;
        selectedCount.textContent = checked + ' seleccionado(s)';
        submitBtn.disabled = !(checked > 0 && collaboratorSelect.value);
    }

    async function loadTemplateByModality(modality) {
        if (!templateTypeId || !modality) {
            templateSuggestion.classList.add('d-none');
            return;
        }
        const url = `{{ route('api.assignment-templates.for-value') }}?type_id=${templateTypeId}&value=${encodeURIComponent(modality)}`;
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                templateSuggestion.classList.add('d-none');
                return;
            }
            const data = await response.json();
            if (!data || !Array.isArray(data.items) || data.items.length === 0) {
                templateSuggestion.classList.add('d-none');
                return;
            }

            templateName.textContent = data.name;
            const resumen = data.items.map(i => `${i.quantity}x ${i.asset_type_name}`).join(', ');
            templateItems.textContent = resumen;
            templateSuggestion.classList.remove('d-none');
        } catch (e) {
            templateSuggestion.classList.add('d-none');
        }
    }

    collaboratorSelect.addEventListener('change', function () {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.value) {
            collaboratorInfo.classList.add('d-none');
            templateSuggestion.classList.add('d-none');
            updateSubmit();
            return;
        }

        collaboratorInfo.classList.remove('d-none');
        document.getElementById('infoDoc').textContent = opt.dataset.doc || '-';
        document.getElementById('infoPosition').textContent = opt.dataset.position || '-';
        document.getElementById('infoArea').textContent = opt.dataset.area || '-';
        document.getElementById('infoBranch').textContent = opt.dataset.branch || '-';

        const mod = opt.dataset.modality || 'presencial';
        const modEl = document.getElementById('infoModality');
        const labels = { remoto: 'Remoto', hibrido: 'Hibrido', presencial: 'Presencial' };
        const classes = { remoto: 'badge-info', hibrido: 'badge-warning text-dark', presencial: 'badge-success' };
        modEl.textContent = labels[mod] || mod;
        modEl.className = 'badge ' + (classes[mod] || 'badge-secondary');

        remoteAlert.classList.toggle('d-none', mod !== 'remoto');
        loadTemplateByModality(mod);
        updateSubmit();
    });

    collaboratorSearch.addEventListener('input', function () {
        const val = this.value.toLowerCase().trim();
        const opts = Array.from(collaboratorSelect.options);
        opts.forEach((opt, idx) => {
            if (idx === 0) return;
            const match = opt.text.toLowerCase().includes(val);
            opt.style.display = match ? '' : 'none';
            // También usamos hidden para compatibilidad
            opt.hidden = !match;
        });
        // Si hay solo un resultado visible, auto-seleccionarlo
        const visible = opts.filter((o, i) => i > 0 && !o.hidden);
        if (visible.length === 1 && val.length >= 3) {
            collaboratorSelect.value = visible[0].value;
            collaboratorSelect.dispatchEvent(new Event('change'));
        } else if (val === '') {
            collaboratorSelect.value = '';
            collaboratorInfo.classList.add('d-none');
            updateSubmit();
        }
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateSubmit));

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.asset-row:not([style*="display:none"]) .asset-checkbox')
                .forEach(cb => cb.checked = this.checked);
            updateSubmit();
        });
    }

    function applyAssetFilters() {
        const text = (assetSearch?.value || '').toLowerCase();
        const sub = (assetSubcategoryFilter?.value || '').toLowerCase();
        document.querySelectorAll('.asset-row').forEach(row => {
            const matchesText = row.textContent.toLowerCase().includes(text);
            const rowSub = (row.dataset.subcategory || '').toLowerCase();
            const matchesSub = !sub || rowSub === sub;
            row.style.display = (matchesText && matchesSub) ? '' : 'none';
        });
    }

    if (assetSearch) {
        assetSearch.addEventListener('input', applyAssetFilters);
    }
    if (assetSubcategoryFilter) {
        assetSubcategoryFilter.addEventListener('change', applyAssetFilters);
    }

    if (destinationType) {
        destinationType.addEventListener('change', syncDestinationFields);
        syncDestinationFields();
    }

    updateSubmit();
    if (collaboratorSelect.value) {
        collaboratorSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@stop
