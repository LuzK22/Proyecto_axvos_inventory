@extends('adminlte::page')

@php
    $isArea  = $destinatarioType === 'area';
    $nombre  = $isArea ? ($area->name ?? '—') : ($collaborator->full_name ?? '—');
    $titulo  = $tipo === 'total' ? 'Devolución total' : 'Devolución parcial';
    $colorBg = $tipo === 'total' ? '#dc2626' : '#d97706';
@endphp

@section('title', $titulo . ' — ' . $nombre)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-undo text-warning mr-2"></i>
            {{ $titulo }}
            <small class="text-muted ml-2" style="font-size:.7em;">{{ $nombre }}</small>
        </h1>
        <a href="{{ $routeBack }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al expediente
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- Cabecera del destinatario --}}
<div class="card card-outline mb-3" style="border-top:3px solid {{ $colorBg }};">
    <div class="card-body py-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold"
                 style="width:44px;height:44px;font-size:1.1rem;background:{{ $colorBg }};">
                {{ strtoupper(substr($nombre, 0, 1)) }}
            </div>
            <div>
                <div class="font-weight-bold">{{ $nombre }}</div>
                <div class="text-muted small">
                    {{ $isArea ? 'Área / Pool' : ($collaborator->position ?? 'Colaborador') }}
                </div>
            </div>
            <div class="ml-auto">
                <span class="badge px-3 py-2" style="background:{{ $colorBg }};color:#fff;font-size:.85rem;">
                    {{ $titulo }}
                </span>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ $routeReturnStore }}" id="return-form">
    @csrf

    <div class="card card-outline card-warning mb-3">
        <div class="card-header py-2">
            <h3 class="card-title mb-0">
                <i class="fas fa-boxes mr-1 text-warning"></i>
                Activos a devolver
                <span class="badge badge-warning text-dark ml-1">{{ $activeItems->count() }}</span>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;" class="text-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="select-all-return">
                                    <label class="custom-control-label" for="select-all-return"></label>
                                </div>
                            </th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Estado</th>
                            <th>Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeItems as $item)
                        @php $sel = in_array($item->id, $preselected); @endphp
                        <tr class="return-row{{ $sel ? ' table-warning' : '' }}">
                            <td class="text-center align-middle">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input return-checkbox"
                                           id="ret_{{ $item->id }}"
                                           name="aa_ids[]"
                                           value="{{ $item->id }}"
                                           {{ $sel ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="ret_{{ $item->id }}"></label>
                                </div>
                            </td>
                            <td class="align-middle font-weight-bold" style="font-family:monospace;">
                                {{ $item->asset->internal_code }}
                            </td>
                            <td class="align-middle">
                                <span class="badge badge-light border">{{ $item->asset->type?->name ?? '—' }}</span>
                            </td>
                            <td class="align-middle">
                                <div>{{ $item->asset->brand ?? '—' }}</div>
                                <div class="text-muted small">{{ $item->asset->model ?? '' }}</div>
                            </td>
                            <td class="align-middle text-muted small" style="font-family:monospace;">
                                {{ $item->asset->serial ?? '—' }}
                            </td>
                            <td class="align-middle">
                                @if($item->asset->status)
                                    <span class="badge"
                                          style="background:{{ $item->asset->status->color ?? '#6c757d' }};color:#fff;">
                                        {{ $item->asset->status->name }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">—</span>
                                @endif
                            </td>
                            <td class="align-middle small text-muted">
                                {{ $item->assigned_at ? \Carbon\Carbon::parse($item->assigned_at)->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Notas --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-group mb-0">
                <label class="font-weight-bold">
                    <i class="fas fa-comment-alt mr-1 text-muted"></i>
                    Notas de devolución
                    <small class="text-muted font-weight-normal">(opcional)</small>
                </label>
                <textarea name="return_notes"
                          rows="3"
                          class="form-control @error('return_notes') is-invalid @enderror"
                          placeholder="Estado del activo, observaciones, daños visibles...">{{ old('return_notes') }}</textarea>
                @error('return_notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Contador + botones --}}
    <div class="d-flex justify-content-between align-items-center">
        <div id="return-counter" class="text-muted small">
            <i class="fas fa-info-circle mr-1"></i>
            <span id="return-count-text">{{ count($preselected) }} seleccionado{{ count($preselected) !== 1 ? 's' : '' }}</span>
        </div>
        <div>
            <a href="{{ $routeBack }}" class="btn btn-secondary mr-2">Cancelar</a>
            <button type="button"
                    id="btn-confirm-return"
                    class="btn btn-{{ $tipo === 'total' ? 'danger' : 'warning text-dark' }}"
                    onclick="confirmReturn()"
                    {{ count($preselected) === 0 ? 'disabled' : '' }}>
                <i class="fas fa-undo mr-1"></i>
                Confirmar devolución
                <span class="badge badge-light ml-1" id="return-count-badge">{{ count($preselected) }}</span>
            </button>
        </div>
    </div>
</form>
@stop

@section('js')
<script>
(function () {
    const checkboxes   = document.querySelectorAll('.return-checkbox');
    const selectAll    = document.getElementById('select-all-return');
    const countText    = document.getElementById('return-count-text');
    const countBadge   = document.getElementById('return-count-badge');
    const btnConfirm   = document.getElementById('btn-confirm-return');

    function updateCount() {
        const n = [...checkboxes].filter(c => c.checked).length;
        countText.textContent  = n + ' seleccionado' + (n !== 1 ? 's' : '');
        countBadge.textContent = n;
        btnConfirm.disabled    = n === 0;

        document.querySelectorAll('.return-row').forEach(row => {
            const cb = row.querySelector('.return-checkbox');
            row.classList.toggle('table-warning', cb && cb.checked);
        });

        if (selectAll) {
            selectAll.checked       = [...checkboxes].every(c => c.checked);
            selectAll.indeterminate = !selectAll.checked && [...checkboxes].some(c => c.checked);
        }
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateCount();
        });
    }
    updateCount();

    window.confirmReturn = function () {
        const n = [...checkboxes].filter(c => c.checked).length;
        if (n === 0) return;
        if (confirm('¿Confirmar la devolución de ' + n + ' activo' + (n > 1 ? 's' : '') + '?')) {
            document.getElementById('return-form').submit();
        }
    };
})();
</script>
@stop

@section('css')
<style>
    .return-row { cursor: pointer; transition: background .1s; }
    .btn-xs { padding: .15rem .4rem; font-size: .75rem; }
    .gap-3 { gap: .75rem; }
</style>
@stop
