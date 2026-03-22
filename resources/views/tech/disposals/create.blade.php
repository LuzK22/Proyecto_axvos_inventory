@extends('adminlte::page')
@section('title', 'Nueva Solicitud de Baja')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-file-medical-alt mr-2 text-danger"></i> Nueva Solicitud de Baja — TI
        </h1>
        <small class="text-muted">Selecciona el activo y completa la justificación. Quedará pendiente de aprobación.</small>
    </div>
    <a href="{{ route('tech.disposals.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="row">
    <div class="col-lg-8">

        {{-- Búsqueda de activo --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2" style="border-left:4px solid #991b1b;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-laptop mr-1 text-danger"></i>
                    Seleccionar Activo TI
                </h6>
            </div>
            <div class="card-body">
                <input type="text" id="assetSearch" class="form-control form-control-sm mb-2"
                       placeholder="Buscar por código, tipo, marca, serial...">

                @if($assets->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    No hay activos TI disponibles para solicitar baja (todos ya tienen solicitud pendiente o están dados de baja).
                </div>
                @else
                <div style="max-height:380px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px;">
                    <table class="table table-sm table-hover mb-0" id="assetTable">
                        <thead class="thead-light" style="position:sticky;top:0;font-size:.72rem;text-transform:uppercase;">
                            <tr>
                                <th style="width:36px;"></th>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Marca / Modelo</th>
                                <th>Estado</th>
                                <th>Sucursal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $asset)
                            <tr class="asset-row" style="cursor:pointer;"
                                data-id="{{ $asset->id }}"
                                data-code="{{ $asset->internal_code }}"
                                data-search="{{ strtolower($asset->internal_code . ' ' . $asset->type?->name . ' ' . $asset->brand . ' ' . $asset->model . ' ' . $asset->serial) }}"
                                onclick="selectAsset(this)">
                                <td class="text-center">
                                    <input type="radio" name="_assetSelect" value="{{ $asset->id }}"
                                           class="asset-radio" style="pointer-events:none;">
                                </td>
                                <td><code style="font-size:.78rem;">{{ $asset->internal_code }}</code></td>
                                <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                                <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                                <td>
                                    @if($asset->status)
                                    <span class="badge badge-pill" style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;font-size:.65rem;">
                                        {{ $asset->status->name }}
                                    </span>
                                    @endif
                                </td>
                                <td><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Formulario de solicitud --}}
        <div class="card shadow-sm" id="formCard" style="{{ $assets->isEmpty() ? 'display:none;' : '' }}">
            <div class="card-header py-2" style="border-left:4px solid #374151;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-edit mr-1"></i> Justificación de Baja
                    <span id="selectedAssetBadge" class="badge badge-danger ml-2" style="display:none;"></span>
                </h6>
            </div>
            <div class="card-body">
                <form id="requestForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="_assetId" id="hiddenAssetId">

                    <div class="form-group">
                        <label class="font-weight-bold">Motivo de Baja <span class="text-danger">*</span></label>
                        <select name="reason" class="form-control" required>
                            <option value="">Seleccionar motivo...</option>
                            <option value="danado">Dañado / Irreparable</option>
                            <option value="obsoleto">Obsoleto / Sin soporte</option>
                            <option value="perdido">Perdido / Robado</option>
                            <option value="venta">Venta</option>
                            <option value="donacion">Donación</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Descripción / Justificación <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" required
                                  placeholder="Describe el estado del activo y por qué se solicita la baja..."></textarea>
                        <small class="text-muted">Mínimo 20 caracteres. Esta información llegará al aprobador.</small>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <div class="col-lg-4">

        <div class="card shadow-sm">
            <div class="card-body">
                <button type="button" id="btnSubmit" class="btn btn-danger btn-block" disabled
                        onclick="submitRequest()">
                    <i class="fas fa-paper-plane mr-1"></i> Enviar Solicitud de Baja
                </button>
                <p class="text-muted small mt-2 mb-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    La solicitud quedará <strong>pendiente de aprobación</strong>.
                    El perfil Aprobador recibirá la notificación y podrá aprobar o rechazar.
                </p>
            </div>
        </div>

        <div class="card shadow-sm mt-3 border-left-warning">
            <div class="card-body py-2 px-3">
                <p class="mb-1 font-weight-bold small text-uppercase text-muted">Activo seleccionado</p>
                <div id="assetSummary" class="text-muted small">
                    <i class="fas fa-mouse-pointer mr-1"></i> Haz clic en un activo de la tabla
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <strong>¿Qué pasa después?</strong><br>
                    1. La solicitud queda en estado <span class="badge badge-warning">Pendiente</span><br>
                    2. El Aprobador la revisa y decide<br>
                    3. Si se aprueba, el activo cambia a estado <span class="badge badge-danger">Baja</span>
                </small>
            </div>
        </div>

    </div>
</div>
@stop

@section('js')
<script>
let selectedAssetId = null;

/* ── Búsqueda en tabla ─────────────────────── */
document.getElementById('assetSearch').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});

/* ── Selección de activo ───────────────────── */
function selectAsset(row) {
    // Quitar selección anterior
    document.querySelectorAll('.asset-row').forEach(r => r.classList.remove('table-danger'));
    document.querySelectorAll('.asset-radio').forEach(r => r.checked = false);

    // Marcar fila seleccionada
    row.classList.add('table-danger');
    row.querySelector('.asset-radio').checked = true;

    selectedAssetId = row.dataset.id;
    const code      = row.dataset.code;

    // Actualizar UI
    document.getElementById('hiddenAssetId').value = selectedAssetId;
    document.getElementById('requestForm').action  = '/assets/' + selectedAssetId + '/deletion-request';
    document.getElementById('selectedAssetBadge').textContent = code;
    document.getElementById('selectedAssetBadge').style.display = '';
    document.getElementById('btnSubmit').disabled = false;
    document.getElementById('assetSummary').innerHTML =
        '<i class="fas fa-laptop mr-1 text-danger"></i><strong>' + code + '</strong>';
}

/* ── Envío del formulario ──────────────────── */
function submitRequest() {
    if (!selectedAssetId) return;
    const form = document.getElementById('requestForm');
    const reason = form.querySelector('[name="reason"]').value;
    const notes  = form.querySelector('[name="notes"]').value;

    if (!reason) { alert('Selecciona el motivo de baja.'); return; }
    if (notes.trim().length < 20) { alert('La descripción debe tener al menos 20 caracteres.'); return; }

    form.submit();
}
</script>
@stop
