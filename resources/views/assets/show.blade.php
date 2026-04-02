@extends('adminlte::page')

@section('title', 'Activo ' . $asset->internal_code)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.hub') }}">Otros Activos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Inventario</a></li>
                <li class="breadcrumb-item active">{{ $asset->internal_code }}</li>
            </ol>
        </nav>
        <div>
            <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            @can('assets.edit')
                <a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary mr-1">
                    <i class="fas fa-pen mr-1"></i> Editar
                </a>
            @endcan
            @can('assets.assign')
                @if(!$pendingDeletion && !$asset->isRetired())
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="openDeletionModal({{ $asset->id }}, '{{ $asset->internal_code }}', '{{ addslashes($asset->brand . ' ' . $asset->model) }}')">
                        <i class="fas fa-trash-alt mr-1"></i> Solicitar Baja
                    </button>
                @elseif($pendingDeletion)
                    <span class="badge badge-warning px-2 py-1">
                        <i class="fas fa-clock mr-1"></i> Baja pendiente de aprobación
                    </span>
                @endif
            @endcan
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

{{-- Alerta: activo retirado --}}
@if($asset->isRetired())
    <div class="alert alert-danger d-flex align-items-center mb-3">
        <i class="fas fa-ban fa-lg mr-2"></i>
        <strong>Este activo está {{ $asset->status?->name }}.</strong>
        <span class="ml-2">Ya no puede ser asignado ni modificado su estado.</span>
    </div>
@endif

