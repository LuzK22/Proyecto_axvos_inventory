@extends('adminlte::page')

@php
    $isArea      = $destinatarioType === 'area';
    $nombre      = $isArea ? $destinatario->name : $destinatario->full_name;
    $subtitulo   = $isArea ? 'Área / Pool' : ($destinatario->position ?? 'Colaborador');
    $sucursal    = $destinatario->branch?->name ?? null;
    $inicial     = strtoupper(substr($nombre, 0, 1));
    $colorGrad   = $isArea ? 'linear-gradient(135deg,#0f766e,#14b8a6)' : 'linear-gradient(135deg,#7c3aed,#a78bfa)';
    $totalActivos = $activeItems->count();
@endphp

@section('title', 'Expediente OTRO — ' . $nombre)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="m-0">
                <i class="fas fa-folder-open text-success mr-2"></i>
                Expediente OTRO
                <small class="text-muted ml-2" style="font-size:.7em;">{{ $nombre }}</small>
            </h1>
        </div>
        <a href="{{ route('assets.assignments.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- ─── FICHA DEL DESTINATARIO ──────────────────────────────────────────────── --}}
<div class="card card-outline card-success mb-3">
    <div class="card-body py-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold"
                     style="width:56px;height:56px;font-size:1.3rem;background:{{ $colorGrad }};">
                    {{ $inicial }}
                </div>
            </div>
            <div class="col">
                <div class="font-weight-bold" style="font-size:1.1rem;">{{ $nombre }}</div>
                <div class="text-muted small">
                    {{ $subtitulo }}
                    @if(!$isArea && $destinatario->area) · {{ $destinatario->area }} @endif
                    @if($sucursal) · {{ $sucursal }} @endif
                </div>
                @if($isArea && $destinatario->description)
                    <div class="text-muted small mt-1">{{ $destinatario->description }}</div>
                @endif
            </div>
            <div class="col-auto">
                @if($isArea)
                    <span class="badge badge-teal px-3 py-2" style="background:#0f766e;color:#fff;font-size:.85rem;">
                        <i class="fas fa-building mr-1"></i>Área
                    </span>
                @else
                    @php
                        $mod = $destinatario->modalidad_trabajo ?? 'presencial';
                        $modClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                        $modLabel = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                    @endphp
                    <span class="badge {{ $modClass }} px-3 py-2" style="font-size:.85rem;">{{ $modLabel }}</span>
                @endif
            </div>
            <div class="col-auto text-center">
                <div class="font-weight-bold" style="font-size:1.5rem;color:#0f766e;">{{ $totalActivos }}</div>
                <div class="text-muted small">activo{{ $totalActivos !== 1 ? 's' : '' }} OTRO</div>
            </div>
        </div>
    </div>
</div>

{{-- ─── FORMULARIO CON CHECKBOXES ──────────────────────────────────────────── --}}
<form id="expediente-form" method="GET" action="#">

