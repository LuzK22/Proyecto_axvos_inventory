@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-users text-primary mr-2"></i> Usuarios del Sistema
        </h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Nuevo usuario
        </a>
    </div>
@stop

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ optional($user->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">No hay usuarios registrados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
