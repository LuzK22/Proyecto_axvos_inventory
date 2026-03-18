@extends('adminlte::page')

@section('title', 'Detalle de Asignación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-clipboard-list text-primary mr-2"></i>
            Asignación #{{ $assignment->id }}
        </h1>
        <div>
            @can('tech.assets.assign')
                @if($assignment->status === 'activa')
                    {{-- Solo Acta TI — módulo Tecnología --}}
                    @if(!empty($actaTi))
                        <a href="{{ route('actas.show', $actaTi) }}" class="btn btn-sm mr-1"
                           style="background:#0f766e;color:#fff;border:none;">
                            <i class="fas fa-file-signature mr-1"></i> Ver Acta TI
                        </a>
                    @else
                        <form method="POST" action="{{ route('actas.generate', $assignment) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="category" value="TI">
                            <button type="submit" class="btn btn-sm mr-1"
                                    style="background:#0f766e;color:#fff;border:none;">
                                <i class="fas fa-file-signature mr-1"></i> Generar Acta TI
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('tech.assignments.return', $assignment) }}" class="btn btn-warning mr-1">
                        <i class="fas fa-undo mr-1"></i> Registrar Devolución
                    </a>
                @endif
            @endcan
            <a href="{{ route('tech.assignments.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

<div class="row">

    {{-- ── Info del Colaborador ──────────────────────────────────────── --}}
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user mr-1"></i> Colaborador</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre:</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('collaborators.show', $assignment->collaborator) }}">
                            {{ $assignment->collaborator->full_name }}
                        </a>
                    </dd>
                    <dt class="col-sm-5">Cédula:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->document }}</dd>
                    <dt class="col-sm-5">Cargo:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->position ?? '-' }}</dd>
                    <dt class="col-sm-5">Área:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->area ?? '-' }}</dd>
                    <dt class="col-sm-5">Sucursal:</dt>
                    <dd class="col-sm-7">{{ $assignment->collaborator->branch?->name ?? '-' }}</dd>
                    <dt class="col-sm-5">Modalidad:</dt>
                    <dd class="col-sm-7">
                        @php
                            $mod = $assignment->collaborator->modalidad_trabajo ?? 'presencial';
                            $badgeClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                            $modLabel   = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $modLabel }}</span>
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Datos de Asignación</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Estado:</dt>
                    <dd class="col-sm-6">
                        @if($assignment->status === 'activa')
                            <span class="badge badge-success">Activa</span>
                        @else
                            <span class="badge badge-secondary">Devuelta</span>
                        @endif
                    </dd>
                    <dt class="col-sm-6">Fecha:</dt>
                    <dd class="col-sm-6">{{ $assignment->assignment_date->format('d/m/Y') }}</dd>
                    <dt class="col-sm-6">Registrado por:</dt>
                    <dd class="col-sm-6">{{ $assignment->assignedBy?->name ?? '-' }}</dd>
                    @if($assignment->notes)
                        <dt class="col-sm-6">Notas:</dt>
                        <dd class="col-sm-6">{{ $assignment->notes }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- ── Activos Asignados ─────────────────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-laptop mr-1"></i> Activos
                    <span class="badge badge-success ml-1">{{ $assignment->assignmentAssets->count() }}</span>
                </h3>
                <div class="card-tools">
                    @if($assignment->activeAssets->count() > 0)
                        <span class="badge badge-success mr-1">{{ $assignment->activeAssets->count() }} activo(s)</span>
                    @endif
                    @if($assignment->returnedAssets->count() > 0)
                        <span class="badge badge-secondary">{{ $assignment->returnedAssets->count() }} devuelto(s)</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Asignado</th>
                            <th>Estado</th>
                            @if($assignment->status === 'activa')
                                <th>Acciones</th>
                            @else
                                <th>Devuelto</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignment->assignmentAssets as $aa)
                            <tr class="{{ $aa->isReturned() ? 'table-light text-muted' : '' }}">
                                <td><code>{{ $aa->asset->internal_code }}</code></td>
                                <td>{{ $aa->asset->type?->name }}</td>
                                <td>{{ $aa->asset->brand }} {{ $aa->asset->model }}</td>
                                <td><small>{{ $aa->asset->serial }}</small></td>
                                <td><small>{{ $aa->assigned_at?->format('d/m/Y') }}</small></td>
                                <td>
                                    @if($aa->isReturned())
                                        <span class="badge badge-secondary">Devuelto</span>
                                    @else
                                        <span class="badge badge-success">Activo</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$aa->isReturned() && $assignment->status === 'activa')
                                        {{-- Dropdown de acciones --}}
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-xs btn-outline-secondary dropdown-toggle"
                                                    data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                {{-- Retirar (→ Disponible) --}}
                                                <a class="dropdown-item text-success"
                                                   href="#" onclick="openModal('retire', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-undo-alt mr-1"></i> Retirar (Disponible)
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                {{-- Mantenimiento --}}
                                                <a class="dropdown-item text-warning"
                                                   href="#" onclick="openModal('maintenance', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-tools mr-1"></i> Enviar a Mantenimiento
                                                </a>
                                                {{-- Garantía --}}
                                                <a class="dropdown-item text-info"
                                                   href="#" onclick="openModal('warranty', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-shield-alt mr-1"></i> Enviar a Garantía
                                                </a>
                                                {{-- Traslado --}}
                                                <a class="dropdown-item text-primary"
                                                   href="#" onclick="openModal('transfer', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-exchange-alt mr-1"></i> Trasladar de Sede
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                {{-- Baja --}}
                                                <a class="dropdown-item text-danger"
                                                   href="#" onclick="openModal('baja', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-ban mr-1"></i> Dar de Baja
                                                </a>
                                                {{-- Donación --}}
                                                <a class="dropdown-item text-dark"
                                                   href="#" onclick="openModal('donation', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-hand-holding-heart mr-1"></i> Donar
                                                </a>
                                                {{-- Venta --}}
                                                <a class="dropdown-item text-dark"
                                                   href="#" onclick="openModal('sale', {{ $aa->asset->id }}, '{{ $aa->asset->internal_code }}')">
                                                    <i class="fas fa-tag mr-1"></i> Vender
                                                </a>
                                            </div>
                                        </div>
                                    @elseif($aa->isReturned())
                                        <small>{{ $aa->returned_at?->format('d/m/Y') }}</small>
                                        @if($aa->return_notes)
                                            <br><small class="text-muted">{{ $aa->return_notes }}</small>
                                        @endif
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     MODALES DE TRANSICIÓN
═══════════════════════════════════════════════════════════════════ --}}

