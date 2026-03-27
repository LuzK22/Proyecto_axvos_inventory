@extends('adminlte::page')

@section('title', 'Registrar Otro Activo')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('assets.hub') }}">Otros Activos</a></li>
            <li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Inventario</a></li>
            <li class="breadcrumb-item active">Registrar Activo</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

<form method="POST" action="{{ route('assets.store') }}" id="form-create-asset">
@csrf

<div class="row">

    {{-- ── Columna principal ─────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Card: Identificación --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #7c3aed;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-tag mr-1" style="color:#7c3aed;"></i> Identificación del Activo
                </h6>
            </div>
            <div class="card-body">

                {{-- Tipo — agrupado por subcategoría --}}
                <div class="form-group">
                    <label class="font-weight-bold">Tipo de Activo <span class="text-danger">*</span></label>
                    <select name="asset_type_id" class="form-control" required id="asset_type_select">
                        <option value="">Seleccionar tipo...</option>
                        @php
                            // Agrupar tipos por subcategoría para mejor UX
                            $grouped = $types->groupBy(fn($t) => $t->subcategory ?: 'Sin subcategoría');
                        @endphp
                        @foreach($grouped as $subcat => $grupo)
                            <optgroup label="{{ $subcat }}">
                                @foreach($grupo as $type)
                                    <option value="{{ $type->id }}"
                                            data-prefix="{{ $type->prefix }}"
                                            {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('asset_type_id')<small class="text-danger">{{ $message }}</small>@enderror
                    {{-- Vista previa del código que se generará --}}
                    <small class="text-muted mt-1 d-block">
                        Código que se generará:
                        <code id="code_preview">OTRO-XXX-00001</code>
                    </small>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre / Descripción <span class="text-danger">*</span></label>
                            <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                   placeholder="Ej: Silla, Escritorio, Nevera..." value="{{ old('brand') }}" required>
                            @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Marca <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                                   placeholder="Ej: Rimax, IKEA, Haceb, Genérico..." value="{{ old('model') }}" required>
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">
                                Número de Serie
                                <small class="text-muted font-weight-normal">(si aplica)</small>
                            </label>
                            <input type="text" name="serial" class="form-control @error('serial') is-invalid @enderror"
                                   placeholder="Serial del fabricante (opcional)" value="{{ old('serial') }}">
                            @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">
                                Etiqueta de Inventario
                                <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                                   data-toggle="tooltip" title="Sticker físico para inventario presencial. Ej: INV-0042"></i>
                            </label>
                            <input type="text" name="asset_tag" class="form-control @error('asset_tag') is-invalid @enderror"
                                   placeholder="Ej: INV-0042" value="{{ old('asset_tag') }}">
                            @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">
                        Código Activo Fijo
                        <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                           data-toggle="tooltip" title="Código de contabilidad — SAP, Siigo, etc."></i>
                    </label>
                    <input type="text" name="fixed_asset_code" class="form-control"
                           placeholder="Ej: AF-2024-00312" value="{{ old('fixed_asset_code') }}">
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
                            <select name="property_type" id="property_type" class="form-control"
                                    required onchange="toggleProvider()">
                                <option value="">Seleccionar...</option>
                                <option value="PROPIO"    {{ old('property_type') === 'PROPIO'    ? 'selected' : '' }}>Propio</option>
                                <option value="LEASING"   {{ old('property_type') === 'LEASING'   ? 'selected' : '' }}>Leasing</option>
                                <option value="ALQUILADO" {{ old('property_type') === 'ALQUILADO' ? 'selected' : '' }}>Alquilado</option>
                                <option value="OTRO"      {{ old('property_type') === 'OTRO'      ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('property_type')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Sucursal <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}@if($branch->city) — {{ $branch->city }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>

                {{-- Proveedor: visible solo para LEASING / ALQUILADO --}}
                <div class="form-group" id="provider_row"
                     style="{{ in_array(old('property_type'), ['LEASING','ALQUILADO']) ? '' : 'display:none;' }}">
                    <label class="font-weight-bold">
                        <i class="fas fa-handshake mr-1 text-primary"></i>
                        Proveedor / Empresa Arrendadora <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="provider_name" class="form-control"
                           placeholder="Nombre de la empresa..."
                           value="{{ old('provider_name') }}">
                    @error('provider_name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold">Observaciones</label>
                    <textarea name="observations" class="form-control" rows="3"
                              placeholder="Condición del activo, ubicación específica, notas importantes...">{{ old('observations') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Card: Información Financiera --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #059669;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-dollar-sign mr-1" style="color:#059669;"></i>
                    Información Financiera
                    <small class="text-muted font-weight-normal">(opcional)</small>
                </h6>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Valor de Compra</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" name="purchase_value" class="form-control"
                                       placeholder="0.00" step="0.01" min="0"
                                       value="{{ old('purchase_value') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Fecha de Adquisición</label>
                            <input type="date" name="purchase_date" class="form-control"
                                   value="{{ old('purchase_date') }}" max="{{ date('Y-m-d') }}">
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
                                   placeholder="ej. 10"
                                   value="{{ old('useful_life_years') }}">
                            @error('useful_life_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="font-weight-bold">Método Depreciación</label>
                            <select name="depreciation_method" class="form-control @error('depreciation_method') is-invalid @enderror">
                                <option value="">Seleccionar...</option>
                                <option value="linea_recta"         {{ old('depreciation_method') === 'linea_recta'          ? 'selected' : '' }}>Línea recta</option>
                                <option value="saldo_decreciente"   {{ old('depreciation_method') === 'saldo_decreciente'    ? 'selected' : '' }}>Saldo decreciente</option>
                                <option value="unidades_produccion" {{ old('depreciation_method') === 'unidades_produccion'  ? 'selected' : '' }}>Unidades de producción</option>
                                <option value="no_deprecia"         {{ old('depreciation_method') === 'no_deprecia'          ? 'selected' : '' }}>No deprecia</option>
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
                                       value="{{ old('residual_value') }}">
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
                                   value="{{ old('depreciation_start_date') }}">
                            @error('depreciation_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Cuenta PUC</label>
                            <input type="text" name="account_code" maxlength="20"
                                   class="form-control @error('account_code') is-invalid @enderror"
                                   placeholder="ej. 1520010101"
                                   value="{{ old('account_code') }}">
                            @error('account_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Columna lateral ─────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        <div class="card shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save mr-1"></i> Guardar Activo
                </button>
                <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold text-muted" style="font-size:.78rem;text-transform:uppercase;">
                    <i class="fas fa-info-circle mr-1"></i> Campos requeridos
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Tipo de activo</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Nombre / Descripción</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Marca</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Tipo de propiedad</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Sucursal</li>
                    <li><i class="fas fa-exclamation-circle text-warning mr-1"></i> Serial (recomendado)</li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-magic mr-1" style="color:#7c3aed;"></i>
                    El <strong>Código Interno</strong> (ej: <code>OTRO-SIL-00001</code>) se genera automáticamente
                    al seleccionar el tipo.
                </small>
            </div>
        </div>

        {{-- Enlace para crear nuevo tipo si no existe --}}
        <div class="card shadow-sm border-left-warning">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-tags mr-1 text-warning"></i>
                    ¿El tipo no existe?
                    <a href="{{ route('asset-types.create', 'OTRO') }}" target="_blank">
                        Crear nuevo tipo OTRO
                    </a>
                </small>
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
$(function(){ $('[data-toggle="tooltip"]').tooltip(); });

// Mostrar/ocultar proveedor según tipo de propiedad
function toggleProvider() {
    const val   = document.getElementById('property_type').value;
    const row   = document.getElementById('provider_row');
    const input = row.querySelector('input');
    if (val === 'LEASING' || val === 'ALQUILADO') {
        row.style.display = '';
        input.setAttribute('required', 'required');
    } else {
        row.style.display = 'none';
        input.removeAttribute('required');
        input.value = '';
    }
}

// Vista previa del código según tipo seleccionado
document.getElementById('asset_type_select').addEventListener('change', function() {
    const opt    = this.options[this.selectedIndex];
    const prefix = opt.dataset.prefix;
    const preview = document.getElementById('code_preview');
    preview.textContent = prefix ? prefix + '-00001' : 'OTRO-XXX-00001';
});
</script>
@stop
