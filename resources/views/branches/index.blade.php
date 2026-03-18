@extends('adminlte::page')

@section('title', 'Sucursales')

@section('content_header')
    <h1>Sucursales</h1>
@stop

@section('content')
    <a href="{{ route('branches.create') }}" class="btn btn-primary mb-3">
        Nueva Sucursal
    </a>

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

