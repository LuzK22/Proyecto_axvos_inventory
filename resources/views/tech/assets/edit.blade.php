@extends('adminlte::page')

@section('title', 'Editar Activo TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.index') }}">Listado</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.show', $asset) }}">{{ $asset->internal_code }}</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

<form method="POST" action="{{ route('tech.assets.update', $asset) }}">
@csrf @method('PUT')

<div class="row">

    {{-- ── Columna principal ─────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Card: Identificación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #1d4ed8;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-tag mr-1 text-primary"></i> Identificación del Activo
                </h6>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label class="font-weight-bold text-muted">Código Interno</label>
                    <input type="text" class="form-control form-control-sm bg-light"
                           value="{{ $asset->internal_code }}" readonly>
                    <small class="text-muted">El código interno no se puede modificar.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Tipo de Activo <span class="text-danger">*</span></label>
                    <select name="asset_type_id" class="form-control" required>
                        <option value="">Seleccionar tipo...</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}"
                                {{ old('asset_type_id', $asset->asset_type_id) == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_type_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Marca <span class="text-danger">*</span></label>
                            <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                   value="{{ old('brand', $asset->brand) }}" required>
                            @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model', $asset->model) }}" required>
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Número de Serie <span class="text-danger">*</span></label>
                            <input type="text" name="serial" class="form-control @error('serial') is-invalid @enderror"
                                   value="{{ old('serial', $asset->serial) }}" required>
                            @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">
                                Etiqueta de Inventario
                                <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                                   data-toggle="tooltip" title="Sticker físico pegado al equipo. Ej: INV-0042"></i>
                            </label>
                            <input type="text" name="asset_tag" class="form-control @error('asset_tag') is-invalid @enderror"
                                   placeholder="Ej: INV-0042" value="{{ old('asset_tag', $asset->asset_tag) }}">
                            @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">
                        Nombre del equipo
                        <small class="text-muted font-weight-normal">(portÃ¡til, PC de escritorio, servidor)</small>
                    </label>
                    <input type="text" name="hostname" class="form-control @error('hostname') is-invalid @enderror"
                           placeholder="Ej: PC-CONTABILIDAD-01, LAPTOP-JUAN01"
                           value="{{ old('hostname', $asset->hostname) }}">
                    @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Campo manual y diferente al cÃ³digo interno.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">
                        Usuario de dominio
                        <small class="text-muted font-weight-normal">(portátil, PC, servidor, torre)</small>
                    </label>
                    <input type="text" name="domain_user" id="domain_user_input"
                           class="form-control @error('domain_user') is-invalid @enderror"
                           placeholder="Ej: jgarcia, mlopez"
                           value="{{ old('domain_user', $asset->domain_user) }}">
                    @error('domain_user')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Usuario de dominio asignado por la empresa.</small>
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">
                        Código Activo Fijo
                        <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                           data-toggle="tooltip" title="Número contable asignado por Finanzas/Contabilidad"></i>
                    </label>
                    <input type="text" name="fixed_asset_code" class="form-control"
                           placeholder="Ej: AF-2024-00312"
                           value="{{ old('fixed_asset_code', $asset->fixed_asset_code) }}">
                </div>

            </div>
        </div>

        {{-- Card: Propiedad y Ubicación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #374151;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-building mr-1" style="color:#374151;"></i> Propiedad y Ubicación
                </h6>
            </div>
            <div class="card-body">

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Tipo de Propiedad <span class="text-danger">*</span></label>
                            <select name="property_type" id="property_type" class="form-control" required onchange="toggleProvider()">
                                <option value="">Seleccionar...</option>
                                <option value="PROPIO"    {{ old('property_type', $asset->property_type) === 'PROPIO'    ? 'selected' : '' }}>Propio</option>
                                <option value="LEASING"   {{ old('property_type', $asset->property_type) === 'LEASING'   ? 'selected' : '' }}>Leasing</option>
                                <option value="ALQUILADO" {{ old('property_type', $asset->property_type) === 'ALQUILADO' ? 'selected' : '' }}>Alquilado</option>
                                <option value="OTRO"      {{ old('property_type', $asset->property_type) === 'OTRO'      ? 'selected' : '' }}>Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Sucursal (sede) <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('branch_id', $asset->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}@if($branch->city) — {{ $branch->city }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">La ciudad se define en la configuraciÃ³n de la sucursal.</small>
                        </div>
                    </div>
                </div>

                {{-- Proveedor para LEASING / ALQUILADO --}}
                @php $showProvider = in_array(old('property_type', $asset->property_type), ['LEASING','ALQUILADO']); @endphp
                <div class="form-group" id="provider_row" style="{{ $showProvider ? '' : 'display:none;' }}">
                    <label class="font-weight-bold">
                        <i class="fas fa-handshake mr-1 text-primary"></i>
                        Proveedor / Empresa Arrendadora
                    </label>
                    <input type="text" name="provider_name" class="form-control"
                           placeholder="Nombre de la empresa..."
                           value="{{ old('provider_name', $asset->provider_name) }}">
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">Observaciones</label>
                    <textarea name="observations" class="form-control" rows="3"
                              placeholder="Condición del equipo, accesorios, notas...">{{ old('observations', $asset->observations) }}</textarea>
                </div>

            </div>
        </div>

        {{-- Card: Información Financiera --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #059669;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-dollar-sign mr-1" style="color:#059669;"></i> Información Financiera
                    <small class="text-muted font-weight-normal">(opcional)</small>
                </h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Valor de Compra</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="purchase_value" class="form-control"
                                       placeholder="0.00" step="0.01" min="0"
                                       value="{{ old('purchase_value', $asset->purchase_value) }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Fecha de Compra</label>
                            <input type="date" name="purchase_date" class="form-control"
                                   value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                {{-- ── Campos NIIF NIC 16 ── --}}
                <hr class="mt-3 mb-2">
                <p class="text-muted small mb-2">
                    <i class="fas fa-calculator mr-1" style="color:#059669;"></i>
                    <strong>Campos contables NIIF NIC 16</strong> — usados para calcular depreciación y valor en libros en los reportes.
                </p>
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold">Vida Útil (años)</label>
                            <input type="number" name="useful_life_years" min="1" max="99"
                                   class="form-control @error('useful_life_years') is-invalid @enderror"
                                   placeholder="ej. 5"
                                   value="{{ old('useful_life_years', $asset->useful_life_years) }}">
                            @error('useful_life_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold">Método Depreciación</label>
                            <select name="depreciation_method" class="form-control @error('depreciation_method') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                <option value="linea_recta"         {{ old('depreciation_method', $asset->depreciation_method) === 'linea_recta'          ? 'selected' : '' }}>Línea recta</option>
                                <option value="saldo_decreciente"   {{ old('depreciation_method', $asset->depreciation_method) === 'saldo_decreciente'    ? 'selected' : '' }}>Saldo decreciente</option>
                                <option value="unidades_produccion" {{ old('depreciation_method', $asset->depreciation_method) === 'unidades_produccion'  ? 'selected' : '' }}>Unidades de producción</option>
                                <option value="no_deprecia"         {{ old('depreciation_method', $asset->depreciation_method) === 'no_deprecia'          ? 'selected' : '' }}>No deprecia</option>
                            </select>
                            @error('depreciation_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold">Valor Residual</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" name="residual_value" min="0" step="0.01"
                                       class="form-control @error('residual_value') is-invalid @enderror"
                                       placeholder="0.00"
                                       value="{{ old('residual_value', $asset->residual_value) }}">
                                @error('residual_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Inicio Depreciación</label>
                            <input type="date" name="depreciation_start_date"
                                   class="form-control @error('depreciation_start_date') is-invalid @enderror"
                                   value="{{ old('depreciation_start_date', $asset->depreciation_start_date?->format('Y-m-d')) }}">
                            @error('depreciation_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Cuenta PUC</label>
                            <input type="text" name="account_code" maxlength="20"
                                   class="form-control @error('account_code') is-invalid @enderror"
                                   placeholder="ej. 1524050501"
                                   value="{{ old('account_code', $asset->account_code) }}">
                            @error('account_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Columna lateral ───────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Estado actual --}}
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold text-muted" style="font-size:.78rem;text-transform:uppercase;">Estado Actual</h6>
            </div>
            <div class="card-body py-3 text-center">
                <span class="badge badge-{{ $asset->status?->color ?? 'secondary' }} px-3 py-2" style="font-size:.85rem;">
                    {{ $asset->status?->name ?? 'Sin estado' }}
                </span>
                <p class="text-muted small mt-2 mb-0">
                    El estado se cambia desde la vista de detalle del activo.
                </p>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
                <a href="{{ route('tech.assets.show', $asset) }}" class="btn btn-outline-secondary btn-block mt-2">
                    <i class="fas fa-eye mr-1"></i> Ver Detalle
                </a>
                <a href="{{ route('tech.assets.index') }}" class="btn btn-link btn-block btn-sm text-muted mt-1">
                    <i class="fas fa-list mr-1"></i> Volver al Listado
                </a>
            </div>
        </div>

        {{-- Resumen --}}
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold text-muted" style="font-size:.78rem;text-transform:uppercase;">Resumen</h6>
            </div>
            <div class="card-body py-2 px-3">
                <dl class="row small mb-0">
                    <dt class="col-6 text-muted">Código:</dt>
                    <dd class="col-6"><code>{{ $asset->internal_code }}</code></dd>
                    <dt class="col-6 text-muted">Registrado:</dt>
                    <dd class="col-6">{{ $asset->created_at->format('d/m/Y') }}</dd>
                    <dt class="col-6 text-muted">Modificado:</dt>
                    <dd class="col-6">{{ $asset->updated_at->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>

    </div>
</div>
</form>
@stop

@section('css')
<style>.card { border-radius: 10px; }</style>
@stop

@section('js')
<script>
$(function(){
    $('[data-toggle="tooltip"]').tooltip();
    bindDomainUserVisibility();
    toggleDomainUserByType();
});
function toggleProvider() {
    const val = document.getElementById('property_type').value;
    const row = document.getElementById('provider_row');
    const input = row.querySelector('input');
    if (val === 'LEASING' || val === 'ALQUILADO') {
        row.style.display = '';
    } else {
        row.style.display = 'none';
        input.value = '';
    }
}

function bindDomainUserVisibility() {
    const typeSelect = document.querySelector('select[name="asset_type_id"]');
    if (!typeSelect) return;
    typeSelect.addEventListener('change', toggleDomainUserByType);
}

function toggleDomainUserByType() {
    const input = document.getElementById('domain_user_input');
    const row = input ? input.closest('.form-group') : null;
    const typeSelect = document.querySelector('select[name="asset_type_id"]');
    if (!row || !input || !typeSelect) return;

    const selectedText = (typeSelect.options[typeSelect.selectedIndex]?.text || '').toLowerCase();
    const appliesByType = /(portatil|portátil|laptop|pc|escritorio|servidor|torre)/.test(selectedText);
    const keepVisibleByData = (input.value || '').trim() !== '';

    row.style.display = (appliesByType || keepVisibleByData) ? '' : 'none';
}
</script>
@stop
