@extends('adminlte::page')

@section('title', 'Activo ' . $asset->internal_code)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tech.assets.index') }}">Activos TI</a></li>
                <li class="breadcrumb-item active">{{ $asset->internal_code }}</li>
            </ol>
        </nav>
        <div>
            <a href="{{ route('tech.assets.index') }}" class="btn btn-sm btn-outline-secondary mr-1">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
            @can('tech.assets.edit')
                <a href="{{ route('tech.assets.edit', $asset) }}" class="btn btn-sm btn-outline-primary mr-1">
                    <i class="fas fa-pen mr-1"></i> Editar
                </a>
            @endcan
            @can('tech.assets.assign')
                @if(!$pendingDeletion && !$asset->isRetired())
                    <button type="button" class="btn btn-sm btn-outline-danger"
                            onclick="openDeletionModal({{ $asset->id }}, '{{ $asset->internal_code }}', '{{ addslashes($asset->brand . ' ' . $asset->model) }}')">
                        <i class="fas fa-trash-alt mr-1"></i> Solicitar Eliminación
                    </button>
                @elseif($pendingDeletion)
                    <span class="badge badge-warning px-2 py-1">
                        <i class="fas fa-clock mr-1"></i> Eliminación pendiente de aprobación
                    </span>
                @endif
            @endcan
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

{{-- Banner activo retirado --}}
@if($asset->isRetired())
    <div class="alert alert-danger d-flex align-items-center mb-3">
        <i class="fas fa-ban fa-lg mr-2"></i>
        <strong>Este activo está {{ $asset->status?->name }}.</strong>
        <span class="ml-2">Ya no puede ser asignado ni modificado su estado.</span>
    </div>
@endif

