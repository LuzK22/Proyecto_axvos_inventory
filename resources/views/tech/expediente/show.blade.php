@extends('adminlte::page')

@section('title', 'Expediente TI — ' . $collaborator->full_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="m-0">
                <i class="fas fa-folder-open text-primary mr-2"></i>
                Expediente TI
                <small class="text-muted ml-2" style="font-size:.7em;">{{ $collaborator->full_name }}</small>
            </h1>
        </div>
        <div>
            <a href="{{ route('tech.assignments.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- ─── FICHA DEL COLABORADOR ──────────────────────────────────────────────── --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold"
                     style="width:56px;height:56px;font-size:1.3rem;background:linear-gradient(135deg,#1d4ed8,#0ea5e9);">
                    {{ strtoupper(substr($collaborator->full_name, 0, 1)) }}
                </div>
            </div>
            <div class="col">
                <div class="font-weight-bold" style="font-size:1.1rem;">{{ $collaborator->full_name }}</div>
                <div class="text-muted small">
                    {{ $collaborator->position ?? '—' }}
                    @if($collaborator->area) · {{ $collaborator->area }} @endif
                    @if($collaborator->branch) · {{ $collaborator->branch->name }} @endif
                </div>
            </div>
            <div class="col-auto">
                @php
                    $mod = $collaborator->modalidad_trabajo ?? 'presencial';
                    $modClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                    $modLabel = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                @endphp
                <span class="badge {{ $modClass }} px-3 py-2" style="font-size:.85rem;">{{ $modLabel }}</span>
            </div>
            <div class="col-auto text-center">
                <div class="font-weight-bold" style="font-size:1.5rem;color:#1d4ed8;">{{ $activeItems->count() }}</div>
                <div class="text-muted small">activo{{ $activeItems->count() !== 1 ? 's' : '' }} TI</div>
            </div>
        </div>
    </div>
</div>

{{-- ─── FORMULARIO PRINCIPAL con checkboxes ──────────────────────────────── --}}
<form id="expediente-form" method="GET" action="#">

<div class="card card-outline card-primary mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-laptop mr-1 text-primary"></i>
            Activos TI activos asignados
        </h3>
        <div class="d-flex align-items-center" style="gap:.5rem;">
            <button type="button" id="btn-toggle-det"
                    class="btn btn-xs btn-outline-secondary"
                    onclick="toggleDet()" style="font-size:.73rem;">
                <i class="fas fa-table mr-1"></i>
                <span id="lbl-det">Ver detalles</span>
            </button>
            <div class="custom-control custom-checkbox mr-2">
                <input type="checkbox" class="custom-control-input" id="select-all">
                <label class="custom-control-label font-weight-bold" for="select-all">Seleccionar todos</label>
            </div>
        </div>
    </div>

    {{-- ─── BARRA DE ACCIONES ────────────────────────────────────────── --}}
    <div class="card-header py-2" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
        <div class="d-flex flex-wrap gap-2 align-items-center" id="action-bar">
            {{-- Generar acta con seleccionados --}}
            <button type="button"
                    class="btn btn-sm btn-primary action-btn needs-selection"
                    id="btn-acta-seleccionados"
                    disabled
                    onclick="submitAction('acta_seleccionados')">
                <i class="fas fa-file-signature mr-1"></i>
                Generar acta con seleccionados
                <span class="badge badge-light ml-1 selection-count" style="display:none;">0</span>
            </button>

            {{-- Generar acta consolidada --}}
            @if($activeItems->count() > 0)
            <button type="button"
                    class="btn btn-sm btn-success"
                    id="btn-acta-consolidada"
                    onclick="submitAction('acta_consolidada')">
                <i class="fas fa-layer-group mr-1"></i>
                Acta consolidada
                <span class="badge badge-light ml-1">{{ $activeItems->count() }}</span>
            </button>
            @endif

            <div class="border-left mx-1" style="height:24px;"></div>

            {{-- Devolución parcial --}}
            <button type="button"
                    class="btn btn-sm btn-warning action-btn needs-selection"
                    id="btn-devolucion-parcial"
                    disabled
                    onclick="submitAction('devolucion_parcial')">
                <i class="fas fa-undo mr-1"></i>
                Devolución parcial
                <span class="badge badge-dark ml-1 selection-count" style="display:none;">0</span>
            </button>

            {{-- Devolución total --}}
            @if($activeItems->count() > 0)
            <button type="button"
                    class="btn btn-sm btn-danger"
                    id="btn-devolucion-total"
                    onclick="submitAction('devolucion_total')">
                <i class="fas fa-sign-out-alt mr-1"></i>
                Devolución total
            </button>
            @endif
        </div>

        {{-- Aviso de selección --}}
        <div id="selection-hint" class="text-muted small mt-1" style="display:none;">
            <i class="fas fa-info-circle mr-1"></i>
            <span id="selection-hint-text"></span>
        </div>
    </div>

    {{-- ─── TABLA DE ACTIVOS ─────────────────────────────────────────── --}}
    <div class="card-body p-0">
        @if($activeItems->isEmpty())
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                Este colaborador no tiene activos TI asignados actualmente.
                <div class="mt-3">
                    <a href="{{ route('tech.assignments.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Nueva asignación
                    </a>
                </div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="assets-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;" class="text-center">&nbsp;</th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th class="col-det">Etiqueta</th>
                            <th class="col-det">Cód. Fijo</th>
                            <th class="col-det">Propiedad</th>
                            <th class="col-det">Valor compra</th>
                            <th>Estado</th>
                            <th>Asignado</th>
                            <th>Asignación</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeItems as $item)
                        @php
                            $asset = $item->asset;
                            $assignment = $item->assignment;
                        @endphp
                        <tr class="asset-row" data-aa-id="{{ $item->id }}" data-asset-id="{{ $asset->id }}">
                            {{-- Checkbox --}}
                            <td class="text-center align-middle">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input asset-checkbox"
                                           id="asset_{{ $item->id }}"
                                           name="assignment_asset_ids[]"
                                           value="{{ $item->id }}"
                                           data-asset-id="{{ $asset->id }}"
                                           data-assignment-id="{{ $assignment->id ?? '' }}">
                                    <label class="custom-control-label" for="asset_{{ $item->id }}"></label>
                                </div>
                            </td>

                            {{-- Código --}}
                            <td class="align-middle font-weight-bold" style="font-family:monospace;white-space:nowrap;">
                                {{ $asset->internal_code }}
                            </td>

                            {{-- Tipo --}}
                            <td class="align-middle">
                                <span class="badge badge-light border">
                                    {{ $asset->type?->name ?? '—' }}
                                </span>
                            </td>

                            {{-- Marca / Modelo --}}
                            <td class="align-middle">
                                <div class="font-weight-semibold">{{ $asset->brand ?? '—' }}</div>
                                <div class="text-muted small">{{ $asset->model ?? '' }}</div>
                            </td>

                            {{-- Serial --}}
                            <td class="align-middle text-muted small" style="font-family:monospace;">
                                {{ $asset->serial ?? '—' }}
                            </td>

                            {{-- Detalles adicionales (ocultos por defecto) --}}
                            <td class="col-det align-middle small text-muted" style="font-family:monospace;">
                                {{ $asset->asset_tag ?? '—' }}
                            </td>
                            <td class="col-det align-middle small text-muted" style="font-family:monospace;">
                                {{ $asset->fixed_asset_code ?? '—' }}
                            </td>
                            <td class="col-det align-middle small">
                                <span class="badge badge-light border" style="font-size:.68rem;">
                                    {{ $asset->property_type ?? '—' }}
                                </span>
                            </td>
                            <td class="col-det align-middle small text-muted">
                                {{ $asset->purchase_value ? '$ '.number_format($asset->purchase_value, 0, ',', '.') : '—' }}
                            </td>

                            {{-- Estado --}}
                            <td class="align-middle">
                                @if($asset->status)
                                    <span class="badge"
                                          style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;">
                                        {{ $asset->status->name }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">—</span>
                                @endif
                            </td>

                            {{-- Fecha de asignación --}}
                            <td class="align-middle small text-muted" style="white-space:nowrap;">
                                {{ $item->assigned_at ? \Carbon\Carbon::parse($item->assigned_at)->format('d/m/Y') : '—' }}
                            </td>

                            {{-- Asignación (link) --}}
                            <td class="align-middle small">
                                @if($assignment)
                                    <a href="{{ route('tech.assignments.show', $assignment) }}"
                                       class="text-primary"
                                       title="Ver asignación #{{ $assignment->id }}">
                                        #{{ $assignment->id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="align-middle text-center" style="white-space:nowrap;">
                                <a href="{{ route('tech.assets.show', $asset) }}"
                                   class="btn btn-xs btn-outline-primary mr-1"
                                   title="Ciclo de vida del activo">
                                    <i class="fas fa-history"></i>
                                </a>
                                @if($assignment)
                                <a href="{{ route('tech.assignments.show', $assignment) }}"
                                   class="btn btn-xs btn-outline-secondary"
                                   title="Ver asignación">
                                    <i class="fas fa-eye"></i>
                                </a>
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

</form>

{{-- ─── LÍNEA DE TIEMPO DEL COLABORADOR ───────────────────────────────────── --}}
@if($timeline->isNotEmpty())
<div class="card card-outline card-info mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-stream mr-1 text-info"></i>
            Línea de tiempo TI
            <span class="badge badge-info ml-1">{{ $timeline->count() }}</span>
        </h3>
        <small class="text-muted">Todos los activos asignados y devueltos</small>
    </div>
    <div class="card-body p-0">
        <div class="timeline-list" style="position:relative;">
            @foreach($timeline as $item)
            @php
                $isActive  = is_null($item->returned_at);
                $dotColor  = $isActive ? '#1d4ed8' : '#6b7280';
                $bgRow     = $isActive ? '#eff6ff' : '#f9fafb';
            @endphp
            <div class="d-flex align-items-start px-3 py-2 border-bottom timeline-row"
                 style="background:{{ $bgRow }};">
                {{-- Punto de tiempo --}}
                <div class="mr-3 mt-1 flex-shrink-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                         style="width:28px;height:28px;font-size:.7rem;background:{{ $dotColor }};">
                        <i class="fas {{ $isActive ? 'fa-laptop' : 'fa-undo' }}"></i>
                    </div>
                </div>
                {{-- Contenido --}}
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <span class="font-weight-bold" style="font-family:monospace;font-size:.9rem;">
                                {{ $item->asset->internal_code }}
                            </span>
                            <span class="text-muted small ml-2">{{ $item->asset->type?->name }}</span>
                            @if($item->asset->brand)
                                <span class="text-muted small ml-1">· {{ $item->asset->brand }}</span>
                            @endif
                            @if($item->asset->model)
                                <span class="text-muted small ml-1">{{ $item->asset->model }}</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-1 ml-2">
                            @if($isActive)
                                <span class="badge badge-primary badge-pill" style="font-size:.72rem;">Activo</span>
                            @else
                                <span class="badge badge-secondary badge-pill" style="font-size:.72rem;">Devuelto</span>
                            @endif
                            <a href="{{ route('tech.assets.show', $item->asset) }}"
                               class="btn btn-xs btn-outline-secondary ml-1"
                               title="Ciclo de vida">
                                <i class="fas fa-history"></i>
                            </a>
                        </div>
                    </div>
                    <div class="text-muted" style="font-size:.78rem;">
                        <i class="fas fa-sign-in-alt mr-1 text-success"></i>
                        Asignado: {{ \Carbon\Carbon::parse($item->assigned_at)->format('d/m/Y') }}
                        @if(!$isActive)
                            &nbsp;
                            <i class="fas fa-sign-out-alt mr-1 text-danger ml-2"></i>
                            Devuelto: {{ \Carbon\Carbon::parse($item->returned_at)->format('d/m/Y') }}
                            @php
                                $dias = \Carbon\Carbon::parse($item->assigned_at)->diffInDays($item->returned_at);
                            @endphp
                            <span class="text-muted ml-1">({{ $dias }} día{{ $dias !== 1 ? 's' : '' }})</span>
                        @else
                            @php
                                $dias = \Carbon\Carbon::parse($item->assigned_at)->diffInDays(now());
                            @endphp
                            <span class="text-muted ml-2">— hace {{ $dias }} día{{ $dias !== 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ─── MODAL: CONFIRMAR ACCIÓN (FASE 3 / FASE 4) ─────────────────────────── --}}
<div class="modal fade" id="modal-confirmar-accion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-titulo">Confirmar acción</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="modal-body-text"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                {{-- Para actas usamos un form POST; para devoluciones usamos redirect GET --}}
                <form method="POST" id="modal-form-acta" action="" style="display:none;">
                    @csrf
                    <input type="hidden" name="tipo" id="form-tipo">
                    <div id="form-aa-inputs"></div>
                    <button type="submit" class="btn btn-primary" id="modal-submit-btn">Confirmar</button>
                </form>
                <a href="#" class="btn btn-warning text-dark" id="modal-return-btn" style="display:none;">Continuar devolución</a>
            </div>
        </div>
    </div>
</div>

{{-- ─── MODAL: POST-ASIGNACIÓN (FASE 5) ────────────────────────────────────── --}}
@if($mostrarModal && $newAssignmentId)
<div class="modal fade" id="modal-post-asignacion" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-primary">
            <div class="modal-header" style="background:#1d4ed8;color:#fff;">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle mr-2"></i>
                    Asignación creada correctamente
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">Se registraron los activos asignados. ¿Deseas generar el acta de entrega ahora?</p>
                <div class="list-group">
                    {{-- Opción 1: Acta solo con los nuevos activos --}}
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center"
                            onclick="generarActaPostAsignacion('nuevos')">
                        <i class="fas fa-file-signature text-primary fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">Generar acta solo con los activos nuevos</div>
                            <small class="text-muted">Solo los activos de la asignación que acaba de crear</small>
                        </div>
                    </button>
                    {{-- Opción 2: Acta consolidada --}}
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center"
                            onclick="generarActaPostAsignacion('consolidada')">
                        <i class="fas fa-layer-group text-success fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">Generar acta consolidada con todos los activos activos</div>
                            <small class="text-muted">Un solo documento con todos los activos que tiene el colaborador ({{ $activeItems->count() }} en total)</small>
                        </div>
                    </button>
                    {{-- Opción 3: Sin acta por ahora --}}
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center text-muted"
                            data-dismiss="modal">
                        <i class="fas fa-clock fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">No generar acta todavía</div>
                            <small>Agregaré más activos antes o la generaré después desde el expediente</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Form oculto para post-asignación --}}