<div class="card card-outline card-success mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-boxes mr-1 text-success"></i>
            Activos OTRO activos asignados
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

    {{-- BARRA DE ACCIONES --}}
    <div class="card-header py-2" style="background:#f8fafc;border-top:1px solid #e2e8f0;">
        <div class="d-flex flex-wrap gap-2 align-items-center" id="action-bar">
            <button type="button"
                    class="btn btn-sm btn-primary action-btn needs-selection"
                    disabled
                    onclick="submitAction('acta_seleccionados')">
                <i class="fas fa-file-signature mr-1"></i>
                Generar acta con seleccionados
                <span class="badge badge-light ml-1 selection-count" style="display:none;">0</span>
            </button>

            @if($totalActivos > 0)
            <button type="button"
                    class="btn btn-sm btn-success"
                    onclick="submitAction('acta_consolidada')">
                <i class="fas fa-layer-group mr-1"></i>
                Acta consolidada
                <span class="badge badge-light ml-1">{{ $totalActivos }}</span>
            </button>
            @endif

            <div class="border-left mx-1" style="height:24px;"></div>

            <button type="button"
                    class="btn btn-sm btn-warning action-btn needs-selection"
                    disabled
                    onclick="submitAction('devolucion_parcial')">
                <i class="fas fa-undo mr-1"></i>
                Devolución parcial
                <span class="badge badge-dark ml-1 selection-count" style="display:none;">0</span>
            </button>

            @if($totalActivos > 0)
            <button type="button"
                    class="btn btn-sm btn-danger"
                    onclick="submitAction('devolucion_total')">
                <i class="fas fa-sign-out-alt mr-1"></i>
                Devolución total
            </button>
            @endif

            <div class="border-left mx-1" style="height:24px;"></div>

            @can('assets.assign')
            <a href="{{ $routeNew }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-plus mr-1"></i> Nueva asignación
            </a>
            @endcan
        </div>

        <div id="selection-hint" class="text-muted small mt-1" style="display:none;">
            <i class="fas fa-info-circle mr-1"></i>
            <span id="selection-hint-text"></span>
        </div>
    </div>

    {{-- TABLA DE ACTIVOS --}}
    <div class="card-body p-0">
        @if($activeItems->isEmpty())
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                No hay activos OTRO asignados actualmente.
                @can('assets.assign')
                <div class="mt-3">
                    <a href="{{ $routeNew }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Nueva asignación
                    </a>
                </div>
                @endcan
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
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeItems as $item)
                        @php
                            $asset      = $item->asset;
                            $assignment = $item->assignment;
                        @endphp
                        <tr class="asset-row" data-aa-id="{{ $item->id }}" data-asset-id="{{ $asset->id }}">
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
                            <td class="align-middle font-weight-bold" style="font-family:monospace;white-space:nowrap;">
                                {{ $asset->internal_code }}
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-light border">{{ $asset->type?->name ?? '—' }}</span>
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-semibold">{{ $asset->brand ?? '—' }}</div>
                                <div class="text-muted small">{{ $asset->model ?? '' }}</div>
                            </td>
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
                            <td class="align-middle small text-muted" style="white-space:nowrap;">
                                {{ $item->assigned_at ? \Carbon\Carbon::parse($item->assigned_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="align-middle small">
                                @if($assignment)
                                    <a href="{{ route('assets.assignments.show', $assignment) }}"
                                       class="text-success" title="Ver asignación #{{ $assignment->id }}">
                                        #{{ $assignment->id }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="align-middle text-center">
                                @if($assignment)
                                <a href="{{ route('assets.assignments.show', $assignment) }}"
                                   class="btn btn-xs btn-outline-secondary" title="Ver asignación">
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

{{-- ─── HISTORIAL DE ACTAS ──────────────────────────────────────────────────── --}}
@if($actaIds->isNotEmpty())
<div class="card card-outline card-secondary mb-3">
    <div class="card-header py-2">
        <h3 class="card-title mb-0">
            <i class="fas fa-history mr-1 text-secondary"></i>
            Historial de actas OTRO
            <span class="badge badge-secondary ml-1">{{ $actaIds->count() }}</span>
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>N° Acta</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Generada</th>
                        <th>Firmas</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actaIds as $acta)
                    <tr>
                        <td class="font-weight-bold" style="font-family:monospace;font-size:.85rem;">
                            {{ $acta->acta_number }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $acta->type_color }}">{{ $acta->type_label }}</span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $acta->status_color }}">{{ $acta->status_label }}</span>
                        </td>
                        <td class="small text-muted">{{ $acta->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @php
                                $firmadas = $acta->signatures->whereNotNull('signed_at')->count();
                                $total    = $acta->signatures->count();
                            @endphp
                            <span class="badge {{ $firmadas === $total && $total > 0 ? 'badge-success' : 'badge-warning text-dark' }}">
                                {{ $firmadas }}/{{ $total }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('actas.show', $acta) }}"
                               class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ─── MODAL: CONFIRMAR ACCIÓN ────────────────────────────────────────────── --}}
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

