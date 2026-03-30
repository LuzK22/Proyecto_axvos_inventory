@extends('adminlte::page')

@section('title', 'Registrar Devolución')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-undo text-warning mr-2"></i> Registrar Devolución
        </h1>
        <a href="{{ route('tech.assignments.show', $assignment) }}" class="btn btn-secondary">
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

<div class="row">

    {{-- Info del colaborador --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Colaborador</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre:</dt>
                    <dd class="col-sm-7"><strong>{{ $assignment->collaborator->full_name }}</strong></dd>
                    <dt class="col-sm-5">Cédula:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->document }}</dd>
                    <dt class="col-sm-5">Cargo:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->position ?? '-' }}</dd>
                    <dt class="col-sm-5">Sucursal:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->branch?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Asignación:</dt>
                    <dd class="col-sm-7">#{{ $assignment->id }} — {{ $assignment->assignment_date->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Activos a devolver --}}
    <div class="col-md-8">
        <form method="POST" action="{{ route('tech.assignments.return.store', $assignment) }}">
        @csrf

        <div class="card card-outline card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-boxes mr-1"></i> Seleccionar Activos a Devolver
                </h3>
                <div class="card-tools">
                    <span id="returnCount" class="badge badge-warning">0 seleccionados</span>
                </div>
            </div>
            <div class="card-body">

                <div class="callout callout-warning mb-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    Puede devolver uno o varios activos. Los activos devueltos quedarán
                    <strong>Disponibles</strong> nuevamente.
                </div>

                {{-- Selección rápida --}}
                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-warning" id="selectAllReturn">
                        <i class="fas fa-check-double mr-1"></i> Seleccionar todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="clearAllReturn">
                        <i class="fas fa-times mr-1"></i> Limpiar
                    </button>
                </div>

                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="50"></th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignment->activeAssets as $aa)
                            <tr>
                                <td>
                                    <input type="checkbox" name="asset_ids[]"
                                           value="{{ $aa->asset_id }}"
                                           class="return-checkbox"
                                           {{ in_array($aa->asset_id, old('asset_ids', $preselectedAssetIds ?? [])) ? 'checked' : '' }}>
                                </td>
                                <td><code>{{ $aa->asset->internal_code }}</code></td>
                                <td>{{ $aa->asset->type?->name }}</td>
                                <td>{{ $aa->asset->brand }} {{ $aa->asset->model }}</td>
                                <td><small>{{ $aa->asset->serial }}</small></td>
                                <td><small>{{ $aa->assigned_at?->format('d/m/Y') }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="form-group">
                    <label>Observaciones de la Devolución</label>
                    <textarea name="return_notes" class="form-control" rows="3"
                              placeholder="Estado del equipo al momento de la devolución, observaciones...">{{ old('return_notes') }}</textarea>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <a href="{{ route('tech.assignments.show', $assignment) }}" class="btn btn-secondary mr-2">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-warning" id="returnBtn" disabled>
                    <i class="fas fa-undo mr-1"></i> Confirmar Devolución
                </button>
            </div>
        </div>

        </form>
    </div>

</div>

@stop

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes  = document.querySelectorAll('.return-checkbox');
    const returnBtn   = document.getElementById('returnBtn');
    const returnCount = document.getElementById('returnCount');

    function update() {
        const checked = document.querySelectorAll('.return-checkbox:checked').length;
        returnCount.textContent = checked + ' seleccionado(s)';
        returnBtn.disabled = checked === 0;
    }

    checkboxes.forEach(cb => cb.addEventListener('change', update));

    document.getElementById('selectAllReturn').addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
        update();
    });

    document.getElementById('clearAllReturn').addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
        update();
    });
});
</script>
@stop