<form method="POST" id="form-post-asignacion"
      action="{{ route('tech.expediente.acta', $collaborator) }}"
      style="display:none;">
    @csrf
    <input type="hidden" name="tipo" id="post-tipo" value="">
    <input type="hidden" name="nuevo_assignment_id" value="{{ $newAssignmentId }}">
    <div id="post-aa-inputs"></div>
</form>
@endif

@stop

@section('js')
<script>
(function () {
    'use strict';

    // ── Rutas del servidor (Blade → JS) ─────────────────────────────────────
    const ROUTE_ACTA    = @json(route('tech.expediente.acta', $collaborator));
    const ROUTE_RETURN  = @json(route('tech.expediente.return', $collaborator));

    // ── Selectores ───────────────────────────────────────────────────────────
    const selectAll       = document.getElementById('select-all');
    const checkboxes      = document.querySelectorAll('.asset-checkbox');
    const actionBtns      = document.querySelectorAll('.needs-selection');
    const selectionCounts = document.querySelectorAll('.selection-count');
    const hint            = document.getElementById('selection-hint');
    const hintText        = document.getElementById('selection-hint-text');
    const rows            = document.querySelectorAll('.asset-row');

    // ── Seleccionar todos ───────────────────────────────────────────────────
    selectAll.addEventListener('change', function () {
        checkboxes.forEach(cb => { cb.checked = this.checked; });
        updateUI();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateUI();
            selectAll.checked = [...checkboxes].every(c => c.checked);
            selectAll.indeterminate = !selectAll.checked && [...checkboxes].some(c => c.checked);
        });
    });

    // ── Resaltar fila al seleccionar ────────────────────────────────────────
    function updateUI() {
        const selected = getSelectedIds();
        const count    = selected.length;

        // Filas
        rows.forEach(row => {
            const cb = row.querySelector('.asset-checkbox');
            row.classList.toggle('table-primary', cb && cb.checked);
        });

        // Botones que requieren selección
        actionBtns.forEach(btn => { btn.disabled = count === 0; });

        // Contadores en badges
        selectionCounts.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        });

        // Hint
        if (count > 0) {
            hint.style.display = 'block';
            hintText.textContent = count + ' activo' + (count > 1 ? 's' : '') + ' seleccionado' + (count > 1 ? 's' : '');
        } else {
            hint.style.display = 'none';
        }
    }

    // ── Obtener IDs seleccionados ───────────────────────────────────────────
    function getSelectedIds() {
        return [...checkboxes]
            .filter(cb => cb.checked)
            .map(cb => cb.value);
    }

    function getSelectedAssetIds() {
        return [...checkboxes]
            .filter(cb => cb.checked)
            .map(cb => cb.dataset.assetId);
    }

    // ── Dispatcher de acciones ──────────────────────────────────────────────
    // Elementos del modal de confirmación
    const modalEl       = document.getElementById('modal-confirmar-accion');
    const modalTitulo   = document.getElementById('modal-titulo');
    const modalBody     = document.getElementById('modal-body-text');
    const modalFormActa = document.getElementById('modal-form-acta');
    const formTipo      = document.getElementById('form-tipo');
    const formAaInputs  = document.getElementById('form-aa-inputs');
    const modalSubmitBtn = document.getElementById('modal-submit-btn');
    const modalReturnBtn = document.getElementById('modal-return-btn');

    window.submitAction = function (action) {
        const aaIds    = getSelectedIds();
        const allAaIds = [...checkboxes].map(cb => cb.value);

        // Ocultar ambos botones de confirmación; mostrar según acción
        modalFormActa.style.display = 'none';
        modalReturnBtn.style.display = 'none';

        switch (action) {

            // ── Acta con activos seleccionados ──────────────────────────
            case 'acta_seleccionados': {
                if (aaIds.length === 0) return;
                modalTitulo.textContent = 'Generar acta con activos seleccionados';
                modalBody.innerHTML =
                    '<p>Se generará un acta de entrega incluyendo <strong>' + aaIds.length +
                    ' activo' + (aaIds.length > 1 ? 's' : '') + '</strong> seleccionado' +
                    (aaIds.length > 1 ? 's' : '') + '.</p>' +
                    '<p class="text-muted small mb-0">Los activos no seleccionados quedarán pendientes para futuras actas.</p>';

                // Configurar form POST
                modalFormActa.action = ROUTE_ACTA;
                formTipo.value       = 'seleccionados';
                formAaInputs.innerHTML = aaIds
                    .map(id => '<input type="hidden" name="aa_ids[]" value="' + id + '">')
                    .join('');
                modalSubmitBtn.className = 'btn btn-primary';
                modalSubmitBtn.textContent = 'Generar acta';
                modalFormActa.style.display = 'inline-block';
                break;
            }

            // ── Acta consolidada (todos los activos activos) ────────────
            case 'acta_consolidada': {
                modalTitulo.textContent = 'Generar acta consolidada';
                modalBody.innerHTML =
                    '<p>Se generará un <strong>acta consolidada</strong> con <strong>todos los activos TI activos</strong> ' +
                    'del colaborador (<strong>' + allAaIds.length + ' activo' + (allAaIds.length > 1 ? 's' : '') + '</strong>).</p>' +
                    '<p class="text-muted small mb-0">Esta acta agrupará activos de todas las asignaciones activas.</p>';

                // Configurar form POST (sin aa_ids = consolidada completa)
                modalFormActa.action   = ROUTE_ACTA;
                formTipo.value         = 'consolidada';
                formAaInputs.innerHTML = '';
                modalSubmitBtn.className = 'btn btn-success';
                modalSubmitBtn.textContent = 'Generar acta consolidada';
                modalFormActa.style.display = 'inline-block';
                break;
            }

            // ── Devolución parcial ───────────────────────────────────────
            case 'devolucion_parcial': {
                if (aaIds.length === 0) return;
                modalTitulo.textContent = 'Devolución parcial';
                modalBody.innerHTML =
                    '<p>Se registrará la devolución de <strong>' + aaIds.length +
                    ' activo' + (aaIds.length > 1 ? 's' : '') + '</strong> seleccionado' +
                    (aaIds.length > 1 ? 's' : '') + '.</p>' +
                    '<p class="text-muted small mb-0">Los activos no seleccionados continuarán asignados al colaborador.</p>';

                // GET redirect con aa_ids como query string (coma-separados)
                modalReturnBtn.href = ROUTE_RETURN + '?tipo=parcial&aa_ids=' + aaIds.join(',');
                modalReturnBtn.className = 'btn btn-warning text-dark';
                modalReturnBtn.textContent = 'Continuar devolución';
                modalReturnBtn.style.display = 'inline-block';
                break;
            }

            // ── Devolución total ─────────────────────────────────────────
            case 'devolucion_total': {
                modalTitulo.textContent = 'Devolución total';
                modalBody.innerHTML =
                    '<p>Se registrará la devolución de <strong>todos los activos TI</strong> asignados a este colaborador ' +
                    '(<strong>' + allAaIds.length + ' activo' + (allAaIds.length > 1 ? 's' : '') + '</strong>).</p>' +
                    '<div class="alert alert-warning mb-0 small">' +
                    '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                    'Esta acción marcará como devueltos todos los activos activos del colaborador.</div>';

                modalReturnBtn.href = ROUTE_RETURN + '?tipo=total';
                modalReturnBtn.className = 'btn btn-danger';
                modalReturnBtn.textContent = 'Confirmar devolución total';
                modalReturnBtn.style.display = 'inline-block';
                break;
            }

            default: return;
        }

        $(modalEl).modal('show');
    };

    // ── FASE 5: Generar acta desde modal post-asignación ───────────────────
    window.generarActaPostAsignacion = function (tipo) {
        const formPost = document.getElementById('form-post-asignacion');
        if (!formPost) return;

        const postTipo      = document.getElementById('post-tipo');
        const postAaInputs  = document.getElementById('post-aa-inputs');

        if (tipo === 'consolidada') {
            postTipo.value         = 'consolidada';
            postAaInputs.innerHTML = '';
            formPost.submit();
            return;
        }

        // tipo === 'nuevos': obtener los aa_ids de la asignación nueva vía AJAX
        const newAssignmentId = formPost.querySelector('[name="nuevo_assignment_id"]')?.value;
        if (!newAssignmentId) return;

        // Construir aa_ids desde los checkboxes filtrados por data-assignment-id
        const nuevosCbs = [...checkboxes].filter(cb =>
            cb.dataset.assignmentId === String(newAssignmentId)
        );

        if (nuevosCbs.length === 0) {
            // Fallback: usar todos los activos del colaborador si no hay coincidencia
            postTipo.value         = 'consolidada';
            postAaInputs.innerHTML = '';
        } else {
            postTipo.value = 'seleccionados';
            postAaInputs.innerHTML = nuevosCbs
                .map(cb => '<input type="hidden" name="aa_ids[]" value="' + cb.value + '">')
                .join('');
        }

        formPost.submit();
    };

    // ── FASE 5: Auto-abrir modal post-asignación si corresponde ────────────
    @if($mostrarModal && $newAssignmentId)
    $(document).ready(function () {
        $('#modal-post-asignacion').modal('show');
    });
    @endif

})();

