<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $acta->acta_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            background: #fff;
        }
        .page { padding: 28px 36px; }

        /* ── Encabezado ── */
        .header { border-bottom: 3px solid #1e3a8a; padding-bottom: 14px; margin-bottom: 18px; }
        .header-inner { display: table; width: 100%; }
        .header-logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header-title { display: table-cell; vertical-align: middle; padding-left: 14px; }
        .header-title h1 { font-size: 16pt; color: #1e3a8a; margin: 0; }
        .header-title p { font-size: 9pt; color: #475569; margin: 2px 0 0; }
        .header-meta { display: table-cell; vertical-align: middle; text-align: right; }
        .header-meta .acta-num { font-size: 13pt; font-weight: bold; color: #1e3a8a; }
        .header-meta .acta-date { font-size: 8pt; color: #6b7280; }
        .badge-status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: bold;
            margin-top: 4px;
        }

        /* ── Secciones ── */
        .section { margin-bottom: 16px; }
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        /* ── Info table ── */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; font-size: 9pt; vertical-align: top; }
        .info-table td:first-child { color: #6b7280; width: 35%; }
        .info-table td:last-child { font-weight: 600; }

        /* ── Activos table ── */
        .assets-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .assets-table th {
            background: #1e3a8a;
            color: #fff;
            font-size: 8pt;
            font-weight: bold;
            padding: 5px 8px;
            text-align: left;
        }
        .assets-table td { padding: 5px 8px; font-size: 8.5pt; border-bottom: 1px solid #f1f5f9; }
        .assets-table tr:nth-child(even) td { background: #f8fafc; }

        /* ── Firmas ── */
        .signatures-section { margin-top: 22px; }
        .sig-table { width: 100%; border-collapse: collapse; }
        .sig-box {
            display: inline-block;
            width: 46%;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 14px;
            margin: 0 1%;
            vertical-align: top;
        }
        .sig-name { font-weight: bold; font-size: 9pt; margin-bottom: 2px; }
        .sig-role { font-size: 8pt; color: #6b7280; margin-bottom: 8px; }
        .sig-img-area {
            height: 60px;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            background: #fafafa;
            text-align: center;
            line-height: 60px;
            color: #9ca3af;
            font-size: 8pt;
        }
        .sig-img-area img { max-height: 56px; max-width: 180px; vertical-align: middle; }
        .sig-date { font-size: 7.5pt; color: #6b7280; margin-top: 6px; }
        .pending-text { color: #9ca3af; font-style: italic; font-size: 8.5pt; line-height: 60px; }

        /* ── Footer ── */
        .footer { margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 7.5pt; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Encabezado ── --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-title">
                <h1>Acta de {{ ucfirst($acta->acta_type) }}</h1>
                <p>Inventario de Activos &mdash; Sistema de Gestión</p>
            </div>
            <div class="header-meta">
                <div class="acta-num">{{ $acta->acta_number }}</div>
                <div class="acta-date">{{ $acta->created_at->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    {{-- ── Información del acta ── --}}
    <div class="section">
        <div class="section-title">Datos del Acta</div>
        <table class="info-table">
            <tr>
                <td>Número de Acta</td>
                <td>{{ $acta->acta_number }}</td>
                <td>Tipo</td>
                <td>{{ ucfirst($acta->acta_type) }}</td>
            </tr>
            <tr>
                <td>Fecha de Generación</td>
                <td>{{ $acta->created_at->format('d \d\e F \d\e Y') }}</td>
                <td>Generada por</td>
                <td>{{ $acta->generatedBy->name ?? '—' }}</td>
            </tr>
            @if($acta->sent_at)
            <tr>
                <td>Enviada para firma</td>
                <td>{{ $acta->sent_at->format('d/m/Y H:i') }}</td>
                <td>Estado</td>
                <td>{{ $acta->status_label }}</td>
            </tr>
            @endif
        </table>
    </div>

    {{-- ── Datos del colaborador ── --}}
    @php $coll = $acta->assignment->collaborator; @endphp
    <div class="section">
        <div class="section-title">Datos del Receptor</div>
        <table class="info-table">
            <tr>
                <td>Nombre completo</td>
                <td>{{ $coll->full_name ?? '—' }}</td>
                <td>Documento</td>
                <td>{{ $coll->document ?? '—' }}</td>
            </tr>
            <tr>
                <td>Cargo / Posición</td>
                <td>{{ $coll->position ?? '—' }}</td>
                <td>Área</td>
                <td>{{ $coll->area ?? '—' }}</td>
            </tr>
            <tr>
                <td>Correo electrónico</td>
                <td colspan="3">{{ $coll->email ?? '—' }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Activos (filtrados por categoría del acta) ── --}}
    @php
        $category  = strtoupper($acta->asset_category ?? 'TI');
        $catLabel  = match($category) {
            'OTRO' => 'Otros Activos',
            'ALL'  => 'Activos Mixtos',
            default => 'Activos TI',
        };
        $assets    = $acta->scopedAssignmentAssets();
        $showTechColumns = $assets->contains(fn($aa) => strtoupper($aa->asset?->type?->category ?? '') === 'TI');
    @endphp
    <div class="section">
        <div class="section-title">{{ $catLabel }} Entregados</div>
        <table class="assets-table">
            <thead>
                <tr>
                    <th style="width:30px;">#</th>
                    <th>Código</th>
                    <th>Tipo de Activo</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Serial</th>
                    @if($showTechColumns)
                        <th>Etiqueta Inventario</th>
                        <th>Activo Fijo</th>
                    @endif
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $i => $aa)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $aa->asset->internal_code ?? $aa->asset->asset_code ?? '—' }}</td>
                    <td>{{ $aa->asset->type->name ?? '—' }}</td>
                    <td>{{ $aa->asset->brand ?? '—' }}</td>
                    <td>{{ $aa->asset->model ?? '—' }}</td>
                    <td>{{ $aa->asset->serial ?? '—' }}</td>
                    @if($showTechColumns)
                        <td>{{ $aa->asset->asset_tag ?? '—' }}</td>
                        <td>{{ $aa->asset->fixed_asset_code ?? '—' }}</td>
                    @endif
                    <td></td>
                </tr>
                @empty
                <tr><td colspan="{{ $showTechColumns ? 9 : 7 }}" style="text-align:center;color:#9ca3af;">Sin activos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Declaración ── --}}
    <div class="section" style="background:#f8fafc;border-left:3px solid #1e3a8a;padding:10px 14px;border-radius:0 4px 4px 0;">
        <p style="font-size:8.5pt;color:#374151;line-height:1.6;">
            El receptor declara haber recibido en perfectas condiciones los activos relacionados anteriormente,
            comprometiéndose a hacer uso responsable de los mismos, a reportar cualquier daño, pérdida o robo,
            y a devolverlos en las mismas condiciones al término del período de asignación.
        </p>
    </div>

    {{-- ── Firmas ── --}}
    <div class="signatures-section">
        <div class="section-title">Firmas</div>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                @foreach($acta->signatures as $sig)
                <td style="width:{{ 96 / $acta->signatures->count() }}%;padding:0 2%;vertical-align:top;">
                    <div style="border:1px solid #cbd5e1;border-radius:6px;padding:12px;">
                        <div style="font-weight:bold;font-size:9pt;">{{ $sig->signer_name }}</div>
                        <div style="font-size:8pt;color:#6b7280;margin-bottom:8px;">{{ $sig->role_label }}</div>
                        <div style="height:60px;border:1px dashed #cbd5e1;border-radius:4px;background:#fafafa;text-align:center;">
                            @if($sig->isSigned() && $sig->signature_data)
                                <img src="{{ $sig->signature_data }}" style="max-height:56px;max-width:100%;vertical-align:middle;">
                            @else
                                <span style="color:#9ca3af;font-size:8pt;line-height:60px;">Pendiente de firma</span>
                            @endif
                        </div>
                        @if($sig->isSigned())
                            <div style="font-size:7.5pt;color:#6b7280;margin-top:5px;">
                                Firmado: {{ $sig->signed_at->format('d/m/Y H:i') }}
                                @if($sig->signed_ip)
                                    &middot; IP: {{ $sig->signed_ip }}
                                @endif
                            </div>
                        @endif
                    </div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- ── Footer ── --}}
    <div class="footer">
        Documento generado por el Sistema de Inventario &mdash; {{ $acta->acta_number }} &mdash; {{ now()->format('d/m/Y H:i') }}
    </div>

</div>
</body>
</html>
