@extends('adminlte::page')

@section('title', 'Sucursales')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.hub') }}" class="btn btn-sm btn-secondary mr-3">
                <i class="fas fa-arrow-left mr-1"></i> Administración
            </a>
            <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
                <i class="fas fa-building mr-2" style="color:#00b4d8;"></i> Sucursales
            </h1>
        </div>
        <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Nueva Sucursal
        </a>
    </div>
@stop

@section('content')

    <table class="table table-bordered">
        <tr>
            <th>Nombre</th>
            <th>Ciudad</th>
            <th>Estado</th>
        </tr>

        @foreach($branches as $branch)
        <tr>
            <td>{{ $branch->name }}</td>
            <td>{{ $branch->city }}</td>
            <td>{{ $branch->active ? 'Activa' : 'Inactiva' }}</td>
        </tr>
        @endforeach
    </table>
@stop

