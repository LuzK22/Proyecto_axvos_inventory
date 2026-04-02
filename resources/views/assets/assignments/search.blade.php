@extends('adminlte::page')

@section('title', 'Buscar destinatario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">
                <i class="fas fa-search mr-2" style="color:#7c3aed;"></i>
                Buscar destinatario
            </h1>
            <small class="text-muted">Colaboradores, responsables y áreas con activos asignados</small>
        </div>
        <a href="{{ route('assets.assignments.hub') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- Barra de búsqueda + tabs --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('assets.assignments.search') }}" id="searchForm">

            {{-- Tabs de tipo --}}
            <div class="d-flex mb-3" style="gap:6px;">
                @foreach([
                    'all'          => ['Todos',          'secondary', 'fa-list'],
                    'collaborator' => ['Colaboradores',  'primary',   'fa-user'],
                    'manager'      => ['Responsables',   'info',      'fa-user-tie'],
                    'area'         => ['Áreas',          'success',   'fa-map-marker-alt'],
                ] as $tKey => [$tLabel, $tColor, $tIcon])
                    <button type="submit"
                            name="tab"
                            value="{{ $tKey }}"
                            class="btn btn-sm {{ $tab === $tKey ? 'btn-'.$tColor : 'btn-outline-'.$tColor }}">
                        <i class="fas {{ $tIcon }} mr-1"></i>
                        {{ $tLabel }}
                        @if($tKey === 'collaborator')
                            <span class="badge badge-light ml-1">{{ $collaboratorRows->where('destination_type','collaborator')->count() }}</span>
                        @elseif($tKey === 'manager')
                            <span class="badge badge-light ml-1">{{ $collaboratorRows->where('destination_type','jefe')->count() }}</span>
                        @elseif($tKey === 'area')
                            <span class="badge badge-light ml-1">{{ $areaRows->count() }}</span>
                        @else
                            <span class="badge badge-light ml-1">{{ $collaboratorRows->count() + $areaRows->count() }}</span>
                        @endif
                    </button>
                    <input type="hidden" name="q" value="{{ $q }}">
                @endforeach
            </div>

            {{-- Campo búsqueda --}}
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                </div>
                <input type="text"
                       name="q"
                       id="searchInput"
                       class="form-control border-left-0"
                       placeholder="Buscar por nombre, cédula o área..."
                       value="{{ $q }}"
                       autocomplete="off">
                @if($q !== '')
                    <div class="input-group-append">
                        <a href="{{ route('assets.assignments.search', ['tab' => $tab]) }}"
                           class="btn btn-outline-secondary" title="Limpiar">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

@php
    // Filtrar por tab
    $showCollaborators = in_array($tab, ['all', 'collaborator']) && $collaboratorRows->where('destination_type','collaborator')->isNotEmpty();
    $showManagers      = in_array($tab, ['all', 'manager'])      && $collaboratorRows->where('destination_type','jefe')->isNotEmpty();
    $showAreas         = in_array($tab, ['all', 'area'])         && $areaRows->isNotEmpty();
    $hasResults        = $showCollaborators || $showManagers || $showAreas;
@endphp

@if(!$hasResults)
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-search fa-3x mb-3 d-block" style="opacity:.2;"></i>
            @if($q !== '')
                <p class="mb-1">
                    No hay destinatarios con activos que coincidan con
                    <strong>"{{ $q }}"</strong>.
                </p>
                <a href="{{ route('assets.assignments.search', ['tab' => $tab]) }}"
                   class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="fas fa-times mr-1"></i> Limpiar búsqueda
                </a>
            @else
                <p class="mb-0">No hay destinatarios con activos asignados actualmente.</p>
            @endif
        </div>
    </div>
@endif

{{-- ── COLABORADORES ─────────────────────────────────────────────────────── --}}
@if($showCollaborators)
    @include('assets.assignments._search_table', [
        'rows'      => $collaboratorRows->where('destination_type', 'collaborator')->values(),
        'title'     => 'Colaboradores',
        'icon'      => 'fa-user',
        'color'     => '#1d4ed8',
        'badgeClass'=> 'badge-primary',
    ])
@endif

{{-- ── RESPONSABLES / JEFES ─────────────────────────────────────────────── --}}
@if($showManagers)
    @include('assets.assignments._search_table', [
        'rows'      => $collaboratorRows->where('destination_type', 'jefe')->values(),
        'title'     => 'Responsables / Jefes de área',
        'icon'      => 'fa-user-tie',
        'color'     => '#0891b2',
        'badgeClass'=> 'badge-info',
    ])
@endif

{{-- ── ÁREAS ─────────────────────────────────────────────────────────────── --}}
@if($showAreas)
    @include('assets.assignments._search_table', [
        'rows'      => $areaRows,
        'title'     => 'Áreas',
        'icon'      => 'fa-map-marker-alt',
        'color'     => '#059669',
        'badgeClass'=> 'badge-success',
    ])
@endif

@stop

@section('js')
<script>
(function () {
    let _t;
    document.getElementById('searchInput')?.addEventListener('input', function () {
        clearTimeout(_t);
        _t = setTimeout(() => document.getElementById('searchForm').submit(), 350);
    });
})();
</script>
@stop
