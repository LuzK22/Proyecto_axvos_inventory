@extends('adminlte::page')
@section('title', 'Nuevo Préstamo — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-plus-circle mr-2" style="color:#7c3aed;"></i>Nuevo Préstamo — Otros Activos
        </h1>
        <small class="text-muted">Registra un préstamo temporal de un activo general</small>
    </div>
    <a href="{{ route('assets.loans.hub') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<form method="POST" action="{{ route('assets.loans.store') }}">
@csrf
<div class="row">
    <div class="col-lg-7">

        {{-- Activo --}}
        <div class="card card-outline mb-3" style="border-color:#7c3aed;">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-box mr-1" style="color:#7c3aed;"></i> Activo a prestar
                </h6>
            </div>
            <div class="card-body">
                <input type="text" id="assetSearch" class="form-control form-control-sm mb-2"
                       placeholder="Buscar activo por código, marca, modelo...">
                <select name="asset_id" class="form-control form-control-sm" required id="assetSelect">
                    <option value="">— Seleccionar activo disponible —</option>
                    @foreach($assets as $a)
                    <option value="{{ $a->id }}"
                            data-search="{{ strtolower($a->internal_code.' '.$a->brand.' '.$a->model) }}"
                            {{ old('asset_id')==$a->id?'selected':'' }}>
                        {{ $a->internal_code }} — {{ $a->brand }} {{ $a->model }} ({{ $a->type?->name }})
                    </option>
                    @endforeach
                </select>
                @error('asset_id')<small class="text-danger">{{ $message }}</small>@enderror
                @if($assets->isEmpty())
                <div class="alert alert-warning mt-2 py-2 mb-0">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    No hay activos de Otros disponibles en este momento.
                </div>
                @endif
            </div>
        </div>

        {{-- Tipo de destino --}}
        <div class="card card-outline mb-3" style="border-color:#7c3aed;">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-map-marker-alt mr-1" style="color:#7c3aed;"></i> Destino del préstamo
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small mb-1">Tipo de destino <span class="text-danger">*</span></label>
                    <div class="d-flex" style="gap:16px;">
                        <div class="custom-control custom-radio">
                            <input type="radio" id="dest_collaborator" name="destination_type"
                                   value="collaborator" class="custom-control-input"
                                   {{ old('destination_type', 'collaborator') === 'collaborator' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dest_collaborator">
                                <i class="fas fa-user mr-1"></i> Colaborador
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="dest_branch" name="destination_type"
                                   value="branch" class="custom-control-input"
                                   {{ old('destination_type') === 'branch' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="dest_branch">
                                <i class="fas fa-building mr-1"></i> Sucursal
                            </label>
                        </div>
                    </div>
                    @error('destination_type')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- Selector colaborador --}}
                <div id="sectionCollaborator" class="destination-section">
                    <label class="text-muted small mb-1">Colaborador <span class="text-danger">*</span></label>
                    <select name="collaborator_id" class="form-control form-control-sm" id="collaboratorSelect">
                        <option value="">— Seleccionar colaborador —</option>
                        @foreach($collaborators as $c)
                        <option value="{{ $c->id }}" {{ old('collaborator_id')==$c->id?'selected':'' }}>
                            {{ $c->full_name }}{{ $c->position ? ' — '.$c->position : '' }}{{ $c->branch ? ' ('.$c->branch->name.')' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('collaborator_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                {{-- Selector sucursal --}}
                <div id="sectionBranch" class="destination-section" style="display:none;">
                    <label class="text-muted small mb-1">Sucursal destino <span class="text-danger">*</span></label>
                    <select name="destination_branch_id" class="form-control form-control-sm" id="branchSelect">
                        <option value="">— Seleccionar sucursal —</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('destination_branch_id')==$b->id?'selected':'' }}>
                            {{ $b->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('destination_branch_id')<small class="text-danger">{{ $message }}</small>@enderror
                    <small class="text-muted mt-1 d-block">
                        <i class="fas fa-info-circle mr-1"></i>
                        El activo será registrado en préstamo a esta sucursal temporalmente.
                    </small>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-5">
        {{-- Fechas y notas --}}
        <div class="card card-outline mb-3" style="border-color:#7c3aed;">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-calendar mr-1" style="color:#7c3aed;"></i> Fechas
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label class="text-muted small mb-1">Fecha de préstamo <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control form-control-sm"
                           value="{{ old('start_date', date('Y-m-d')) }}" required>
                    @error('start_date')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="form-group mb-2">
                    <label class="text-muted small mb-1">Fecha de devolución <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control form-control-sm"
                           value="{{ old('end_date') }}" required>
                    @error('end_date')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="form-group mb-0">
                    <label class="text-muted small mb-1">Observaciones</label>
                    <textarea name="notes" rows="3" class="form-control form-control-sm"
                              placeholder="Motivo del préstamo, condiciones...">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-block" style="background:#7c3aed;color:#fff;">
            <i class="fas fa-check mr-1"></i> Registrar Préstamo
        </button>

        <div class="card mt-3" style="border-left:4px solid #7c3aed;">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1" style="color:#7c3aed;"></i>
                    Solo se muestran activos de <strong>Otros</strong> con estado <strong>Disponible</strong>.
                </small>
            </div>
        </div>

        <div class="card mt-2 border-left-warning shadow-sm">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-file-alt mr-1 text-warning"></i>
                    <strong>Acta opcional</strong> — la empresa puede configurar si los préstamos de otros activos generan acta automáticamente.
                </small>
            </div>
        </div>
    </div>
</div>
</form>
@stop

@section('js')
<script>
(function() {
    var radios = document.querySelectorAll('input[name="destination_type"]');
    var secCollaborator = document.getElementById('sectionCollaborator');
    var secBranch       = document.getElementById('sectionBranch');
    var collaboratorSel = document.getElementById('collaboratorSelect');
    var branchSel       = document.getElementById('branchSelect');

    function toggleDestination() {
        var val = document.querySelector('input[name="destination_type"]:checked')?.value;
        if (val === 'branch') {
            secCollaborator.style.display = 'none';
            secBranch.style.display       = 'block';
            collaboratorSel.removeAttribute('required');
            branchSel.setAttribute('required', 'required');
        } else {
            secCollaborator.style.display = 'block';
            secBranch.style.display       = 'none';
            branchSel.removeAttribute('required');
            collaboratorSel.setAttribute('required', 'required');
        }
    }

    radios.forEach(function(r) { r.addEventListener('change', toggleDestination); });
    toggleDestination();

    // Asset search filter
    document.getElementById('assetSearch').addEventListener('input', function() {
        var q = this.value.toLowerCase();
        Array.from(document.getElementById('assetSelect').options).forEach(function(opt) {
            if (!opt.value) return;
            opt.hidden = !opt.dataset.search.includes(q);
        });
    });
})();
</script>
@stop
