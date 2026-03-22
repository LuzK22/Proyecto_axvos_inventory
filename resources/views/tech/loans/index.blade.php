@extends('adminlte::page')
@section('title', 'Préstamos TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark"><i class="fas fa-handshake mr-2" style="color:#1e3a8a;"></i>Préstamos TI</h1>
        <small class="text-muted">Préstamos temporales de activos tecnológicos</small>
    </div>
    <div>
        <a href="{{ route('tech.loans.export', request()->all()) }}" class="btn btn-sm btn-success mr-1">
            <i class="fas fa-file-csv mr-1"></i> Exportar
        </a>
        @can('tech.assets.assign')
        <a href="{{ route('tech.loans.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Nuevo Préstamo
        </a>
        @endcan
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="row mb-3">
    @foreach([['activo','primary','handshake',$activoCount,'Activos'],['vencido','danger','exclamation-triangle',$vencidoCount,'Vencidos'],['devuelto','secondary','undo',$devueltoCount,'Devueltos']] as [$f,$c,$ic,$cnt,$lbl])
    <div class="col-4">
        <a href="{{ route('tech.loans.index', ['filter'=>$f]) }}" class="text-decoration-none">
            <div class="info-box shadow-sm mb-0" style="{{ $filter===$f ? '' : 'opacity:.7;' }}">
                <span class="info-box-icon bg-{{ $c }}"><i class="fas fa-{{ $ic }}"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ $lbl }}</span>
                    <span class="info-box-number">{{ $cnt }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline flex-wrap" style="gap:8px;">
            <div class="btn-group btn-group-sm mr-2">
                @foreach(['activo'=>'Activos','vencido'=>'Vencidos','devuelto'=>'Devueltos','all'=>'Todos'] as $f=>$label)
                <a href="{{ route('tech.loans.index', ['filter'=>$f]) }}"
                   class="btn {{ $filter===$f ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
            <input type="hidden" name="filter" value="{{ $filter }}">
            <input type="text" name="collaborator" value="{{ request('collaborator') }}"
                   class="form-control form-control-sm" placeholder="Colaborador / Cédula" style="min-width:180px;">
            <select name="branch_id" class="form-control form-control-sm">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Filtrar</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                <tr>
                    <th class="pl-3">#</th><th>Activo</th><th>Colaborador</th><th>Sucursal</th>
                    <th>Inicio</th><th>Vence</th><th>Estado</th><th>Por</th><th></th>
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
                    <td>
                        <small class="font-weight-bold">{{ $loan->collaborator?->full_name }}</small>
                        <small class="text-muted d-block">{{ $loan->collaborator?->document }}</small>
                    </td>
                    <td><small>{{ $loan->collaborator?->branch?->name ?? '—' }}</small></td>
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
                        <a href="{{ route('tech.loans.show', $loan) }}" class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($loan->status !== 'devuelto')
                        <a href="{{ route('tech.loans.return', $loan) }}" class="btn btn-xs btn-outline-warning ml-1">
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
                        @can('tech.assets.assign')
                        <div class="mt-2">
                            <a href="{{ route('tech.loans.create') }}" class="btn btn-xs btn-outline-primary">
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
@stop