<div class="row">

    {{-- ── Columna izquierda: Info principal ─────────────────────────── --}}
    <div class="col-md-5">

        {{-- Identificación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex align-items-center justify-content-between" style="border-left:4px solid #1d4ed8;">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-laptop mr-1 text-primary"></i> Identificación</h6>
                <span class="badge badge-{{ $asset->status?->color ?? 'secondary' }} px-2">
                    {{ $asset->status?->name ?? '—' }}
                </span>
            </div>
            <div class="card-body py-2">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Código:</dt>
                    <dd class="col-7"><code class="font-weight-bold">{{ $asset->internal_code }}</code></dd>

                    <dt class="col-5 text-muted">Tipo:</dt>
                    <dd class="col-7">{{ $asset->type?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Marca:</dt>
                    <dd class="col-7">{{ $asset->brand }}</dd>

                    <dt class="col-5 text-muted">Modelo:</dt>
                    <dd class="col-7">{{ $asset->model }}</dd>

                    <dt class="col-5 text-muted">Serial:</dt>
                    <dd class="col-7"><code>{{ $asset->serial }}</code></dd>

                    <dt class="col-5 text-muted">Etiqueta Inventario:</dt>
                    <dd class="col-7">
                        @if($asset->asset_tag)
                            <code>{{ $asset->asset_tag }}</code>
                        @else
                            <em class="text-muted">Sin etiqueta</em>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Cód. Activo Fijo:</dt>
                    <dd class="col-7">{{ $asset->fixed_asset_code ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        {{-- Propiedad y Ubicación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #374151;">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-building mr-1"></i> Propiedad y Ubicación</h6>
            </div>
            <div class="card-body py-2">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Propiedad:</dt>
                    <dd class="col-7">
                        <span class="badge badge-light border">{{ $asset->property_type }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Sucursal:</dt>
                    <dd class="col-7">{{ $asset->branch?->name ?? '—' }}</dd>

                    @if($asset->provider_name)
                        <dt class="col-5 text-muted">Proveedor:</dt>
                        <dd class="col-7">{{ $asset->provider_name }}</dd>
                    @endif

                    @if($asset->observations)
                        <dt class="col-5 text-muted">Observaciones:</dt>
                        <dd class="col-7">{{ $asset->observations }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Información Financiera --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #059669;">
                <h6 class="mb-0 font-weight-bold"><i class="fas fa-dollar-sign mr-1" style="color:#059669;"></i> Información Financiera</h6>
            </div>
            <div class="card-body py-2">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Valor de compra:</dt>
                    <dd class="col-7">
                        @if($asset->purchase_value)
                            <strong>$ {{ number_format($asset->purchase_value, 2) }}</strong>
                        @else
                            <span class="text-muted">No registrado</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Fecha compra:</dt>
                    <dd class="col-7">
                        {{ $asset->purchase_date?->format('d/m/Y') ?? '—' }}
                    </dd>
                </dl>
            </div>
        </div>

    </div>

    {{-- ── Columna derecha: Estado + Acciones + Asignación ───────────── --}}
    <div class="col-md-7">

        {{-- Asignación actual --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #0f766e;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-user-check mr-1" style="color:#0f766e;"></i> Asignación Actual
                </h6>
            </div>
            <div class="card-body py-2">
                @if($currentAssignment)
                    @php $asgn = $currentAssignment->assignment; @endphp
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Colaborador:</dt>
                        <dd class="col-7">
                            <a href="{{ route('collaborators.show', $asgn->collaborator) }}">
                                {{ $asgn->collaborator->full_name }}
                            </a>
                        </dd>
                        <dt class="col-5 text-muted">Cédula:</dt>
                        <dd class="col-7">{{ $asgn->collaborator->document }}</dd>
                        <dt class="col-5 text-muted">Asignado desde:</dt>
                        <dd class="col-7">{{ $currentAssignment->assigned_at?->format('d/m/Y') }}</dd>
                        <dt class="col-5 text-muted">Ver asignación:</dt>
                        <dd class="col-7">
                            <a href="{{ route('tech.assignments.show', $asgn) }}" class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-eye mr-1"></i> Asignación #{{ $asgn->id }}
                            </a>
                        </dd>
                    </dl>
                @else
                    <p class="text-muted small mb-0">
                        <i class="fas fa-info-circle mr-1"></i> Este activo no está asignado actualmente.
                    </p>
                @endif
            </div>
        </div>

        {{-- Cambiar estado (transiciones) --}}
        @if(!$asset->isRetired())
            <div class="card shadow-sm">
                <div class="card-header py-2" style="border-left:4px solid #7c3aed;">
                    <h6 class="mb-0 font-weight-bold">
                        <i class="fas fa-exchange-alt mr-1" style="color:#7c3aed;"></i> Cambiar Estado
                    </h6>
                </div>
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2" style="gap:.5rem;">

                        @if($asset->status?->name === 'En Traslado')
                            <form method="POST" action="{{ route('asset.transition.arrival', $asset) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success">
                                    <i class="fas fa-check-circle mr-1"></i> Confirmar Llegada
                                </button>
                            </form>
                        @else
                            {{-- Mantenimiento --}}
                            @if($asset->status?->name !== 'Mantenimiento')
                                <button class="btn btn-sm btn-warning" onclick="openTransModal('maintenance','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                    <i class="fas fa-tools mr-1"></i> Mantenimiento
                                </button>
                            @endif

                            {{-- Garantía --}}
                            @if($asset->status?->name !== 'En Garantía')
                                <button class="btn btn-sm btn-info text-white" onclick="openTransModal('warranty','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                    <i class="fas fa-shield-alt mr-1"></i> Garantía
                                </button>
                            @endif

                            {{-- Traslado --}}
                            <button class="btn btn-sm btn-primary" onclick="openTransModal('transfer','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                <i class="fas fa-exchange-alt mr-1"></i> Trasladar
                            </button>

                            {{-- Disponible (si está en mantenimiento/garantía/bodega) --}}
                            @if(in_array($asset->status?->name, ['Mantenimiento','En Garantía','En Bodega']))
                                <form method="POST" action="{{ route('asset.transition.arrival', $asset) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">
                                        <i class="fas fa-check mr-1"></i> Marcar Disponible
                                    </button>
                                </form>
                            @endif

                            <div class="w-100 my-1" style="border-top:1px dashed #dee2e6;"></div>

                            {{-- Baja (con aprobación) --}}
                            <button class="btn btn-sm btn-outline-danger" onclick="openTransModal('baja','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                <i class="fas fa-ban mr-1"></i> Dar de Baja
                            </button>

                            {{-- Donación --}}
                            <button class="btn btn-sm btn-outline-dark" onclick="openTransModal('donation','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                <i class="fas fa-hand-holding-heart mr-1"></i> Donar
                            </button>

                            {{-- Venta --}}
                            <button class="btn btn-sm btn-outline-dark" onclick="openTransModal('sale','{{ $asset->id }}','{{ $asset->internal_code }}')">
                                <i class="fas fa-tag mr-1"></i> Vender
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Historial de asignaciones --}}
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-history mr-1"></i> Historial de Asignaciones
                </h6>
            </div>
            <div class="card-body p-0">
                @if($assignmentHistory->isEmpty())
                    <p class="text-muted small p-3 mb-0">
                        <i class="fas fa-inbox mr-1"></i> Sin historial de asignaciones.
                    </p>
                @else
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Colaborador</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignmentHistory as $aa)
                                <tr>
                                    <td>
                                        <small>{{ $aa->assignment->collaborator->full_name }}</small>
                                    </td>
                                    <td><small>{{ $aa->assigned_at?->format('d/m/Y') }}</small></td>
                                    <td>
                                        <small>{{ $aa->returned_at?->format('d/m/Y') ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @if($aa->isReturned())
                                            <span class="badge badge-secondary">Devuelto</span>
                                        @else
                                            <span class="badge badge-success">Activo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Historial de eventos --}}
        @if($asset->events->isNotEmpty())
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h6 class="mb-0 font-weight-bold">
                        <i class="fas fa-stream mr-1"></i> Línea de Tiempo del Activo
                    </h6>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="timeline timeline-inverse" style="max-height:300px;overflow-y:auto;">
                        @foreach($asset->events as $event)
                            <div class="time-label">
                                <span class="bg-{{ $event->event_color }}">{{ $event->event_label }}</span>
                            </div>
                            <div>
                                <i class="fas fa-circle bg-{{ $event->event_color }}"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $event->created_at->format('d/m/Y H:i') }}
                                    </span>
                                    <h3 class="timeline-header">
                                        <strong>{{ $event->from_status ?? '—' }}</strong>
                                        → <strong>{{ $event->to_status }}</strong>
                                    </h3>
                                    @if($event->notes)
                                        <div class="timeline-body">
                                            <small>{{ $event->notes }}</small>
                                        </div>
                                    @endif
                                    <div class="timeline-footer">
                                        <small class="text-muted">
                                            <i class="fas fa-user mr-1"></i>{{ $event->user?->name ?? 'Sistema' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div><i class="fas fa-clock bg-gray"></i></div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

{{-- ═══ MODALES DE TRANSICIÓN ═══════════════════════════════════════════ --}}

{{-- Modal: Mantenimiento --}}
<div class="modal fade" id="modalMaintenance" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formMaintenance" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-tools mr-1"></i> Enviar a Mantenimiento</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Activo: <strong id="maintenanceCode"></strong></p>
                    <div class="form-group mb-0">
                        <label>Descripción del problema</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ej: pantalla rota, no enciende..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm">Confirmar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Garantía --}}
<div class="modal fade" id="modalWarranty" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formWarranty" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-shield-alt mr-1"></i> Enviar a Garantía</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Activo: <strong id="warrantyCode"></strong></p>
                    <div class="form-group">
                        <label>Proveedor / Fabricante</label>
                        <input type="text" name="provider" class="form-control" placeholder="Dell, Lenovo...">
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info btn-sm text-white">Confirmar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Traslado --}}
<div class="modal fade" id="modalTransfer" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formTransfer" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt mr-1"></i> Trasladar de Sede</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Activo: <strong id="transferCode"></strong></p>
                    <div class="form-group">
                        <label>Sede destino <span class="text-danger">*</span></label>
                        <select name="to_branch_id" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirmar Traslado</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Baja --}}
<div class="modal fade" id="modalBaja" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formBaja" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-ban mr-1"></i> Dar de Baja</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Requiere aprobación del <strong>Aprobador</strong>.
                    </div>
                    <p class="small text-muted">Activo: <strong id="bajaCode"></strong></p>
                    <div class="form-group">
                        <label>Motivo <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="danado">Dañado / Inservible</option>
                            <option value="obsoleto">Obsoleto</option>
                            <option value="perdido">Perdido / Extraviado</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Descripción <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Enviar Solicitud de Baja</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Donación --}}
<div class="modal fade" id="modalDonation" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formDonation" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-hand-holding-heart mr-1"></i> Donación</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Activo: <strong id="donationCode"></strong></p>
                    <div class="form-group">
                        <label>Receptor / Entidad <span class="text-danger">*</span></label>
                        <input type="text" name="recipient" class="form-control" required>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm">Confirmar Donación</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Venta --}}
<div class="modal fade" id="modalSale" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formSale" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-tag mr-1"></i> Venta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Activo: <strong id="saleCode"></strong></p>
                    <div class="form-group">
                        <label>Comprador <span class="text-danger">*</span></label>
                        <input type="text" name="buyer" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Valor de venta</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                            <input type="number" name="sale_value" class="form-control" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm">Confirmar Venta</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Solicitar Eliminación --}}
<div class="modal fade" id="modalDeletion" tabindex="-1">
    <div class="modal-dialog">
        <form id="formDeletion" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash-alt mr-1"></i> Solicitar Eliminación de Registro</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Esta solicitud borrará el registro <strong>permanentemente</strong> del sistema tras aprobación del <strong>Aprobador</strong>.
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Motivo <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="danado">Dañado / Inservible</option>
                            <option value="obsoleto">Obsoleto</option>
                            <option value="perdido">Perdido / Extraviado</option>
                            <option value="venta">Venta</option>
                            <option value="donacion">Donación</option>
                            <option value="otro">Error de registro / Duplicado / Otro</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Descripción <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" required
                                  placeholder="Explique el motivo detalladamente..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-paper-plane mr-1"></i> Enviar Solicitud
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@stop

@section('js')
<script>
const transRoutes = {
    maintenance: '/assets/{id}/transition/maintenance',
    warranty:    '/assets/{id}/transition/warranty',
    transfer:    '/assets/{id}/transition/transfer',
    baja:        '/assets/{id}/transition/baja',
    donation:    '/assets/{id}/transition/donation',
    sale:        '/assets/{id}/transition/sale',
};
const transModals = {
    maintenance: { modal:'#modalMaintenance', form:'#formMaintenance', codeEl:'#maintenanceCode' },
    warranty:    { modal:'#modalWarranty',    form:'#formWarranty',    codeEl:'#warrantyCode' },
    transfer:    { modal:'#modalTransfer',    form:'#formTransfer',    codeEl:'#transferCode' },
    baja:        { modal:'#modalBaja',        form:'#formBaja',        codeEl:'#bajaCode' },
    donation:    { modal:'#modalDonation',    form:'#formDonation',    codeEl:'#donationCode' },
    sale:        { modal:'#modalSale',        form:'#formSale',        codeEl:'#saleCode' },
};

function openTransModal(type, assetId, code) {
    const cfg = transModals[type];
    document.querySelector(cfg.codeEl).textContent = code;
    document.querySelector(cfg.form).action = transRoutes[type].replace('{id}', assetId);
    document.querySelector(cfg.form).querySelectorAll('textarea,input:not([type=hidden])').forEach(el => el.value = '');
    const sel = document.querySelector(cfg.form + ' select:not([name=_method])');
    if (sel) sel.selectedIndex = 0;
    $(cfg.modal).modal('show');
}

function openDeletionModal(assetId, code, desc) {
    document.getElementById('formDeletion').action = '/assets/' + assetId + '/deletion-request';
    document.getElementById('formDeletion').querySelector('select').selectedIndex = 0;
    document.getElementById('formDeletion').querySelector('textarea').value = '';
    $('#modalDeletion').modal('show');
}
</script>
@stop
