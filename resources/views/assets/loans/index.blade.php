@extends('adminlte::page')
@php
    $filterConfig = [
        'activo'   => ['label'=>'Préstamos Activos',    'color'=>'#7c3aed', 'icon'=>'handshake',           'badge'=>'primary'],
        'vencido'  => ['label'=>'Préstamos Vencidos',    'color'=>'#991b1b', 'icon'=>'exclamation-triangle', 'badge'=>'danger'],
        'devuelto' => ['label'=>'Historial / Devueltos', 'color'=>'#374151', 'icon'=>'history',              'badge'=>'secondary'],
        'all'      => ['label'=>'Todos los Préstamos',   'color'=>'#065f46', 'icon'=>'list',                 'badge'=>'success'],
    ];
    $current = $filterConfig[$filter] ?? $filterConfig['activo'];
@endphp
@section('title', $current['label'] . ' — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-{{ $current['icon'] }} mr-2" style="color:{{ $current['color'] }};"></i>
            {{ $current['label'] }}
        </h1>
        <small class="text-muted">Préstamos temporales de otros activos (mobiliario, enseres, etc.)</small>
    </div>
    <div>
        <a href="{{ route('assets.loans.hub') }}" class="btn btn-sm btn-secondary mr-1">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <a href="#" class="btn btn-sm btn-success mr-1" data-toggle="modal" data-target="#modalExportLoans">
            <i class="fas fa-file-csv mr-1"></i> Exportar
        </a>
        @can('assets.assign')
        <a href="{{ route('assets.loans.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Nuevo Préstamo
        </a>
        @endcan
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

{{-- Tabs de navegación --}}
<div class="d-flex mb-3" style="gap:6px;flex-wrap:wrap;">
    @foreach($filterConfig as $fKey => $fCfg)
    @php
        $count = match($fKey) {
            'activo'   => $activoCount,
            'vencido'  => $vencidoCount,
            'devuelto' => $devueltoCount,
            default    => $activoCount + $vencidoCount + $devueltoCount,
        };
        $isActive = $filter === $fKey;
    @endphp
    <a href="{{ route('assets.loans.index', array_merge(request()->except('filter'), ['filter'=>$fKey])) }}"
       class="btn btn-sm {{ $isActive ? 'btn-'.$fCfg['badge'] : 'btn-outline-'.$fCfg['badge'] }}"
       style="{{ $isActive ? 'font-weight:600;' : 'opacity:.8;' }}">
        <i class="fas fa-{{ $fCfg['icon'] }} mr-1"></i>
        {{ $fCfg['label'] }}
        <span class="badge badge-light ml-1" style="font-size:.7rem;">{{ $count }}</span>
    </a>
    @endforeach
</div>

{{-- Filtros adicionales --}}
<div class="card shadow-sm mb-3" style="border-left:4px solid {{ $current['color'] }};">
    <div class="card-body py-2">
        <form method="GET" class="form-inline flex-wrap" style="gap:8px;">
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="collaborator" value="{{ request('collaborator') }}"
                   class="form-control form-control-sm" placeholder="Colaborador / Sucursal / Cédula" style="min-width:200px;">
            <select name="branch_id" class="form-control form-control-sm">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Filtrar</button>
            @if(request()->hasAny(['collaborator','branch_id']))
                <a href="{{ route('assets.loans.index', ['filter'=>$filter]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card shadow-sm" style="border-top:3px solid {{ $current['color'] }};">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="font-size:.85rem;">
        <span>
            <i class="fas fa-{{ $current['icon'] }} mr-1" style="color:{{ $current['color'] }};"></i>
            <strong>{{ $current['label'] }}</strong>
            — {{ $loans->total() }} registro(s)
        </span>
        <button type="button" id="btn-toggle-det"
                class="btn btn-xs btn-outline-secondary"
                onclick="toggleDet()" style="font-size:.73rem;">
            <i class="fas fa-table mr-1"></i>
            <span id="lbl-det">Ver detalles</span>
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                <tr>
                    <th class="pl-3">#</th>
                    <th>Activo</th>
                    <th class="col-det">Marca / Modelo</th>
                    <th class="col-det">Serial</th>
                    <th class="col-det">Etiqueta</th>
                    <th class="col-det">Cód. Fijo</th>
                    <th>Destino</th>
                    <th>Tipo destino</th>
                    <th>Inicio</th>
                    <th>Vence</th>
                    <th>Estado</th>
                    <th>Por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                <tr class="{{ $loan->status==='vencido'?'table-warning':'' }}">
                    <td class="pl-3"><small>{{ $loan->id }}</small></td>
                    <td>
                        <code style="font-size:.78rem;">{{ $loan->asset?->internal_code }}</code>
                        <small class="text-muted d-block">{{ $loan->asset?->type?->name }}</small>
                    </td>
                    <td class="col-det">
                        <small class="font-weight-bold">{{ $loan->asset?->brand ?? '—' }}</small>
                        <small class="text-muted d-block">{{ $loan->asset?->model ?? '' }}</small>
                    </td>
                    <td class="col-det">
                        <small style="font-family:monospace;">{{ $loan->asset?->serial ?? '—' }}</small>
                    </td>
                    <td class="col-det">
                        <small style="font-family:monospace;">{{ $loan->asset?->asset_tag ?? '—' }}</small>
                    </td>
                    <td class="col-det">
                        <small style="font-family:monospace;">{{ $loan->asset?->fixed_asset_code ?? '—' }}</small>
                    </td>
                    <td>
                        @if($loan->destination_type === 'branch')
                            <small class="font-weight-bold">
                                <i class="fas fa-building mr-1 text-muted"></i>
                                {{ $loan->destinationBranch?->name ?? '—' }}
                            </small>
                        @else
                            <small class="font-weight-bold">{{ $loan->collaborator?->full_name ?? '—' }}</small>
                            <small class="text-muted d-block">{{ $loan->collaborator?->document }}</small>
                        @endif
                    </td>
                    <td>
                        @if($loan->destination_type === 'branch')
                            <span class="badge badge-pill" style="background:#7c3aed;color:#fff;font-size:.68rem;">Sucursal</span>
                        @else
                            <span class="badge badge-pill badge-info" style="font-size:.68rem;">Colaborador</span>
                        @endif
                    </td>
                    <td><small>{{ $loan->start_date?->format('d/m/Y') }}</small></td>
                    <td>
                        <small class="{{ $loan->status==='vencido'?'text-danger font-weight-bold':'' }}">
                            {{ $loan->end_date?->format('d/m/Y') }}
                        </small>
                        @if($loan->status==='activo')
                            <br><small class="text-muted">{{ $loan->daysRemaining() }} día(s)</small>
                        @elseif($loan->status==='vencido')
                            <br><small class="text-danger">Vencido</small>
                        @endif
                    </td>
                    <td>
                        @php $sc = match($loan->status){'activo'=>'success','vencido'=>'danger',default=>'secondary'}; @endphp
                        <span class="badge badge-{{ $sc }}" style="font-size:.68rem;">{{ ucfirst($loan->status) }}</span>
                    </td>
                    <td><small class="text-muted">{{ $loan->creator?->name ?? '—' }}</small></td>
                    <td>
                        <a href="{{ route('assets.loans.show', $loan) }}" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($loan->status !== 'devuelto')
                        <a href="{{ route('assets.loans.return', $loan) }}" class="btn btn-xs btn-outline-warning ml-1">
                            <i class="fas fa-undo"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-handshake fa-2x d-block mb-2" style="opacity:.2;"></i>
                        No hay préstamos.
                        @can('assets.assign')
                        <div class="mt-2">
                            <a href="{{ route('assets.loans.create') }}" class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-plus mr-1"></i> Registrar préstamo
                            </a>
                        </div>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())<div class="card-footer">{{ $loans->links() }}</div>@endif