{{-- Modal: Retirar --}}
<div class="modal fade" id="modalRetire" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formRetire" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-undo-alt mr-1"></i> Retirar Activo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>El activo <strong id="retireCode"></strong> quedará <span class="badge badge-success">Disponible</span>.</p>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm">Confirmar Retiro</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Mantenimiento --}}
<div class="modal fade" id="modalMaintenance" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formMaintenance" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-tools mr-1"></i> Enviar a Mantenimiento</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo <strong id="maintenanceCode"></strong> pasará a <span class="badge badge-warning">Mantenimiento</span>.</p>
                    <div class="form-group mb-0">
                        <label>Descripción del problema <span class="text-muted">(opcional)</span></label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ej: pantalla rota, no enciende..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm">Enviar a Mantenimiento</button>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo <strong id="warrantyCode"></strong> pasará a <span class="badge badge-info">En Garantía</span>.</p>
                    <div class="form-group">
                        <label>Proveedor / Fabricante <span class="text-muted">(opcional)</span></label>
                        <input type="text" name="provider" class="form-control" placeholder="Ej: Dell, Lenovo...">
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info btn-sm text-white">Enviar a Garantía</button>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo <strong id="transferCode"></strong> pasará a <span class="badge badge-info">En Traslado</span>.</p>
                    <div class="form-group">
                        <label>Sede destino <span class="text-danger">*</span></label>
                        <select name="to_branch_id" class="form-control" required>
                            <option value="">-- Seleccionar sede --</option>
                            @foreach(\App\Models\Branch::where('active', true)->orderBy('name')->get() as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} — {{ $branch->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>El activo <strong id="bajaCode"></strong> será retirado definitivamente del inventario.</p>
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
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Describa el estado del activo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirmar Baja</button>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo <strong id="donationCode"></strong> pasará a <span class="badge badge-dark">Donado</span>.</p>
                    <div class="form-group">
                        <label>Receptor / Entidad <span class="text-danger">*</span></label>
                        <input type="text" name="recipient" class="form-control" required placeholder="Nombre de la persona u organización...">
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
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
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo <strong id="saleCode"></strong> pasará a <span class="badge badge-dark">Vendido</span>.</p>
                    <div class="form-group">
                        <label>Comprador <span class="text-danger">*</span></label>
                        <input type="text" name="buyer" class="form-control" required placeholder="Nombre del comprador...">
                    </div>
                    <div class="form-group">
                        <label>Valor de venta <span class="text-muted">(opcional)</span></label>
                        <input type="number" name="sale_value" class="form-control" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Opcional..."></textarea>
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

@stop

@section('js')
<script>
// Mapa modal → configuración
const modalConfig = {
    retire:      { modal: '#modalRetire',      form: '#formRetire',      codeEl: '#retireCode',      route: '/assets/{id}/transition/retire' },
    maintenance: { modal: '#modalMaintenance', form: '#formMaintenance', codeEl: '#maintenanceCode', route: '/assets/{id}/transition/maintenance' },
    warranty:    { modal: '#modalWarranty',    form: '#formWarranty',    codeEl: '#warrantyCode',    route: '/assets/{id}/transition/warranty' },
    transfer:    { modal: '#modalTransfer',    form: '#formTransfer',    codeEl: '#transferCode',    route: '/assets/{id}/transition/transfer' },
    baja:        { modal: '#modalBaja',        form: '#formBaja',        codeEl: '#bajaCode',        route: '/assets/{id}/transition/baja' },
    donation:    { modal: '#modalDonation',    form: '#formDonation',    codeEl: '#donationCode',    route: '/assets/{id}/transition/donation' },
    sale:        { modal: '#modalSale',        form: '#formSale',        codeEl: '#saleCode',        route: '/assets/{id}/transition/sale' },
};

function openModal(type, assetId, assetCode) {
    const cfg = modalConfig[type];
    if (!cfg) return;

    // Setear código del activo en el modal
    document.querySelector(cfg.codeEl).textContent = assetCode;

    // Setear action del form con el assetId
    const action = cfg.route.replace('{id}', assetId);
    document.querySelector(cfg.form).action = action;

    // Limpiar campos del form
    document.querySelector(cfg.form).querySelectorAll('textarea, input:not([type=hidden])').forEach(el => el.value = '');
    const sel = document.querySelector(cfg.form).querySelector('select:not([name="_method"])');
    if (sel) sel.selectedIndex = 0;

    $(cfg.modal).modal('show');
}
</script>
@stop
