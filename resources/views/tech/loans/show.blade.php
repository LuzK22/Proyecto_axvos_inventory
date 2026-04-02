@extends('adminlte::page')
@section('title', 'Préstamo #' . $loan->id)

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0">
        <i class="fas fa-handshake text-primary mr-2"></i>
        Préstamo #{{ $loan->id }}
        @php $sc = match($loan->status){'activo'=>'success','vencido'=>'danger',default=>'secondary'}; @endphp
        <span class="badge badge-{{ $sc }} ml-1" style="font-size:.6rem;">{{ ucfirst($loan->status) }}</span>
    </h1>
    <div>
        @if($loan->status !== 'devuelto')
            <a href="{{ route('tech.loans.return', $loan) }}" class="btn btn-sm btn-warning mr-1">
                <i class="fas fa-undo mr-1"></i> Registrar Devolución
            </a>
        @endif
        <a href="{{ route('tech.loans.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

@if($loan->status === 'vencido')
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    <strong>Préstamo vencido.</strong> La fecha de devolución era {{ $loan->end_date->format('d/m/Y') }}
    ({{ abs($loan->daysRemaining()) }} día(s) de atraso).
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3" style="border-left:4px solid #1e3a8a;">
            <div class="card-header py-2"><h6 class="mb-0 font-weight-bold"><i class="fas fa-laptop mr-1" style="color:#1e3a8a;"></i> Activo</h6></div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted pl-0" style="width:35%;font-size:.82rem;">Código</td><td><code>{{ $loan->asset->internal_code }}</code></td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Tipo</td><td>{{ $loan->asset->type?->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Marca / Modelo</td><td>{{ $loan->asset->brand }} {{ $loan->asset->model }}</td></tr>
                    @if($loan->asset->serial)
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Serial</td><td><code style="font-size:.8rem;">{{ $loan->asset->serial }}</code></td></tr>
                    @endif
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Sucursal</td><td>{{ $loan->asset->branch?->name ?? '—' }}</td></tr>
                </table>
                <div class="mt-2">
                    <a href="{{ route('tech.assets.show', $loan->asset) }}" class="btn btn-xs btn-outline-primary">
                        <i class="fas fa-eye mr-1"></i> Ver activo
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3" style="border-left:4px solid #059669;">
            <div class="card-header py-2"><h6 class="mb-0 font-weight-bold"><i class="fas fa-user mr-1" style="color:#059669;"></i> Colaborador</h6></div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted pl-0" style="width:35%;font-size:.82rem;">Nombre</td><td class="font-weight-bold">{{ $loan->collaborator->full_name }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Cédula</td><td>{{ $loan->collaborator->document }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Cargo</td><td>{{ $loan->collaborator->position ?? '—' }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Sucursal</td><td>{{ $loan->collaborator->branch?->name ?? '—' }}</td></tr>
                    @if($loan->collaborator->email)
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Correo</td><td><small>{{ $loan->collaborator->email }}</small></td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3" style="border-left:4px solid #f59e0b;">
            <div class="card-header py-2"><h6 class="mb-0 font-weight-bold"><i class="fas fa-calendar mr-1" style="color:#f59e0b;"></i> Fechas</h6></div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted pl-0" style="width:40%;font-size:.82rem;">Inicio</td><td>{{ $loan->start_date->format('d/m/Y') }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Devolución pactada</td>
                        <td class="{{ $loan->status==='vencido'?'text-danger font-weight-bold':'' }}">{{ $loan->end_date->format('d/m/Y') }}</td></tr>
                    @if($loan->returned_at)
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Devuelto el</td>
                        <td class="text-success font-weight-bold">{{ $loan->returned_at->format('d/m/Y H:i') }}</td></tr>
                    @endif
                    @if($loan->status === 'activo')
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Días restantes</td>
                        <td><span class="badge badge-info">{{ $loan->daysRemaining() }} día(s)</span></td></tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3" style="border-left:4px solid #6c757d;">
            <div class="card-header py-2"><h6 class="mb-0 font-weight-bold"><i class="fas fa-info-circle mr-1 text-muted"></i> Registro</h6></div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted pl-0" style="width:40%;font-size:.82rem;">Registrado por</td><td>{{ $loan->creator?->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Fecha registro</td><td>{{ $loan->created_at->format('d/m/Y H:i') }}</td></tr>
                    @if($loan->returnedBy)
                    <tr><td class="text-muted pl-0" style="font-size:.82rem;">Devolución por</td><td>{{ $loan->returnedBy->name }}</td></tr>
                    @endif
                </table>
                @if($loan->notes)
                <div class="mt-2 p-2 bg-light rounded">
                    <small class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;">Notas</small>
                    <small>{{ $loan->notes }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Carta / Acta de Préstamo ──────────────────────────────────── --}}
@php $loanActas = $loan->relationLoaded('actas') ? $loan->actas : collect(); @endphp
<div class="card shadow-sm mb-3" style="border-left:4px solid #1d4ed8;">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-file-contract mr-1" style="color:#1d4ed8;"></i> Carta de Préstamo
        </h6>
        @if($loanActas->isEmpty())
        <form method="POST" action="{{ route('actas.generate.from.loan', $loan) }}">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary" style="font-size:.78rem;">
                <i class="fas fa-plus mr-1"></i> Generar Carta
            </button>
        </form>
        @else
        <a href="{{ route('actas.index', ['type' => 'prestamo', 'category' => 'TI']) }}"
           class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">
            <i class="fas fa-list mr-1"></i> Ver todas
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        @if($loanActas->isEmpty())
        <div class="py-3 px-3 text-muted small d-flex align-items-center" style="gap:10px;">
            <i class="fas fa-info-circle" style="color:#93c5fd;font-size:1.1rem;"></i>
            <span>No se ha generado carta de préstamo. Puede generarla opcionalmente.</span>
        </div>
        @else
        <table class="table table-sm mb-0">
            <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                <tr>
                    <th class="pl-3">Nº Acta</th>
                    <th>Estado</th>
                    <th>Firmas</th>
                    <th>Generada</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($loanActas as $acta)
                <tr>
                    <td class="pl-3 font-weight-bold" style="font-size:.82rem;">{{ $acta->acta_number }}</td>
                    <td>
                        <span class="badge badge-{{ $acta->status_color }}" style="font-size:.65rem;">
                            {{ $acta->status_label }}
                        </span>
                    </td>
                    <td>
                        @php $signed = $acta->signatures->where('signed_at','!=',null)->count(); $total = $acta->signatures->count(); @endphp
                        <span class="badge {{ $signed===$total && $total>0 ? 'badge-success' : 'badge-warning' }}" style="font-size:.65rem;">
                            {{ $signed }}/{{ $total }}
                        </span>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;">{{ $acta->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('actas.show', $acta) }}" class="btn btn-xs btn-outline-primary" title="Ver acta">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@stop

@section('css')
<style>
.table-borderless td{border:none!important;padding:.2rem .5rem;}
</style>
@stop
