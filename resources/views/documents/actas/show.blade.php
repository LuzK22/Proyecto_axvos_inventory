@extends('adminlte::page')

@section('title', $acta->acta_number)

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('documents.hub') }}">Documentación</a></li>
            <li class="breadcrumb-item"><a href="{{ route('actas.index') }}">Actas</a></li>
            <li class="breadcrumb-item active">{{ $acta->acta_number }}</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">
        <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
@endif

@php($showTechColumns = $acta->hasTechAssets())

<div class="row">
    {{-- ── Columna principal ─────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- Cabecera del acta --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2 d-flex align-items-center justify-content-between"
                 style="border-left:4px solid #0f766e;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-file-signature mr-1" style="color:#0f766e;"></i>
                    {{ $acta->acta_number }}
                    <span class="badge badge-{{ $acta->status_color }} ml-1" style="font-size:.72rem;">
                        {{ $acta->status_label }}
                    </span>
                </h6>
                <div class="d-flex">
                    <a href="{{ route('actas.preview', $acta) }}" target="_blank"
                       class="btn btn-sm btn-outline-secondary mr-1" title="Vista previa PDF">
                        <i class="fas fa-eye mr-1"></i> Vista previa
                    </a>
                    <a href="{{ route('actas.pdf', $acta) }}"
                       class="btn btn-sm btn-outline-secondary mr-1" title="Descargar PDF">
                        <i class="fas fa-file-pdf mr-1"></i> PDF
                    </a>
                    @if($acta->status !== \App\Models\Acta::STATUS_COMPLETADA && $acta->status !== \App\Models\Acta::STATUS_ANULADA)
                        <form method="POST" action="{{ route('actas.void', $acta) }}"
                              onsubmit="return confirm('¿Anular este acta?')">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm btn-outline-danger" title="Anular acta">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Tipo de acta</small>
                        <strong>{{ ucfirst($acta->acta_type) }} / {{ $acta->asset_category_label }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Colaborador</small>
                        <strong>{{ $acta->assignment->collaborator->full_name ?? '—' }}</strong>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Fecha generación</small>
                        <strong>{{ $acta->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
                @if($acta->notes)
                    <div class="mt-2 p-2 rounded" style="background:#f8fafc;border-left:3px solid #cbd5e1;">
                        <small class="text-muted">Notas:</small> {{ $acta->notes }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Activos en el acta --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-laptop mr-1 text-secondary"></i>
                    Activos incluidos
                    <span class="badge badge-light ml-1">{{ $actaAssets->count() }}</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            @if($showTechColumns)
                                <th>Etiqueta Inventario</th>
                                <th>Activo Fijo</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actaAssets as $i => $aa)
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td><code>{{ $aa->asset->internal_code ?? '—' }}</code></td>
                            <td>{{ $aa->asset->type?->name ?? '—' }}</td>
                            <td>{{ trim(($aa->asset->brand ?? '') . ' ' . ($aa->asset->model ?? '')) ?: '—' }}</td>
                            <td class="text-muted small">{{ $aa->asset->serial ?? '—' }}</td>
                            @if($showTechColumns)
                                <td class="text-muted small">{{ $aa->asset->asset_tag ?? '—' }}</td>
                                <td class="text-muted small">{{ $aa->asset->fixed_asset_code ?? '—' }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $showTechColumns ? 7 : 5 }}" class="text-center text-muted py-3">Sin activos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3" style="border-top:3px solid #2563eb;">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-edit mr-1 text-primary"></i> Edición web del acta
                </h6>
            </div>
            <div class="card-body">
                @if(!$template)
                    <div class="alert alert-warning mb-0">
                        No hay una plantilla activa para esta categoría. Activa una plantilla antes de editar o generar el Excel.
                    </div>
                @elseif(in_array($acta->status, [\App\Models\Acta::STATUS_COMPLETADA, \App\Models\Acta::STATUS_ANULADA]))
                    <div class="alert alert-light border mb-0">
                        Esta acta está cerrada. La edición web quedó bloqueada para proteger el Excel y el PDF finales.
                    </div>
                @elseif($editableFields->isEmpty())
                    <div class="alert alert-light border mb-0">
                        La plantilla activa no tiene campos editables de cabecera. Puedes configurar campos no iterables en la plantilla para habilitar esta edición web.
                    </div>
                @else
                    <form method="POST" action="{{ route('actas.fields.update', $acta) }}">
                        @csrf
                        <div class="row">
                            @foreach($editableFields as $field)
                                <div class="col-md-{{ $field['input_type'] === 'textarea' ? '12' : '6' }}">
                                    <div class="form-group">
                                        <label class="font-weight-bold">{{ $field['label'] }}</label>
                                        @if($field['input_type'] === 'textarea')
                                            <textarea name="fields[{{ $field['key'] }}]" rows="3" class="form-control">{{ old('fields.'.$field['key'], $field['value']) }}</textarea>
                                        @else
                                            <input
                                                type="{{ $field['input_type'] }}"
                                                name="fields[{{ $field['key'] }}]"
                                                class="form-control"
                                                value="{{ old('fields.'.$field['key'], $field['value']) }}">
                                        @endif
                                        <small class="text-muted d-block mt-1"><code>{{ $field['key'] }}</code></small>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="alert alert-light border mb-3">
                            <strong>Importante:</strong> los datos por activo se generan automáticamente desde la asignación. En actas TI ya se incluyen “Etiqueta Inventario” y “Activo Fijo” en la vista y en la exportación.
                        </div>

                        <button class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar edición web
                        </button>
                    </form>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Columna lateral: firmas ──────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Excel (plantilla configurable) --}}
        <div class="card shadow-sm mb-3" style="border-top:3px solid #16a34a;">
            <div class="card-body">
                <p class="font-weight-bold mb-2" style="font-size:.85rem;">
                    <i class="fas fa-file-excel mr-1" style="color:#16a34a;"></i> Excel del Acta
                </p>

                <form method="POST" action="{{ route('actas.excel.draft.generate', $acta) }}" class="mb-2">
                    @csrf
                    <button class="btn btn-sm btn-success btn-block" {{ in_array($acta->status, [\App\Models\Acta::STATUS_COMPLETADA, \App\Models\Acta::STATUS_ANULADA]) ? 'disabled' : '' }}>
                        <i class="fas fa-magic mr-1"></i> Generar Excel desde edición web
                    </button>
                </form>

                @if($acta->xlsx_draft_path)
                    <a href="{{ route('actas.excel.draft.download', $acta) }}" class="btn btn-sm btn-outline-success btn-block mb-2">
                        <i class="fas fa-download mr-1"></i> Descargar borrador
                    </a>
                @endif

                <form method="POST" action="{{ route('actas.excel.final.upload', $acta) }}" enctype="multipart/form-data">
                    @csrf
                    <small class="text-muted d-block mb-2">Subida manual opcional, solo como respaldo.</small>
                    <div class="custom-file mb-2">
                        <input type="file" class="custom-file-input" id="excel_final" name="excel_final" accept=".xlsx" required {{ in_array($acta->status, [\App\Models\Acta::STATUS_COMPLETADA, \App\Models\Acta::STATUS_ANULADA]) ? 'disabled' : '' }}>
                        <label class="custom-file-label" for="excel_final">Subir Excel final...</label>
                    </div>
                    <button class="btn btn-sm btn-outline-primary btn-block" {{ in_array($acta->status, [\App\Models\Acta::STATUS_COMPLETADA, \App\Models\Acta::STATUS_ANULADA]) ? 'disabled' : '' }}>
                        <i class="fas fa-upload mr-1"></i> Guardar Excel final
                    </button>
                </form>

                @if($acta->xlsx_final_path)
                    <a href="{{ route('actas.excel.final.download', $acta) }}" class="btn btn-sm btn-outline-primary btn-block mt-2">
                        <i class="fas fa-download mr-1"></i> Descargar Excel final
                    </a>
                @endif

                <hr class="my-2">
                <form method="POST" action="{{ route('actas.pdf.final.generate', $acta) }}">
                    @csrf
                    <button class="btn btn-sm btn-danger btn-block">
                        <i class="fas fa-file-pdf mr-1"></i> Generar PDF final (desde Excel)
                    </button>
                </form>
            </div>
        </div>

        {{-- Acciones --}}
        @if(in_array($acta->status, [\App\Models\Acta::STATUS_BORRADOR, \App\Models\Acta::STATUS_FIRMADA_COLABORADOR, \App\Models\Acta::STATUS_FIRMADA_RESPONSABLE]))
        <div class="card shadow-sm mb-3" style="border-top:3px solid #1e3a8a;">
            <div class="card-body">
                <p class="font-weight-bold mb-2" style="font-size:.85rem;">
                    <i class="fas fa-paper-plane mr-1" style="color:#1e3a8a;"></i> Enviar para firma
                </p>
                <form method="POST" action="{{ route('actas.send', $acta) }}">
                    @csrf

                    {{-- Campo de email por cada firmante pendiente --}}
                    @foreach($acta->signatures as $sig)
                        @if(!$sig->isSigned())
                        <div class="mb-2">
                            <label class="text-muted mb-1 d-block" style="font-size:.75rem;">
                                <i class="fas fa-user mr-1"></i>
                                {{ $sig->role_label }}
                                @if($sig->signer_name)
                                    — <strong>{{ $sig->signer_name }}</strong>
                                @endif
                            </label>
                            <input type="email"
                                   name="emails[{{ $sig->id }}]"
                                   class="form-control form-control-sm"
                                   value="{{ old('emails.'.$sig->id, $sig->signer_email) }}"
                                   placeholder="correo@ejemplo.com"
                                   required>
                        </div>
                        @endif
                    @endforeach

                    <div class="mb-2">
                        <label class="text-muted mb-1 d-block" style="font-size:.75rem;">
                            <i class="fas fa-user-plus mr-1"></i>
                            Tercer correo (opcional)
                        </label>
                        <input type="email"
                               name="third_email"
                               class="form-control form-control-sm"
                               value="{{ old('third_email') }}"
                               placeholder="tercero@ejemplo.com">
                    </div>

                    <button class="btn btn-primary btn-sm btn-block mt-2">
                        <i class="fas fa-paper-plane mr-1"></i> Enviar acta (PDF adjunto)
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Firma interna del gestor --}}
        @if($mySignature && !$mySignature->isSigned())
        <div class="card shadow-sm mb-3" style="border-top:3px solid #0f766e;">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold" style="font-size:.85rem;">
                    <i class="fas fa-pen-nib mr-1" style="color:#0f766e;"></i>
                    Su firma ({{ $mySignature->role_label }})
                </h6>
            </div>
            <div class="card-body">
                <div id="internalSignPad" class="mb-2" style="border:1px solid #dee2e6;border-radius:6px;background:#fafafa;">
                    <canvas id="internalCanvas" width="340" height="120" style="width:100%;touch-action:none;"></canvas>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <button type="button" id="clearInternalSign" class="btn btn-xs btn-outline-secondary">
                        <i class="fas fa-eraser mr-1"></i> Limpiar
                    </button>
                    <span class="text-muted small">Firme con el mouse o con el dedo</span>
                </div>
                <form method="POST" action="{{ route('actas.sign.internal', $acta) }}" id="internalSignForm">
                    @csrf
                    <input type="hidden" name="signature_type" value="drawn">
                    <input type="hidden" name="signature_data" id="internalSignData">
                    <button type="submit" class="btn btn-sm btn-success btn-block">
                        <i class="fas fa-signature mr-1"></i> Guardar mi firma
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Estado de firmas --}}
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold" style="font-size:.85rem;">
                    <i class="fas fa-users mr-1 text-secondary"></i>
                    Estado de firmas
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($acta->signatures as $sig)
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="d-block font-weight-bold" style="font-size:.85rem;">
                                    {{ $sig->signer_name }}
                                </span>
                                <span class="text-muted small">{{ $sig->role_label }}</span>
                                @if($sig->signer_email)
                                    <span class="d-block text-muted" style="font-size:.75rem;">{{ $sig->signer_email }}</span>
                                @endif
                            </div>
                            <div class="text-right ml-2">
                                @if($sig->isSigned())
                                    <span class="badge badge-success badge-pill">Firmado</span>
                                    <small class="d-block text-muted" style="font-size:.7rem;">{{ $sig->signed_at->format('d/m/Y H:i') }}</small>
                                    {{-- Mostrar firma --}}
                                    @if($sig->signature_data)
                                        <img src="{{ $sig->signature_data }}"
                                             style="max-width:80px;max-height:40px;border:1px solid #dee2e6;border-radius:4px;margin-top:4px;"
                                             alt="Firma">
                                    @endif
                                @else
                                    <span class="badge badge-secondary badge-pill">Pendiente</span>
                                    @if($sig->token && $sig->isTokenValid())
                                        <a href="{{ url('/sign/' . $sig->token) }}"
                                           class="d-block text-muted mt-1" style="font-size:.72rem;" target="_blank">
                                            <i class="fas fa-link mr-1"></i> Enlace de firma
                                        </a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>
</div>

@stop

@section('css')
<style>
.card { border-radius: 10px; }
.table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function() {
    const canvas = document.getElementById('internalCanvas');
    if (!canvas) return;

    const pad = new SignaturePad(canvas, { penColor: '#1e293b' });

    // Ajustar resolución en HiDPI
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width  = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    document.getElementById('clearInternalSign').addEventListener('click', () => pad.clear());

    document.getElementById('internalSignForm').addEventListener('submit', function (e) {
        if (pad.isEmpty()) {
            e.preventDefault();
            alert('Por favor dibuje su firma antes de guardar.');
            return;
        }
        document.getElementById('internalSignData').value = pad.toDataURL('image/png');
    });
})();
</script>
@stop
