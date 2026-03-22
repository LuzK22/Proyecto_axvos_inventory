@extends('adminlte::page')
@section('title', 'Solicitudes de Baja — ' . $category)

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-ban mr-2 text-danger"></i>
            Solicitudes de Baja — {{ $category === 'TI' ? 'Tecnología' : 'Otros Activos' }}
            @if($pendingCount > 0)
                <span class="badge badge-danger ml-1">{{ $pendingCount }} pendiente(s)</span>
            @endif
        </h1>
        <small class="text-muted">Historial de solicitudes de desincorporación de activos</small>
    </div>
    <div>
        @can($category === 'TI' ? 'tech.assets.disposal.request' : 'assets.disposal.request')
        <a href="{{ route($category === 'TI' ? 'tech.disposals.create' : 'assets.disposals.create') }}"
           class="btn btn-danger btn-sm mr-2">
            <i class="fas fa-plus mr-1"></i> Nueva Solicitud
        </a>
        @endcan
        <a href="{{ route($category === 'TI' ? 'tech.disposals.hub' : 'assets.disposals.hub') }}"
           class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@stop

@section('content')
@include('partials._alerts')

{{-- Filtros de estado --}}
<div class="mb-3">
    <div class="btn-group">
        <a href="{{ route($selfRoute, ['filter'=>'pending']) }}"
           class="btn btn-sm {{ $filter==='pending' ? 'btn-warning' : 'btn-outline-warning' }}">
            <i class="fas fa-clock mr-1"></i> Pendientes
            @if($pendingCount > 0)<span class="badge badge-light ml-1">{{ $pendingCount }}</span>@endif
        </a>
        <a href="{{ route($selfRoute, ['filter'=>'approved']) }}"
           class="btn btn-sm {{ $filter==='approved' ? 'btn-success' : 'btn-outline-success' }}">
            <i class="fas fa-check mr-1"></i> Aprobadas
        </a>
        <a href="{{ route($selfRoute, ['filter'=>'rejected']) }}"
           class="btn btn-sm {{ $filter==='rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
            <i class="fas fa-times mr-1"></i> Rechazadas
        </a>
        <a href="{{ route($selfRoute, ['filter'=>'all']) }}"
           class="btn btn-sm {{ $filter==='all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
            Todas
        </a>
    </div>
    @can('assets.approve.deletion')
    <a href="{{ route('deletion-requests.index', ['filter'=>'pending']) }}"
       class="btn btn-sm btn-outline-primary ml-2">
        <i class="fas fa-user-shield mr-1"></i> Vista Aprobador
    </a>
    @endcan
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3">Activo</th>
                    <th>Tipo / Descripción</th>
                    <th>Sucursal</th>
                    <th>Motivo</th>
                    <th>Solicitado por</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-center">Resolución</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="pl-3">
                        <code style="font-size:.78rem;">{{ $req->asset->internal_code ?? 'N/A' }}</code>
                    </td>
                    <td>
                        <small>{{ $req->asset->type?->name ?? '—' }}</small><br>
                        <small class="text-muted">{{ $req->asset->brand }} {{ $req->asset->model }}</small>
                    </td>
                    <td><small>{{ $req->asset->branch?->name ?? '—' }}</small></td>
                    <td>
                        <span class="badge badge-secondary" style="font-size:.7rem;">{{ $req->reason_label }}</span>
                        @if($req->notes)
                            <br><small class="text-muted">{{ Str::limit($req->notes, 55) }}</small>
                        @endif
                    </td>
                    <td><small>{{ $req->requestedBy?->name ?? '—' }}</small></td>
                    <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                    <td>
                        @php
                            $colors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                            $labels = ['pending'=>'Pendiente','approved'=>'Aprobada','rejected'=>'Rechazada'];
                        @endphp
                        <span class="badge badge-{{ $colors[$req->status] ?? 'secondary' }}" style="font-size:.7rem;">
                            {{ $labels[$req->status] ?? $req->status }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($req->status === 'pending')
                            @can('assets.approve.deletion')
                            <form method="POST" action="{{ route('deletion-requests.approve', $req) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-success"
                                        onclick="return confirm('¿Aprobar la baja de {{ $req->asset->internal_code }}?')"
                                        title="Aprobar">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                            </form>
                            <button type="button" class="btn btn-xs btn-outline-danger"
                                    onclick="openRejectModal({{ $req->id }}, '{{ $req->asset->internal_code }}')"
                                    title="Rechazar">
                                <i class="fas fa-times"></i>
                            </button>
                            @else
                            <small class="text-muted">Esperando aprobación</small>
                            @endcan
                        @else
                            <small class="text-muted">
                                {{ $req->resolvedBy?->name ?? '—' }}<br>
                                {{ $req->resolved_at?->format('d/m/Y') }}
                            </small>
                            @if($req->status === 'rejected' && $req->rejection_notes)
                                <br><small class="text-danger">{{ Str::limit($req->rejection_notes, 40) }}</small>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x d-block mb-2" style="opacity:.2;"></i>
                        No hay solicitudes
                        {{ $filter === 'pending' ? 'pendientes' : ($filter === 'approved' ? 'aprobadas' : ($filter === 'rejected' ? 'rechazadas' : '')) }}.
                        @if($filter === 'pending')
                        <div class="mt-2">
                            @can($category === 'TI' ? 'tech.assets.disposal.request' : 'assets.disposal.request')
                            <a href="{{ route($category === 'TI' ? 'tech.disposals.create' : 'assets.disposals.create') }}"
                               class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-plus mr-1"></i> Crear primera solicitud
                            </a>
                            @endcan
                        </div>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="card-footer">{{ $requests->links() }}</div>
    @endif
</div>

{{-- Modal Rechazo (solo visible para aprobadores) --}}
@can('assets.approve.deletion')
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formReject" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title mb-0"><i class="fas fa-times mr-1"></i> Rechazar Solicitud</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small mb-2">Activo: <strong id="rejectCode"></strong></p>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control form-control-sm" rows="3" required
                                  placeholder="Explique por qué se rechaza..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Rechazar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@stop

@section('js')
<script>
function openRejectModal(reqId, code) {
    document.getElementById('rejectCode').textContent = code;
    document.getElementById('formReject').action = '/admin/deletion-requests/' + reqId + '/reject';
    document.getElementById('formReject').querySelector('textarea').value = '';
    $('#modalReject').modal('show');
}
</script>
@stop
