@extends('adminlte::page')

@section('title', 'Solicitudes de Baja')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-ban text-danger mr-2"></i> Solicitudes de Baja
            @if($pendingCount > 0)
                <span class="badge badge-danger ml-1">{{ $pendingCount }} pendiente(s)</span>
            @endif
        </h1>
        <a href="{{ route('admin.hub') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('error') }}
    </div>
@endif

{{-- Filtros --}}
<div class="mb-3">
    <div class="btn-group">
        <a href="{{ route('deletion-requests.index', ['filter'=>'pending']) }}"
           class="btn btn-sm {{ $filter==='pending' ? 'btn-warning' : 'btn-outline-warning' }}">
            <i class="fas fa-clock mr-1"></i> Pendientes
        </a>
        <a href="{{ route('deletion-requests.index', ['filter'=>'approved']) }}"
           class="btn btn-sm {{ $filter==='approved' ? 'btn-success' : 'btn-outline-success' }}">
            <i class="fas fa-check mr-1"></i> Aprobadas
        </a>
        <a href="{{ route('deletion-requests.index', ['filter'=>'rejected']) }}"
           class="btn btn-sm {{ $filter==='rejected' ? 'btn-danger' : 'btn-outline-danger' }}">
            <i class="fas fa-times mr-1"></i> Rechazadas
        </a>
        <a href="{{ route('deletion-requests.index', ['filter'=>'all']) }}"
           class="btn btn-sm {{ $filter==='all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
            Todas
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Activo</th>
                    <th>Tipo / Marca</th>
                    <th>Sucursal</th>
                    <th>Motivo</th>
                    <th>Solicitado por</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td>
                            <strong><code>{{ $req->asset->internal_code ?? 'N/A' }}</code></strong>
                        </td>
                        <td>
                            {{ $req->asset->type?->name ?? '-' }}<br>
                            <small class="text-muted">{{ $req->asset->brand }} {{ $req->asset->model }}</small>
                        </td>
                        <td><small>{{ $req->asset->branch?->name ?? '-' }}</small></td>
                        <td>
                            <span class="badge badge-secondary">{{ $req->reason_label }}</span>
                            @if($req->notes)
                                <br><small class="text-muted">{{ Str::limit($req->notes, 60) }}</small>
                            @endif
                        </td>
                        <td><small>{{ $req->requestedBy?->name ?? '-' }}</small></td>
                        <td><small>{{ $req->created_at->format('d/m/Y H:i') }}</small></td>
                        <td>
                            <span class="badge badge-{{ $req->status_color }}">{{ $req->status_label }}</span>
                            @if($req->status === 'rejected' && $req->rejection_notes)
                                <br><small class="text-danger">{{ Str::limit($req->rejection_notes, 40) }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($req->status === 'pending')
                                {{-- Aprobar --}}
                                <form method="POST" action="{{ route('deletion-requests.approve', $req) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-success"
                                            onclick="return confirm('¿Aprobar la baja de {{ $req->asset->internal_code }}? Esta acción cambiará el estado del activo a Baja.')"
                                            title="Aprobar">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                </form>
                                {{-- Rechazar --}}
                                <button type="button" class="btn btn-xs btn-outline-danger"
                                        onclick="openRejectModal({{ $req->id }}, '{{ $req->asset->internal_code }}')"
                                        title="Rechazar">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            @else
                                <small class="text-muted">
                                    {{ $req->resolvedBy?->name ?? '-' }}<br>
                                    {{ $req->resolved_at?->format('d/m/Y') }}
                                </small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                            No hay solicitudes {{ $filter !== 'all' ? $filter.'s' : '' }}
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

{{-- Modal Rechazo --}}
<div class="modal fade" id="modalReject" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form id="formReject" method="POST">@csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times mr-1"></i> Rechazar Solicitud</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Activo: <strong id="rejectCode"></strong></p>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="rejection_notes" class="form-control" rows="3" required
                                  placeholder="Explique por qué se rechaza la solicitud..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger btn-sm">Confirmar Rechazo</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
