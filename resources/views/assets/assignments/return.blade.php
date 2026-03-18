@extends('adminlte::page')
@section('title', 'Registrar Devolución')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark" style="font-size:1.3rem;">
        <i class="fas fa-undo mr-2 text-warning"></i>Registrar Devolución — Asignación #{{ $assignment->id }}
    </h1>
    <a href="{{ route('assets.assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<form method="POST" action="{{ route('assets.assignments.return.process', $assignment) }}">
@csrf
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-warning mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i> Selecciona activos a devolver
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.75rem;text-transform:uppercase;">
                        <tr>
                            <th style="width:40px;" class="pl-3">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignment->assignmentAssets->whereNull('returned_at') as $aa)
                        <tr>
                            <td class="pl-3 py-2">
                                <input type="checkbox" name="assets[]" value="{{ $aa->id }}" checked>
                            </td>
                            <td class="py-2"><code style="font-size:.8rem;">{{ $aa->asset->internal_code }}</code></td>
                            <td class="py-2"><small>{{ $aa->asset->type?->name ?? '—' }}</small></td>
                            <td class="py-2"><small>{{ $aa->asset->brand }} {{ $aa->asset->model }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-group">
            <label class="text-muted small mb-1">Notas de devolución (opcional)</label>
            <textarea name="notes" rows="2" class="form-control form-control-sm"
                      placeholder="Observaciones...">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="btn btn-warning btn-sm">
            <i class="fas fa-undo mr-1"></i> Confirmar Devolución
        </button>
    </div>
</div>
</form>
@stop

@section('js')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('input[name="assets[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
@stop
