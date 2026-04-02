@extends('adminlte::page')

@section('title', 'Devolución TI — ' . $collaborator->full_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-undo text-warning mr-2"></i>
            Registrar Devolución TI
            <small class="text-muted ml-2" style="font-size:.7em;">{{ $collaborator->full_name }}</small>
        </h1>
        <a href="{{ route('tech.expediente.show', $collaborator) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al expediente
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

<form method="POST" action="{{ route('tech.expediente.return.store', $collaborator) }}" id="return-form">
    @csrf

    {{-- ─── FICHA COLABORADOR ──────────────────────────────────────────── --}}
    <div class="card card-outline card-warning mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold"
                         style="width:48px;height:48px;font-size:1.1rem;background:linear-gradient(135deg,#d97706,#f59e0b);">
                        {{ strtoupper(substr($collaborator->full_name, 0, 1)) }}
                    </div>
                </div>
                <div class="col">
                    <strong>{{ $collaborator->full_name }}</strong>
                    <div class="text-muted small">{{ $collaborator->position ?? '' }}{{ $collaborator->area ? ' · ' . $collaborator->area : '' }}</div>
                </div>
                <div class="col-auto">
                    @php
                        $tipo === 'total'
                            ? $tipoBadge = ['danger', 'Devolución total']
                            : $tipoBadge = ['warning text-dark', 'Devolución parcial'];
                    @endphp
                    <span class="badge badge-{{ $tipoBadge[0] }} px-3 py-2">{{ $tipoBadge[1] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── SELECCIÓN DE ACTIVOS ───────────────────────────────────────── --}}
    <div class="card card-outline card-warning mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="fas fa-laptop mr-1 text-warning"></i>
                Activos a devolver
            </h3>
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="select-all">
                <label class="custom-control-label font-weight-bold" for="select-all">Todos</label>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;" class="text-center">&nbsp;</th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Asignado</th>
                            <th>Asignación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeItems as $item)
                        @php $presel = in_array($item->id, $preselected); @endphp
                        <tr class="asset-row {{ $presel ? 'table-warning' : '' }}">
                            <td class="text-center align-middle">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input asset-checkbox"
                                           id="aa_{{ $item->id }}"
                                           name="aa_ids[]"
                                           value="{{ $item->id }}"
                                           {{ $presel ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="aa_{{ $item->id }}"></label>
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
                            <td class="align-middle small text-muted">
                                {{ $item->assigned_at ? \Carbon\Carbon::parse($item->assigned_at)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="align-middle small">
                                @if($item->assignment)
                                    <a href="{{ route('tech.assignments.show', $item->assignment) }}" class="text-primary">#{{ $item->assignment->id }}</a>
                                @else —
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─── OBSERVACIONES ──────────────────────────────────────────────── --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-header py-2">
            <h3 class="card-title mb-0">
                <i class="fas fa-comment-alt mr-1"></i> Observaciones de devolución
            </h3>
        </div>
        <div class="card-body">
            <textarea name="return_notes"
                      class="form-control @error('return_notes') is-invalid @enderror"
                      rows="3"
                      placeholder="Estado del activo al momento de la devolución, observaciones, daños, etc. (opcional)">{{ old('return_notes') }}</textarea>
            @error('return_notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- ─── BOTONES ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('tech.expediente.show', $collaborator) }}" class="btn btn-secondary">
            <i class="fas fa-times mr-1"></i> Cancelar
        </a>
        <div class="d-flex align-items-center gap-2">
            <div class="text-muted small mr-3" id="counter-text"></div>
            <button type="submit"
                    class="btn btn-warning text-dark font-weight-bold"
                    id="btn-submit"
                    disabled>
                <i class="fas fa-undo mr-1"></i>
                Registrar devolución
                <span class="badge badge-dark ml-1" id="submit-count">0</span>
            </button>
        </div>
    </div>

</form>
@stop

@section('js')
<script>
(function () {
    'use strict';

    const selectAll   = document.getElementById('select-all');
    const checkboxes  = document.querySelectorAll('.asset-checkbox');
    const rows        = document.querySelectorAll('.asset-row');
    const btnSubmit   = document.getElementById('btn-submit');
    const submitCount = document.getElementById('submit-count');
    const counterText = document.getElementById('counter-text');

    function update() {
        const checked = [...checkboxes].filter(c => c.checked);
        const count   = checked.length;

        btnSubmit.disabled = count === 0;
        submitCount.textContent = count;

        rows.forEach(row => {
            const cb = row.querySelector('.asset-checkbox');
            if (cb) {
                row.classList.toggle('table-warning', cb.checked);
            }
        });

        counterText.textContent = count > 0
            ? count + ' activo' + (count > 1 ? 's' : '') + ' seleccionado' + (count > 1 ? 's' : '')
            : '';

        selectAll.checked = count === checkboxes.length && checkboxes.length > 0;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }

    selectAll.addEventListener('change', function () {
        checkboxes.forEach(cb => { cb.checked = this.checked; });
        update();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', update));

    // Confirmar antes de enviar
    document.getElementById('return-form').addEventListener('submit', function (e) {
        const count = [...checkboxes].filter(c => c.checked).length;
        if (!confirm('¿Confirmas la devolución de ' + count + ' activo' + (count > 1 ? 's' : '') + '?\n\nEsta acción generará el Acta de Devolución.')) {
            e.preventDefault();
        }
    });

    // Inicializar estado de los checkboxes preseleccionados
    update();
})();
</script>
@stop

@section('css')
<style>
    .asset-row { cursor: pointer; transition: background .1s; }
    .gap-2 { gap: .5rem; }
</style>
@stop
