@extends('adminlte::page')

@section('title', 'Respaldo y Recuperación')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0" style="color:#0d1b2a;">
                <i class="fas fa-shield-alt text-primary mr-2"></i> Respaldo y Recuperación
            </h1>
            <small class="text-muted">Gestión de respaldos — Estrategia 3-2-1 empresarial</small>
        </div>
        <form method="POST" action="{{ route('admin.backup.run') }}"
              onsubmit="return confirm('¿Generar un respaldo ahora? Esto puede tardar unos segundos.')">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i> Generar respaldo ahora
            </button>
        </form>
    </div>
@endsection

@section('content')
<div class="container-fluid">

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Estado del último respaldo --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="info-box shadow-sm">
                <span class="info-box-icon {{ $lastOk ? 'bg-success' : 'bg-danger' }}">
                    <i class="fas fa-database"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Último respaldo exitoso</span>
                    <span class="info-box-number">
                        {{ $lastOk ? $lastOk->created_at->format('d/m/Y H:i') : 'Ninguno' }}
                    </span>
                    <small class="text-muted">
                        {{ $lastOk ? $lastOk->size_human : '—' }}
                        · {{ $lastOk ? ucfirst($lastOk->type) : '—' }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-info">
                    <i class="fas fa-hdd"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Respaldos en disco local</span>
                    <span class="info-box-number">{{ $diskFiles->count() }} archivos</span>
                    <small class="text-muted">
                        Total: {{ $diskFiles->sum(fn($f) => $f['size']) > 0
                            ? round($diskFiles->sum(fn($f) => $f['size']) / 1048576, 1) . ' MB'
                            : '0 MB' }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box shadow-sm">
                <span class="info-box-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Próximo respaldo automático</span>
                    <span class="info-box-number">
                        {{ now()->addDay()->startOfDay()->addHours(2)->format('d/m/Y 02:00') }}
                    </span>
                    <small class="text-muted">Scheduler diario 2:00 AM</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Aviso SFTP --}}
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="fas fa-server fa-2x mr-3 text-info"></i>
        <div>
            <strong>Respaldo en servidor externo (SFTP) — Pendiente de configurar</strong><br>
            <small>
                Cuando el proveedor entregue las credenciales SFTP, agrégalas en el archivo
                <code>.env</code> (<code>BACKUP_SFTP_HOST</code>, <code>BACKUP_SFTP_USER</code>,
                <code>BACKUP_SFTP_PASS</code>) y activa <code>sftp_backup</code> en
                <code>config/backup.php</code>. Los respaldos se enviarán automáticamente.
            </small>
        </div>
    </div>

    <div class="row">
        {{-- Historial de respaldos --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Historial de respaldos</h5>
                </div>
                <div class="card-body p-0">
                    @if($backups->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay respaldos registrados. Genera el primero ahora.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Generado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($backups as $b)
                                    <tr>
                                        <td>
                                            <small>{{ $b->created_at->format('d/m/Y H:i') }}</small>
                                            <br>
                                            <small class="text-muted">{{ $b->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <small class="text-monospace">{{ Str::limit($b->filename, 35) }}</small>
                                        </td>
                                        <td>{{ $b->size_human }}</td>
                                        <td>
                                            @if($b->type === 'manual')
                                                <span class="badge badge-primary">Manual</span>
                                            @else
                                                <span class="badge badge-secondary">Automático</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($b->status === 'completed')
                                                <span class="badge badge-success">✅ Completado</span>
                                            @elseif($b->status === 'running')
                                                <span class="badge badge-warning">⏳ En proceso</span>
                                            @else
                                                <span class="badge badge-danger"
                                                      title="{{ $b->error_message }}">❌ Fallido</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($b->triggeredBy)
                                                <small>{{ $b->triggeredBy->name }}</small>
                                            @else
                                                <small class="text-muted">Sistema</small>
                                            @endif
                                            @if($b->downloaded_at)
                                                <br>
                                                <small class="text-success">
                                                    <i class="fas fa-download"></i>
                                                    Descargado {{ $b->downloaded_at->format('d/m H:i') }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($b->status === 'completed')
                                                <a href="{{ route('admin.backup.download', $b->filename) }}"
                                                   class="btn btn-xs btn-success" title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                            @if($b->status === 'failed')
                                                <button class="btn btn-xs btn-outline-danger"
                                                        title="{{ $b->error_message }}"
                                                        data-toggle="tooltip">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                </button>
                                            @endif
                                            <form method="POST"
                                                  action="{{ route('admin.backup.destroy', $b) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar este respaldo del historial y del disco?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-secondary"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $backups->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel de recuperación y archivos en disco --}}
        <div class="col-lg-4">
            {{-- Archivos físicos en disco --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-hdd mr-1"></i> Archivos en disco local</h6>
                </div>
                <div class="card-body p-0">
                    @if($diskFiles->isEmpty())
                        <p class="text-center text-muted py-3">
                            <small>No hay archivos de respaldo en disco.</small>
                        </p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($diskFiles->take(7) as $f)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <small class="d-block text-monospace">{{ Str::limit($f['name'], 30) }}</small>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::createFromTimestamp($f['date'])->format('d/m H:i') }}
                                    </small>
                                </div>
                                <div class="d-flex align-items-center" style="gap:4px">
                                    <span class="badge badge-light">
                                        {{ round($f['size'] / 1048576, 1) }} MB
                                    </span>
                                    <a href="{{ route('admin.backup.download', basename($f['name'])) }}"
                                       class="btn btn-xs btn-success">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            {{-- Plan de recuperación --}}
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-first-aid mr-1"></i> Plan de recuperación</h6>
                </div>
                <div class="card-body p-2">
                    <div class="accordion" id="recoveryAccordion">

                        <div class="card mb-1 border">
                            <div class="card-header py-2 px-3 bg-light" id="h1">
                                <button class="btn btn-link btn-sm text-left p-0 text-dark"
                                        data-toggle="collapse" data-target="#r1">
                                    <i class="fas fa-trash-alt text-warning mr-1"></i>
                                    Borrado accidental
                                </button>
                            </div>
                            <div id="r1" class="collapse" data-parent="#recoveryAccordion">
                                <div class="card-body py-2 px-3">
                                    <small>
                                        1. Descargar el respaldo del día anterior.<br>
                                        2. Restaurar solo la tabla afectada desde phpMyAdmin.<br>
                                        3. Registrar el incidente en auditoría.<br>
                                        <strong>Tiempo estimado: 30 minutos.</strong>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-1 border">
                            <div class="card-header py-2 px-3 bg-light" id="h2">
                                <button class="btn btn-link btn-sm text-left p-0 text-dark"
                                        data-toggle="collapse" data-target="#r2">
                                    <i class="fas fa-server text-danger mr-1"></i>
                                    Fallo del servidor
                                </button>
                            </div>
                            <div id="r2" class="collapse" data-parent="#recoveryAccordion">
                                <div class="card-body py-2 px-3">
                                    <small>
                                        1. Instalar XAMPP/servidor nuevo.<br>
                                        2. Clonar repositorio desde GitHub.<br>
                                        3. Restaurar BD desde el backup.<br>
                                        4. Copiar archivos de storage.<br>
                                        <strong>Tiempo estimado: 1-2 horas.</strong>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-1 border">
                            <div class="card-header py-2 px-3 bg-light" id="h3">
                                <button class="btn btn-link btn-sm text-left p-0 text-dark"
                                        data-toggle="collapse" data-target="#r3">
                                    <i class="fas fa-code text-primary mr-1"></i>
                                    Actualización que rompe el sistema
                                </button>
                            </div>
                            <div id="r3" class="collapse" data-parent="#recoveryAccordion">
                                <div class="card-body py-2 px-3">
                                    <small>
                                        1. Identificar el commit problemático en GitHub.<br>
                                        2. Ejecutar: <code>git revert [commit]</code><br>
                                        3. Sistema restaurado en 2 minutos.<br>
                                        <strong>Requiere Git configurado.</strong>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card border">
                            <div class="card-header py-2 px-3 bg-light" id="h4">
                                <button class="btn btn-link btn-sm text-left p-0 text-dark"
                                        data-toggle="collapse" data-target="#r4">
                                    <i class="fas fa-shield-alt text-info mr-1"></i>
                                    Incidente de seguridad
                                </button>
                            </div>
                            <div id="r4" class="collapse" data-parent="#recoveryAccordion">
                                <div class="card-body py-2 px-3">
                                    <small>
                                        1. Desconectar el servidor inmediatamente.<br>
                                        2. Restaurar backup anterior al incidente.<br>
                                        3. Cambiar todas las contraseñas.<br>
                                        4. Revisar activity_log para trazar el acceso.<br>
                                        5. Reportar a la SIC si aplica (Ley 1581).<br>
                                        <strong>Tiempo estimado: 2-4 horas.</strong>
                                    </small>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('js')
<script>
    $('[data-toggle="tooltip"]').tooltip();
    // Auto-refresh si hay backup en proceso
    @if($pending > 0)
    setTimeout(() => location.reload(), 8000);
    @endif
</script>
@endsection
