@extends('adminlte::page')

@section('title', 'Registrar Activo TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
            <li class="breadcrumb-item active">Registrar Activo</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

@php
    $expandIdentificacion = old('asset_tag') || old('fixed_asset_code') || old('observations');
    $expandFinanciera     = old('purchase_value') || old('purchase_date') || old('useful_life_years')
                         || old('depreciation_method') || old('residual_value')
                         || old('depreciation_start_date') || old('account_code');
@endphp

<form method="POST" action="{{ route('tech.assets.store') }}" id="form-create-asset">
@csrf

<div class="row">

    {{-- ── Columna principal ─────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- ═══ SECCIÓN 1: Datos básicos (siempre visible) ═══════════════ --}}
        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #1d4ed8;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-tag mr-1 text-primary"></i> Datos Básicos
                    <span class="badge badge-primary ml-1" style="font-size:.65rem;vertical-align:middle;">Requeridos</span>
                </h6>
            </div>
            <div class="card-body">

                <div class="form-group">
                    <label class="font-weight-bold">Tipo de Activo <span class="text-danger">*</span></label>
                    <select name="asset_type_id" class="form-control" required>
                        <option value="">Seleccionar tipo...</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('asset_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_type_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

                <div class="form-group" id="domain_user_row">
                    <label class="font-weight-bold">
                        Usuario de dominio
                        <small class="text-muted font-weight-normal">(portátil, PC, servidor, torre)</small>
                    </label>
                    <input type="text" name="domain_user" class="form-control @error('domain_user') is-invalid @enderror"
                           placeholder="Ej: jgarcia, mlopez"
                           value="{{ old('domain_user') }}">
                    @error('domain_user')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Usuario de dominio asignado por la empresa.</small>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Marca <span class="text-danger">*</span></label>
                            <input type="text" name="brand" class="form-control @error('brand') is-invalid @enderror"
                                   placeholder="Dell, HP, Lenovo..." value="{{ old('brand') }}" required>
                            @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Modelo <span class="text-danger">*</span></label>
                            <input type="text" name="model" class="form-control @error('model') is-invalid @enderror"
                                   placeholder="XPS 15, ThinkPad X1..." value="{{ old('model') }}" required>
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Número de Serie <span class="text-danger">*</span></label>
                    <input type="text" name="serial" class="form-control @error('serial') is-invalid @enderror"
                           placeholder="Serial del fabricante" value="{{ old('serial') }}" required>
                    @error('serial')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">
                        Nombre del equipo
                        <small class="text-muted font-weight-normal">(portÃ¡til, PC de escritorio, servidor)</small>
                    </label>
                    <input type="text" name="hostname" class="form-control @error('hostname') is-invalid @enderror"
                           placeholder="Ej: PC-CONTABILIDAD-01, LAPTOP-JUAN01"
                           value="{{ old('hostname') }}">
                    @error('hostname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Campo manual y diferente al cÃ³digo interno.</small>
                </div>

                <div class="form-row">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Tipo de Propiedad <span class="text-danger">*</span></label>
                            <select name="property_type" id="property_type" class="form-control" required onchange="toggleProvider()">
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
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Sucursal (sede) <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}@if($branch->city) — {{ $branch->city }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')<small class="text-danger">{{ $message }}</small>@enderror
                            <small class="text-muted d-block mt-1">La ciudad se define en la configuraciÃ³n de la sucursal.</small>
                        </div>
                    </div>
                </div>

                {{-- Proveedor: visible solo para LEASING / ALQUILADO --}}
                <div class="form-group mt-3 mb-0" id="provider_row"
                     style="{{ in_array(old('property_type'), ['LEASING','ALQUILADO']) ? '' : 'display:none;' }}">
                    <label class="font-weight-bold">
                        <i class="fas fa-handshake mr-1 text-primary"></i>
                        Proveedor / Empresa Arrendadora <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="provider_name" class="form-control"
                           placeholder="Nombre de la empresa que arrienda o hace leasing..."
                           value="{{ old('provider_name') }}">
                    @error('provider_name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>

            </div>
        </div>

        {{-- ═══ SECCIÓN 2: Identificación adicional (colapsada) ══════════ --}}
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex align-items-center justify-content-between section-toggle"
                 style="border-left:4px solid #374151; cursor:pointer;"
                 data-toggle="collapse" data-target="#seccionIdentificacion"
                 aria-expanded="{{ $expandIdentificacion ? 'true' : 'false' }}">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-barcode mr-1" style="color:#374151;"></i> Identificación Adicional
                    <small class="text-muted font-weight-normal ml-1">— etiqueta física · código contable · notas</small>
                </h6>
                <i class="fas fa-chevron-down toggle-chevron text-muted {{ $expandIdentificacion ? 'rotated' : '' }}"
                   style="font-size:.75rem; transition:transform .2s;"></i>
            </div>
            <div class="collapse {{ $expandIdentificacion ? 'show' : '' }}" id="seccionIdentificacion">
                <div class="card-body">

                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    Etiqueta de Inventario
                                    <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                                       data-toggle="tooltip"
                                       title="Sticker físico pegado al equipo. Ej: INV-0042"></i>
                                </label>
                                <input type="text" name="asset_tag"
                                       class="form-control @error('asset_tag') is-invalid @enderror"
                                       placeholder="Ej: INV-0042" value="{{ old('asset_tag') }}">
                                @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">
                                    Código Activo Fijo
                                    <i class="fas fa-question-circle text-muted" style="font-size:.8rem;"
                                       data-toggle="tooltip"
                                       title="Número de Contabilidad para cruce con SAP, Siigo, etc."></i>
                                </label>
                                <input type="text" name="fixed_asset_code" class="form-control"
                                       placeholder="Ej: AF-2024-00312" value="{{ old('fixed_asset_code') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Observaciones</label>
                        <textarea name="observations" class="form-control" rows="3"
                                  placeholder="Condición del equipo, accesorios incluidos, notas importantes...">{{ old('observations') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- ═══ SECCIÓN 3: Información financiera (colapsada) ════════════ --}}
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex align-items-center justify-content-between section-toggle"
                 style="border-left:4px solid #059669; cursor:pointer;"
                 data-toggle="collapse" data-target="#seccionFinanciera"
                 aria-expanded="{{ $expandFinanciera ? 'true' : 'false' }}">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-dollar-sign mr-1" style="color:#059669;"></i> Información Financiera
                    <small class="text-muted font-weight-normal ml-1">— valor · depreciación NIIF NIC 16</small>
                </h6>
                <i class="fas fa-chevron-down toggle-chevron text-muted {{ $expandFinanciera ? 'rotated' : '' }}"
                   style="font-size:.75rem; transition:transform .2s;"></i>
            </div>
            <div class="collapse {{ $expandFinanciera ? 'show' : '' }}" id="seccionFinanciera">
                <div class="card-body">

                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Valor de Compra</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" name="purchase_value"
                                           class="form-control @error('purchase_value') is-invalid @enderror"
                                           placeholder="0.00" step="0.01" min="0"
                                           value="{{ old('purchase_value') }}">
                                    @error('purchase_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Fecha de Compra</label>
                                <input type="date" name="purchase_date"
                                       class="form-control @error('purchase_date') is-invalid @enderror"
                                       value="{{ old('purchase_date') }}" max="{{ date('Y-m-d') }}">
                                @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <hr class="mt-1 mb-2">
                    <p class="text-muted small mb-2">
                        <i class="fas fa-calculator mr-1" style="color:#059669;"></i>
                        <strong>Campos contables NIIF NIC 16</strong> — depreciación y valor en libros.
                    </p>

                    <div class="form-row">
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold">Vida Útil (años)</label>
                                <input type="number" name="useful_life_years" min="1" max="99"
                                       class="form-control @error('useful_life_years') is-invalid @enderror"
                                       placeholder="ej. 5" value="{{ old('useful_life_years') }}">
                                @error('useful_life_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold">Método Depreciación</label>
                                <select name="depreciation_method"
                                        class="form-control @error('depreciation_method') is-invalid @enderror">
                                    <option value="">Seleccionar...</option>
                                    <option value="linea_recta"          {{ old('depreciation_method') === 'linea_recta'          ? 'selected' : '' }}>Línea recta</option>
                                    <option value="saldo_decreciente"    {{ old('depreciation_method') === 'saldo_decreciente'    ? 'selected' : '' }}>Saldo decreciente</option>
                                    <option value="unidades_produccion"  {{ old('depreciation_method') === 'unidades_produccion'  ? 'selected' : '' }}>Unidades de producción</option>
                                    <option value="no_deprecia"          {{ old('depreciation_method') === 'no_deprecia'          ? 'selected' : '' }}>No deprecia</option>
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
                                           placeholder="0.00" value="{{ old('residual_value') }}">
                                    @error('residual_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Inicio Depreciación</label>
                                <input type="date" name="depreciation_start_date"
                                       class="form-control @error('depreciation_start_date') is-invalid @enderror"
                                       value="{{ old('depreciation_start_date') }}">
                                @error('depreciation_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Cuenta PUC</label>
                                <input type="text" name="account_code" maxlength="20"
                                       class="form-control @error('account_code') is-invalid @enderror"
                                       placeholder="ej. 1524050501" value="{{ old('account_code') }}">
                                @error('account_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- ── Columna lateral ───────────────────────────────────────────── --}}
    <div class="col-lg-4">

        <div class="card shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save mr-1"></i> Guardar Activo
                </button>
                <a href="{{ route('tech.assets.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                    <i class="fas fa-arrow-left mr-1"></i> Volver al Listado
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-left-info">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold text-muted" style="font-size:.78rem;text-transform:uppercase;">
                    <i class="fas fa-info-circle mr-1"></i> Campos requeridos
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Tipo de activo</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Marca y Modelo</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Número de Serie</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Tipo de propiedad</li>
                    <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> Sucursal</li>
                    <li><i class="fas fa-exclamation-circle text-warning mr-1"></i> Proveedor (si es Leasing/Alquilado)</li>
                </ul>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body py-2 px-3">
                <small class="text-muted">
                    <i class="fas fa-magic mr-1 text-primary"></i>
                    El <strong>Código Interno</strong> (ej: <code>TI-POR-00001</code>) se genera automáticamente.
                </small>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body py-2 px-3">
                <small class="text-muted d-block mb-1">
                    <i class="fas fa-layer-group mr-1 text-secondary"></i>
                    <strong>Secciones opcionales</strong>
                </small>
                <small class="text-muted">Haz clic en <em>Identificación Adicional</em> o
                    <em>Información Financiera</em> para expandirlas y completar más datos.</small>
            </div>
        </div>

    </div>

</div>
</form>

@stop

@section('css')
<style>
.card { border-radius: 10px; }

.section-toggle:hover { background-color: rgba(0,0,0,.02); }

.toggle-chevron { transition: transform .25s ease; }
.toggle-chevron.rotated { transform: rotate(180deg); }
</style>
@stop

@section('js')
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();

    // Rotar chevron al expandir/colapsar
    $('.section-toggle').on('click', function () {
        const chevron = $(this).find('.toggle-chevron');
        chevron.toggleClass('rotated');
    });

    // Sincronizar aria-expanded con estado real del collapse
    $('.collapse').on('show.bs.collapse', function () {
        const headerId = '[data-target="#' + this.id + '"]';
        $(headerId).attr('aria-expanded', 'true');
        $(headerId).find('.toggle-chevron').addClass('rotated');
    }).on('hide.bs.collapse', function () {
        const headerId = '[data-target="#' + this.id + '"]';
        $(headerId).attr('aria-expanded', 'false');
        $(headerId).find('.toggle-chevron').removeClass('rotated');
    });

    bindDomainUserVisibility();
    toggleDomainUserByType();
});

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

function bindDomainUserVisibility() {
    const typeSelect = document.querySelector('select[name="asset_type_id"]');
    if (!typeSelect) return;
    typeSelect.addEventListener('change', toggleDomainUserByType);
}

function toggleDomainUserByType() {
    const row = document.getElementById('domain_user_row');
    const input = row ? row.querySelector('input[name="domain_user"]') : null;
    const typeSelect = document.querySelector('select[name="asset_type_id"]');
    if (!row || !input || !typeSelect) return;

    const selectedText = (typeSelect.options[typeSelect.selectedIndex]?.text || '').toLowerCase();
    const applies = /(portatil|portátil|laptop|pc|escritorio|servidor|torre)/.test(selectedText);

    row.style.display = applies ? '' : 'none';
    if (!applies) input.value = '';
}
</script>
@stop