{{-- ─── MODAL: POST-ASIGNACIÓN (FASE 5) ───────────────────────────────────── --}}
@if($mostrarModal && $newAssignmentId)
<div class="modal fade" id="modal-post-asignacion" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-success">
            <div class="modal-header" style="background:#0f766e;color:#fff;">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle mr-2"></i>
                    Asignación creada correctamente
                </h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">¿Deseas generar el acta de entrega ahora?</p>
                <div class="list-group">
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center"
                            onclick="generarActaPostAsignacion('nuevos')">
                        <i class="fas fa-file-signature text-success fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">Solo los activos asignados ahora</div>
                            <small class="text-muted">Acta con únicamente los activos de esta asignación</small>
                        </div>
                    </button>
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center"
                            onclick="generarActaPostAsignacion('consolidada')">
                        <i class="fas fa-layer-group text-primary fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">Acta consolidada con todos los activos</div>
                            <small class="text-muted">{{ $totalActivos }} activo{{ $totalActivos !== 1 ? 's' : '' }} en total asignados a este destinatario</small>
                        </div>
                    </button>
                    <button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center text-muted"
                            data-dismiss="modal">
                        <i class="fas fa-clock fa-lg mr-3"></i>
                        <div>
                            <div class="font-weight-bold">No generar acta todavía</div>
                            <small>Generaré el acta después desde el expediente</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="form-post-asignacion"
      action="{{ $routeActa }}" style="display:none;">
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

    const ROUTE_ACTA   = @json($routeActa);
    const ROUTE_RETURN = @json($routeReturn);

    const selectAll       = document.getElementById('select-all');
    const checkboxes      = document.querySelectorAll('.asset-checkbox');
    const actionBtns      = document.querySelectorAll('.needs-selection');
    const selectionCounts = document.querySelectorAll('.selection-count');
    const hint            = document.getElementById('selection-hint');
    const hintText        = document.getElementById('selection-hint-text');
    const rows            = document.querySelectorAll('.asset-row');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateUI();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateUI();
            if (selectAll) {
                selectAll.checked = [...checkboxes].every(c => c.checked);
                selectAll.indeterminate = !selectAll.checked && [...checkboxes].some(c => c.checked);
            }
        });
    });

    function updateUI() {
        const count = getSelectedIds().length;
        rows.forEach(row => {
            const cb = row.querySelector('.asset-checkbox');
            row.classList.toggle('table-success-light', cb && cb.checked);
        });
        actionBtns.forEach(btn => { btn.disabled = count === 0; });
        selectionCounts.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
        if (count > 0) {
            hint.style.display = 'block';
            hintText.textContent = count + ' activo' + (count > 1 ? 's' : '') + ' seleccionado' + (count > 1 ? 's' : '');
        } else {
            hint.style.display = 'none';
        }
    }

    function getSelectedIds() {
        return [...checkboxes].filter(cb => cb.checked).map(cb => cb.value);
    }

    // ── Elementos del modal ─────────────────────────────────────────────────
    const modalEl        = document.getElementById('modal-confirmar-accion');
    const modalTitulo    = document.getElementById('modal-titulo');
    const modalBody      = document.getElementById('modal-body-text');
    const modalFormActa  = document.getElementById('modal-form-acta');
    const formTipo       = document.getElementById('form-tipo');
    const formAaInputs   = document.getElementById('form-aa-inputs');
    const modalSubmitBtn = document.getElementById('modal-submit-btn');
    const modalReturnBtn = document.getElementById('modal-return-btn');

    window.submitAction = function (action) {
        const aaIds    = getSelectedIds();
        const allAaIds = [...checkboxes].map(cb => cb.value);

        modalFormActa.style.display  = 'none';
        modalReturnBtn.style.display = 'none';

        switch (action) {
            case 'acta_seleccionados': {
                if (aaIds.length === 0) return;
                modalTitulo.textContent = 'Generar acta con activos seleccionados';
                modalBody.innerHTML = '<p>Se generará un acta de entrega con <strong>' + aaIds.length + ' activo' + (aaIds.length > 1 ? 's' : '') + '</strong> seleccionado' + (aaIds.length > 1 ? 's' : '') + '.</p>';
                modalFormActa.action   = ROUTE_ACTA;
                formTipo.value         = 'seleccionados';
                formAaInputs.innerHTML = aaIds.map(id => '<input type="hidden" name="aa_ids[]" value="' + id + '">').join('');
                modalSubmitBtn.className   = 'btn btn-primary';
                modalSubmitBtn.textContent = 'Generar acta';
                modalFormActa.style.display = 'inline-block';
                break;
            }
            case 'acta_consolidada': {
                modalTitulo.textContent = 'Generar acta consolidada';
                modalBody.innerHTML = '<p>Se generará un acta consolidada con <strong>todos los activos OTRO activos</strong> del destinatario (<strong>' + allAaIds.length + ' activo' + (allAaIds.length > 1 ? 's' : '') + '</strong>).</p>';
                modalFormActa.action   = ROUTE_ACTA;
                formTipo.value         = 'consolidada';
                formAaInputs.innerHTML = '';
                modalSubmitBtn.className   = 'btn btn-success';
                modalSubmitBtn.textContent = 'Generar acta consolidada';
                modalFormActa.style.display = 'inline-block';
                break;
            }
            case 'devolucion_parcial': {
                if (aaIds.length === 0) return;
                modalTitulo.textContent = 'Devolución parcial';
                modalBody.innerHTML = '<p>Se registrará la devolución de <strong>' + aaIds.length + ' activo' + (aaIds.length > 1 ? 's' : '') + '</strong> seleccionado' + (aaIds.length > 1 ? 's' : '') + '.</p>';
                modalReturnBtn.href      = ROUTE_RETURN + '?tipo=parcial&aa_ids=' + aaIds.join(',');
                modalReturnBtn.className = 'btn btn-warning text-dark';
                modalReturnBtn.textContent = 'Continuar devolución';
                modalReturnBtn.style.display = 'inline-block';
                break;
            }
            case 'devolucion_total': {
                modalTitulo.textContent = 'Devolución total';
                modalBody.innerHTML = '<p>Se devolverán <strong>todos los activos OTRO</strong> asignados (<strong>' + allAaIds.length + ' activo' + (allAaIds.length > 1 ? 's' : '') + '</strong>).</p><div class="alert alert-warning mb-0 small"><i class="fas fa-exclamation-triangle mr-1"></i>Esta acción marcará todos los activos como devueltos.</div>';
                modalReturnBtn.href      = ROUTE_RETURN + '?tipo=total';
                modalReturnBtn.className = 'btn btn-danger';
                modalReturnBtn.textContent = 'Confirmar devolución total';
                modalReturnBtn.style.display = 'inline-block';
                break;
            }
            default: return;
        }
        $(modalEl).modal('show');
    };

    // ── FASE 5: modal post-asignación ───────────────────────────────────────
    window.generarActaPostAsignacion = function (tipo) {
        const formPost    = document.getElementById('form-post-asignacion');
        if (!formPost) return;
        const postTipo    = document.getElementById('post-tipo');
        const postInputs  = document.getElementById('post-aa-inputs');

        if (tipo === 'consolidada') {
            postTipo.value    = 'consolidada';
            postInputs.innerHTML = '';
            formPost.submit();
            return;
        }

        const newAssignmentId = formPost.querySelector('[name="nuevo_assignment_id"]')?.value;
        const nuevosCbs = [...checkboxes].filter(cb =>
            cb.dataset.assignmentId === String(newAssignmentId)
        );

        if (nuevosCbs.length === 0) {
            postTipo.value       = 'consolidada';
            postInputs.innerHTML = '';
        } else {
            postTipo.value = 'seleccionados';
            postInputs.innerHTML = nuevosCbs
                .map(cb => '<input type="hidden" name="aa_ids[]" value="' + cb.value + '">')
                .join('');
        }
        formPost.submit();
    };

    @if($mostrarModal && $newAssignmentId)
    $(document).ready(function () {
        $('#modal-post-asignacion').modal('show');
    });
    @endif

})();

// ── Toggle columnas de detalle ───────────────────────────────────────────
(function () {
    var LS_KEY = 'axvos_det_otro_exp';
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
    .asset-row.table-success-light td { background-color: #d1fae5 !important; }
    .asset-row { cursor: pointer; transition: background .1s; }
    .asset-row:hover td { background-color: #f0fdf4; }
    .gap-2 { gap: .5rem; }
    .btn-xs { padding: .15rem .4rem; font-size: .75rem; }
    .font-weight-semibold { font-weight: 600; }
</style>
@stop
