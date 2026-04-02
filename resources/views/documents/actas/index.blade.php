@extends('adminlte::page')

@section('title', 'Actas Digitales')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('documents.hub') }}">Documentación</a></li>
            <li class="breadcrumb-item active">Actas Digitales</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')
@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show py-2">
        <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header py-2 d-flex align-items-center justify-content-between"
         style="border-left:4px solid #0f766e;">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-file-signature mr-1" style="color:#0f766e;"></i>
            @if($category === 'TI')
                <span class="badge badge-primary mr-1" style="font-size:.78rem;">Actas TI</span>
            @elseif($category === 'OTRO')
                <span class="badge mr-1" style="background:#7c3aed;color:#fff;font-size:.78rem;">Actas Otros Activos</span>
            @else
                Todas las Actas
            @endif
            <span class="badge badge-light ml-1" style="font-size:.7rem;">{{ $actas->total() }}</span>
        </h6>
        <div>
            <a href="{{ route('documents.hub') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>

    {{-- Selector de tipo: solo se muestra cuando no hay tipo preseleccionado --}}
    @php
        $typeMeta = [
            'all'        => ['label'=>'Todas',      'icon'=>'file-signature', 'color'=>'secondary', 'hex'=>'#475569'],
            'entrega'    => ['label'=>'Entrega',    'icon'=>'hand-holding',   'color'=>'primary',   'hex'=>'#1d4ed8'],
            'devolucion' => ['label'=>'Devolución', 'icon'=>'undo',           'color'=>'warning',   'hex'=>'#92400e'],
            'prestamo'   => ['label'=>'Préstamo',   'icon'=>'handshake',      'color'=>'info',      'hex'=>'#0369a1'],
            'baja'       => ['label'=>'Baja',       'icon'=>'ban',            'color'=>'danger',    'hex'=>'#b91c1c'],
        ];
        $activeMeta = $typeMeta[$typeTab] ?? $typeMeta['all'];
    @endphp

    @if($typeTab === 'all')
    {{-- Vista general: mostrar todos los tipos como tabs --}}
    <div class="card-body pb-0 pt-2 border-bottom">
        <div class="d-flex flex-wrap" style="gap:6px;">
            @foreach($typeMeta as $tKey => $tCfg)
            <a href="{{ request()->fullUrlWithQuery(['type' => $tKey, 'filter' => $filter, 'category' => $category]) }}"
               class="btn btn-sm {{ $typeTab === $tKey ? 'btn-'.$tCfg['color'] : 'btn-outline-'.$tCfg['color'] }} mb-2">
                <i class="fas fa-{{ $tCfg['icon'] }} mr-1"></i>
                {{ $tCfg['label'] }}
                <span class="badge badge-light ml-1" style="font-size:.68rem;">{{ $counts[$tKey] ?? 0 }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @else
    {{-- Vista filtrada: solo contexto activo + enlace para ver todas --}}
    <div class="card-body py-2 border-bottom d-flex align-items-center justify-content-between"
         style="background:#fafbfc;">
        <div class="d-flex align-items-center" style="gap:10px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;
                         width:30px;height:30px;border-radius:7px;
                         background:{{ $activeMeta['hex'] }}18;flex-shrink:0;">
                <i class="fas fa-{{ $activeMeta['icon'] }}" style="color:{{ $activeMeta['hex'] }};font-size:.8rem;"></i>
            </span>
            <div>
                <span class="font-weight-bold" style="font-size:.85rem;color:#1e293b;">
                    {{ $activeMeta['label'] }}
                </span>
                <span class="text-muted" style="font-size:.78rem;">
                    &nbsp;·&nbsp;{{ $counts[$typeTab] ?? 0 }} acta(s)
                    @if($category) &nbsp;·&nbsp;
                        @if($category === 'TI')
                            <span class="badge badge-primary" style="font-size:.62rem;">TI</span>
                        @else
                            <span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:.62rem;">OTRO</span>
                        @endif
                    @endif
                </span>
            </div>
        </div>
        <a href="{{ request()->fullUrlWithQuery(['type' => 'all']) }}"
           class="btn btn-xs btn-outline-secondary" style="font-size:.74rem;">
            <i class="fas fa-list mr-1"></i> Ver todos los tipos
        </a>
    </div>
    @endif

    {{-- Filtros por estado --}}
    <div class="card-body pb-0 pt-2">
        <div class="d-flex flex-wrap" style="gap:6px;">
            @foreach(['all' => 'Todos los estados', 'pending' => 'Pendientes', 'signed' => 'Firmadas', 'draft' => 'Borradores'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['filter' => $key, 'category' => $category]) }}"
                   class="btn btn-xs {{ $filter === $key ? 'btn-dark' : 'btn-outline-secondary' }} mb-2">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card-body p-0">
        @if($actas->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-file-signature fa-3x mb-3 d-block" style="opacity:.2;"></i>
                <p class="mb-0">No hay actas en esta categoría</p>
            </div>
        @else
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Nº Acta</th>
                        <th>Tipo</th>
                        <th>Colaborador</th>
                        <th style="width:150px;">Estado</th>
                        <th style="width:120px;">Generada</th>
                        <th style="width:90px;">Firmas</th>
                        <th style="width:70px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actas as $acta)
                    <tr>
                        <td class="font-weight-bold">
                            <a href="{{ route('actas.show', $acta) }}" class="text-dark">
                                {{ $acta->acta_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-pill"
                                  style="background:{{ match($acta->acta_type) { 'entrega'=>'#0f766e', 'devolucion'=>'#92400e', default=>'#475569' } }};color:#fff;font-size:.72rem;">
                                {{ ucfirst($acta->acta_type) }}
                            </span>
                        </td>
                        <td>{{ $acta->assignment->collaborator->full_name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $acta->status_color }} badge-pill" style="font-size:.72rem;">
                                {{ $acta->status_label }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $acta->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @php
                                $signed = $acta->signatures->where('signed_at', '!=', null)->count();
                                $total  = $acta->signatures->count();
                            @endphp
                            <span class="badge {{ $signed === $total && $total > 0 ? 'badge-success' : 'badge-warning' }}">
                                {{ $signed }}/{{ $total }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('actas.show', $acta) }}"
                               class="btn btn-xs btn-outline-primary" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($actas->hasPages())
        <div class="card-footer">
            {{ $actas->links() }}
        </div>
    @endif
</div>

@stop

@section('css')
<style>
.card { border-radius: 10px; }
.table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
.gap-2 { gap: .5rem; }
</style>
@stop