// ── Toggle columnas de detalle ───────────────────────────────────────────
(function () {
    var LS_KEY = 'axvos_det_ti_exp';
    var shown  = localStorage.getItem(LS_KEY) === '1';

    function applyDet() {
        document.querySelectorAll('.col-det').forEach(function (el) {
            el.style.display = shown ? '' : 'none';
        });
        var lbl = document.getElementById('lbl-det');
        var btn = document.getElementById('btn-toggle-det');
        if (lbl) lbl.textContent = shown ? 'Ocultar detalles' : 'Ver detalles';
        if (btn) {
            btn.classList.toggle('btn-secondary',         shown);
            btn.classList.toggle('btn-outline-secondary', !shown);
        }
    }

    window.toggleDet = function () {
        shown = !shown;
        localStorage.setItem(LS_KEY, shown ? '1' : '0');
        applyDet();
    };

    document.addEventListener('DOMContentLoaded', applyDet);
})();
</script>
@stop

@section('css')
<style>
    .asset-row.table-primary td { background-color: #dbeafe !important; }
    .asset-row { cursor: pointer; transition: background .1s; }
    .asset-row:hover td { background-color: #f0f7ff; }
    .gap-2 { gap: .5rem; }
    .btn-xs { padding: .15rem .4rem; font-size: .75rem; }
    .font-weight-semibold { font-weight: 600; }
</style>
@stop
