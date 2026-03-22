@extends('adminlte::page')
@section('title', 'Registrar Devolución — Préstamo #' . $loan->id)

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark">
        <i class="fas fa-undo mr-2 text-warning"></i>Registrar Devolución
        <small class="text-muted" style="font-size:.7em;">Préstamo #{{ $loan->id }}</small>
    </h1>
    <a href="{{ route('tech.loans.show', $loan) }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3" style="border-top:4px solid #f59e0b;">
            <div class="card-body">
                <p class="mb-1 text-muted small text-uppercase font-weight-bold">Activo a devolver</p>
                <p class="font-weight-bold mb-1">
                    <code>{{ $loan->asset?->internal_code }}</code>
                    — {{ $loan->asset?->brand }} {{ $loan->asset?->model }}
                </p>
                <p class="mb-0">
                    <i class="fas fa-user mr-1 text-muted"></i>
                    {{ $loan->collaborator?->full_name }}
                    <span class="text-muted ml-2">· Vence: {{ $loan->end_date->format('d/m/Y') }}</span>
                    @if($loan->status==='vencido')<span class="badge badge-danger ml-1">Vencido</span>@endif
                </p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('tech.loans.return.store', $loan) }}">
                @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Observaciones de la devolución</label>
                        <textarea name="notes" rows="4" class="form-control"
                                  placeholder="Estado del activo al devolverse, daños, observaciones...">{{ old('notes', $loan->notes) }}</textarea>
                    </div>
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-clock mr-1"></i>
                        Devolución registrada con fecha y hora:
                        <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                    </div>
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('tech.loans.show', $loan) }}" class="btn btn-secondary mr-2">Cancelar</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo mr-1"></i> Confirmar Devolución
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