<div class="row">

    {{-- ── Columna izquierda: Info principal ──────────────────────────── --}}
    <div class="col-md-5">

        {{-- Identificación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex align-items-center justify-content-between"
                 style="border-left:4px solid #7c3aed;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i> Identificación
                </h6>
                {{-- Badge de estado --}}
                @if($asset->status)
                    <span class="badge badge-pill"
                          style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;">
                        {{ $asset->status->name }}
                    </span>
                @endif
            </div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted pl-0" style="width:40%;font-size:.82rem;">Código</td>
                        <td><strong><code>{{ $asset->internal_code }}</code></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Tipo</td>
                        <td>{{ $asset->type?->name ?? '—' }}</td>
                    </tr>
                    @if($asset->type?->subcategory)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Subcategoría</td>
                        <td>
                            <span class="badge badge-light border">{{ $asset->type->subcategory }}</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Nombre</td>
                        <td>{{ $asset->brand ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Marca</td>
                        <td>{{ $asset->model ?? '—' }}</td>
                    </tr>
                    @if($asset->serial)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Serial</td>
                        <td><code style="font-size:.82rem;">{{ $asset->serial }}</code></td>
                    </tr>
                    @endif
                    @if($asset->hostname)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Nombre equipo</td>
                        <td>{{ $asset->hostname }}</td>
                    </tr>
                    @endif
                    @if($asset->asset_tag)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Etiqueta</td>
                        <td><code style="font-size:.82rem;">{{ $asset->asset_tag }}</code></td>
                    </tr>
                    @endif
                    @if($asset->fixed_asset_code)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Activo Fijo</td>
                        <td><code style="font-size:.82rem;">{{ $asset->fixed_asset_code }}</code></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Propiedad y Ubicación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #374151;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-building mr-1" style="color:#374151;"></i> Propiedad y Ubicación
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted pl-0" style="width:40%;font-size:.82rem;">Propiedad</td>
                        <td>
                            <span class="badge badge-{{ $asset->property_type === 'PROPIO' ? 'success' : 'info' }}">
                                {{ $asset->property_type }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Sucursal (sede)</td>
                        <td>{{ $asset->branch?->name ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Ciudad</td>
                        <td>{{ $asset->branch?->city ?? '—' }}</td>
                    </tr>
                    @if($asset->provider_name)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Proveedor</td>
                        <td>{{ $asset->provider_name }}</td>
                    </tr>
                    @endif
                    @if($asset->purchase_value)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Valor</td>
                        <td>${{ number_format($asset->purchase_value, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($asset->purchase_date)
                    <tr>
                        <td class="text-muted pl-0" style="font-size:.82rem;">Adquisición</td>
                        <td>{{ \Carbon\Carbon::parse($asset->purchase_date)->format('d/m/Y') }}</td>
                    </tr>
                    @endif
                </table>
                @if($asset->observations)
                    <div class="mt-2 p-2 bg-light rounded">
                        <small class="text-muted d-block" style="font-size:.75rem;text-transform:uppercase;">
                            Observaciones
                        </small>
                        <small>{{ $asset->observations }}</small>
                    </div>
                @endif
            </div>
        </div>

        {{-- Asignación actual --}}
        @if($currentAssignment)
        <div class="card shadow-sm border-left-success">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold text-success">
                    <i class="fas fa-user-check mr-1"></i> Asignación Actual
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                @php $assignment = $currentAssignment->assignment; @endphp
                <p class="mb-1 font-weight-bold">
                    <i class="fas {{ $assignment->destination_icon ?? 'fa-user' }} mr-1 text-muted"></i>
                    {{ $assignment->recipient_name }}
                </p>
                <small class="text-muted d-block">
                    Destino: {{ \App\Models\Assignment::destinationLabel($assignment->destination_type ?? 'collaborator') }}
                </small>
                <small class="text-muted d-block">
                    Fecha: {{ $assignment->assignment_date?->format('d/m/Y') ?? '—' }}
                </small>
                <a href="{{ route('assets.assignments.show', $assignment) }}"
                   class="btn btn-sm btn-outline-success mt-2 btn-block">
                    <i class="fas fa-eye mr-1"></i> Ver asignación
                </a>
            </div>
        </div>
        @else
        <div class="card shadow-sm border-left-info">
            <div class="card-body py-2 px-3 text-center text-muted">
                <i class="fas fa-box fa-lg mb-1 d-block" style="opacity:.3;"></i>
                <small>Sin asignación activa — disponible</small>
                @can('assets.assign')
                    @if(!$asset->isRetired())
                        <div class="mt-2">
                            <a href="{{ route('assets.assignments.create') }}"
                               class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-user-plus mr-1"></i> Asignar
                            </a>
                        </div>
                    @endif
                @endcan
            </div>
        </div>
        @endif

    </div>

    {{-- ── Columna derecha: Historial y Eventos ────────────────────────── --}}
    <div class="col-md-7">

        {{-- Historial de asignaciones --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #6c757d;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-history mr-1 text-muted"></i>
                    Historial de Asignaciones
                    <span class="badge badge-secondary ml-1">{{ $assignmentHistory->count() }}</span>
                </h6>
            </div>
            <div class="card-body p-0">
                @if($assignmentHistory->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity:.2;"></i>
                        <small>Sin historial de asignaciones</small>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light" style="font-size:.72rem;text-transform:uppercase;">
                                <tr>
                                    <th>Destinatario</th>
                                    <th>Destino</th>
                                    <th>Asignado</th>
                                    <th>Devuelto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignmentHistory as $aa)
                                <tr>
                                    <td class="small">
                                        @php $asn = $aa->assignment; @endphp
                                        {{ $asn?->recipient_name ?? '—' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-muted" style="font-size:.7rem;">
                                            {{ \App\Models\Assignment::destinationLabel($asn?->destination_type ?? 'collaborator') }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $aa->assigned_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td class="small text-muted">
                                        {{ $aa->returned_at?->format('d/m/Y') ?? '—' }}
                                    </td>
                                    <td>
                                        @if($aa->returned_at)
                                            <span class="badge badge-secondary" style="font-size:.7rem;">Devuelto</span>
                                        @else
                                            <span class="badge badge-success" style="font-size:.7rem;">Activo</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Línea de tiempo de eventos --}}
        @if($asset->events->isNotEmpty())
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #f59e0b;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-stream mr-1" style="color:#f59e0b;"></i>
                    Línea de Tiempo
                </h6>
            </div>
            <div class="card-body py-2">
                @foreach($asset->events->sortByDesc('created_at') as $event)
                <div class="d-flex mb-2">
                    <div class="mr-2 mt-1">
                        <span class="badge badge-secondary" style="font-size:.65rem;width:32px;">
                            {{ $loop->iteration }}
                        </span>
                    </div>
                    <div>
                        <strong class="d-block" style="font-size:.82rem;">{{ $event->event_type ?? $event->description }}</strong>
                        <small class="text-muted">
                            {{ $event->created_at?->format('d/m/Y H:i') }}
                            @if($event->user)— {{ $event->user->name }}@endif
                        </small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

</div>

{{-- Modal de Solicitud de Baja (reutilizado del módulo TI) --}}
<div class="modal fade" id="deletionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-trash-alt mr-1 text-danger"></i> Solicitar Baja</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="deletionForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Activo: <strong id="deletionAssetName"></strong></p>
                    <div class="form-group">
                        <label class="font-weight-bold">Motivo de la baja <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                                  placeholder="Explica el motivo: obsolescencia, daño irreparable, pérdida..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash-alt mr-1"></i> Solicitar Baja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
.card { border-radius: 10px; }
.table-borderless td { border: none !important; padding: .2rem .5rem; }
</style>
@stop

@section('js')
<script>
function openDeletionModal(assetId, code, name) {
    document.getElementById('deletionAssetName').textContent = code + ' — ' + name;
    document.getElementById('deletionForm').action = '/assets/' + assetId + '/deletion-request';
    $('#deletionModal').modal('show');
}
</script>
@stop
