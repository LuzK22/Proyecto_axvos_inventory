@extends('adminlte::page')
@section('title', 'Asignación #' . $assignment->id . ' — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark" style="font-size:1.3rem;">
        <i class="fas fa-boxes mr-2" style="color:#7c3aed;"></i>
        Asignación #{{ $assignment->id }}
        <span class="badge ml-1 {{ $assignment->status === 'activa' ? 'badge-success' : 'badge-secondary' }}" style="font-size:.7rem;">
            {{ ucfirst($assignment->status) }}
        </span>
    </h1>
    <div class="d-flex">
        @can('assets.assign')
        @if($assignment->status === 'activa')
            {{-- Solo botón Acta Otros Activos --}}
            <form method="POST" action="{{ route('actas.generate', $assignment) }}" class="d-inline">
                @csrf
                <input type="hidden" name="category" value="OTRO">
                <button type="submit" class="btn btn-sm mr-1"
                        style="background:#7c3aed;color:#fff;border:none;">
                    <i class="fas fa-file-signature mr-1"></i> Generar Acta
                </button>
            </form>
            <a href="{{ route('assets.assignments.return', $assignment) }}"
               class="btn btn-sm btn-warning mr-1">
                <i class="fas fa-undo mr-1"></i> Registrar Devolución
            </a>
        @endif
        @endcan
        <a href="{{ route('assets.assignments.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="row">
    {{-- Columna principal --}}
    <div class="col-lg-8">

        {{-- Activos asignados --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i> Activos Asignados
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.75rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignment->assignmentAssets as $aa)
                        <tr class="{{ $aa->returned_at ? 'text-muted' : '' }}">
                            <td class="pl-3 py-2">
                                <code style="font-size:.8rem;">{{ $aa->asset->internal_code }}</code>
                            </td>
                            <td class="py-2"><small>{{ $aa->asset->type?->name ?? '—' }}</small></td>
                            <td class="py-2"><small>{{ $aa->asset->brand }} {{ $aa->asset->model }}</small></td>
                            <td class="py-2">
                                @if($aa->returned_at)
                                    <span class="badge badge-secondary" style="font-size:.65rem;">
                                        <i class="fas fa-undo mr-1"></i>
                                        Devuelto {{ $aa->returned_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="badge badge-success" style="font-size:.65rem;">
                                        <i class="fas fa-check mr-1"></i>Asignado
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actas generadas --}}
        @if($assignment->actas->count())
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-file-signature mr-1 text-teal" style="color:#0f766e;"></i> Actas Generadas
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead style="background:#f4f6f9;font-size:.75rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Número</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignment->actas as $acta)
                        <tr>
                            <td class="pl-3 py-2"><small class="font-monospace">{{ $acta->acta_number }}</small></td>
                            <td class="py-2"><small>{{ ucfirst($acta->acta_type) }}</small></td>
                            <td class="py-2">
                                <span class="badge badge-{{ $acta->status_color ?? 'secondary' }}" style="font-size:.65rem;">
                                    {{ $acta->status_label ?? $acta->status }}
                                </span>
                            </td>
                            <td class="py-2"><small>{{ $acta->created_at->format('d/m/Y') }}</small></td>
                            <td class="py-2">
                                <a href="{{ route('actas.show', $acta) }}" class="btn btn-xs btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    {{-- Columna lateral --}}
    <div class="col-lg-4">

        {{-- Destinatario --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-user-tag mr-1 text-primary"></i> Destinatario
                </h6>
            </div>
            <div class="card-body">
                @if($assignment->collaborator_id)
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white mr-3"
                             style="width:38px;height:38px;background:#0d6efd;font-size:.7rem;font-weight:bold;flex-shrink:0;">
                            {{ strtoupper(substr($assignment->collaborator->full_name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-weight-bold">{{ $assignment->collaborator->full_name }}</div>
                            <small class="text-muted">{{ $assignment->collaborator->position ?? '' }}</small>
                        </div>
                    </div>
                    <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                        <tr><td class="text-muted pl-0" style="width:90px;">Sucursal</td><td>{{ $assignment->collaborator->branch?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted pl-0">Área</td><td>{{ $assignment->collaborator->area ?? '—' }}</td></tr>
                        <tr><td class="text-muted pl-0">Correo</td><td>{{ $assignment->collaborator->email ?? '—' }}</td></tr>
                    </table>
                @else
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded d-flex align-items-center justify-content-center text-white mr-3"
                             style="width:38px;height:38px;background:#7c3aed;font-size:.8rem;flex-shrink:0;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold">{{ $assignment->area->name ?? '—' }}</div>
                            <small class="text-muted">Área / Espacio</small>
                        </div>
                    </div>
                    @if($assignment->area?->description)
                        <p class="text-muted small mb-1">{{ $assignment->area->description }}</p>
                    @endif
                    @if($assignment->area?->branch)
                        <small class="text-muted">Sucursal: {{ $assignment->area->branch->name }}</small>
                    @endif
                @endif
            </div>
        </div>

        {{-- Detalles --}}
        <div class="card card-outline card-primary mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-info-circle mr-1 text-primary"></i> Detalles
                </h6>
            </div>
            <div class="card-body" style="font-size:.85rem;">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted pl-0" style="width:100px;">Fecha</td><td>{{ $assignment->assignment_date?->format('d/m/Y') }}</td></tr>
                    <tr><td class="text-muted pl-0">Registrado por</td><td>{{ $assignment->assignedBy?->name ?? '—' }}</td></tr>
                    @if($assignment->notes)
                    <tr><td class="text-muted pl-0">Notas</td><td>{{ $assignment->notes }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

    </div>
</div>
@stop
