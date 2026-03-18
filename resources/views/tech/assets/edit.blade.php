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
                            <label class="font-weight-bold">Sucursal <span class="text-danger">*</span></label>
                            <select name="branch_id" class="form-control" required>
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ old('branch_id', $asset->branch_id) == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}@if($branch->city) — {{ $branch->city }}@endif
                                    </option>
                                @endforeach
                            </select>
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
$(function(){ $('[data-toggle="tooltip"]').tooltip(); });
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
</script>
@stop