</div>

{{-- Modal exportar --}}
<div class="modal fade" id="modalExportLoans" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold">
                    <i class="fas fa-file-csv mr-1 text-success"></i> Exportar Préstamos
                </h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">Selecciona qué préstamos quieres descargar:</p>
                <div class="list-group list-group-flush">
                    <a href="{{ route('assets.loans.export', ['filter'=>'activo']) }}"
                       class="list-group-item list-group-item-action py-2 px-3 {{ $filter==='activo' ? 'active' : '' }}">
                        <i class="fas fa-handshake mr-2"></i>
                        Solo <strong>Activos</strong>
                        <span class="badge badge-light float-right">{{ $activoCount }}</span>
                    </a>
                    <a href="{{ route('assets.loans.export', ['filter'=>'vencido']) }}"
                       class="list-group-item list-group-item-action py-2 px-3 {{ $filter==='vencido' ? 'active' : '' }}">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Solo <strong>Vencidos</strong>
                        <span class="badge badge-light float-right">{{ $vencidoCount }}</span>
                    </a>
                    <a href="{{ route('assets.loans.export', ['filter'=>'devuelto']) }}"
                       class="list-group-item list-group-item-action py-2 px-3 {{ $filter==='devuelto' ? 'active' : '' }}">
                        <i class="fas fa-undo mr-2"></i>
                        Solo <strong>Devueltos</strong>
                        <span class="badge badge-light float-right">{{ $devueltoCount }}</span>
                    </a>
                    <a href="{{ route('assets.loans.export') }}"
                       class="list-group-item list-group-item-action py-2 px-3 {{ $filter==='all' ? 'active' : '' }}">
                        <i class="fas fa-list mr-2"></i>
                        <strong>Todos</strong> los préstamos
                        <span class="badge badge-light float-right">{{ $activoCount + $vencidoCount + $devueltoCount }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
(function () {
    var LS_KEY = 'axvos_det_otro_loans';
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
