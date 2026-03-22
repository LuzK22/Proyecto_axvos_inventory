@extends('adminlte::page')
@section('title', 'Nuevo Préstamo TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark"><i class="fas fa-plus-circle mr-2 text-primary"></i>Nuevo Préstamo TI</h1>
        <small class="text-muted">Registra un préstamo temporal de un activo tecnológico</small>
    </div>
    <a href="{{ route('tech.loans.hub') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<form method="POST" action="{{ route('tech.loans.store') }}">
@csrf
<div class="row">
    <div class="col-lg-7">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-laptop mr-1 text-primary"></i> Activo a prestar</h6>
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
                    No hay activos TI disponibles en este momento.
                </div>
                @endif
            </div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-user mr-1 text-primary"></i> Colaborador</h6>
            </div>
            <div class="card-body">
                <select name="collaborator_id" class="form-control form-control-sm" required>
                    <option value="">— Seleccionar colaborador —</option>
                    @foreach($collaborators as $c)
                    <option value="{{ $c->id }}" {{ old('collaborator_id')==$c->id?'selected':'' }}>
                        {{ $c->full_name }}{{ $c->position ? ' — '.$c->position : '' }}{{ $c->branch ? ' ('.$c->branch->name.')' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('collaborator_id')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-calendar mr-1 text-primary"></i> Fechas</h6>
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

        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-check mr-1"></i> Registrar Préstamo
        </button>

        <div class="card mt-3 border-left-warning shadow-sm">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1 text-warning"></i>
                    Solo se muestran activos TI con estado <strong>Disponible</strong>.
                </small>
            </div>
        </div>
    </div>
</div>
</form>
@stop

@section('js')
<script>
document.getElementById('assetSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    Array.from(document.getElementById('assetSelect').options).forEach(opt => {
        if (!opt.value) return;
        opt.hidden = !opt.dataset.search.includes(q);
    });
});
</script>
@stop
