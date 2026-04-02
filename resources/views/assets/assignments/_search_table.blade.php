{{--
    Partial: tabla de resultados de búsqueda de destinatarios (Otros Activos).

    Variables esperadas:
      $rows       → Collection de arrays con: id, name, sub, branch, modality, destination_type,
                     assets_count, latest (Assignment|null), route, create_route
      $title      → string  e.g. "Colaboradores"
      $icon       → string  e.g. "fa-user"
      $color      → hex     e.g. "#1d4ed8"
      $badgeClass → string  e.g. "badge-primary"
--}}

<div class="card shadow-sm mb-3">
    <div class="card-header py-2 d-flex align-items-center justify-content-between"
         style="border-left:4px solid {{ $color }};">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas {{ $icon }} mr-1" style="color:{{ $color }};"></i>
            {{ $title }}
            <span class="{{ $badgeClass }} badge ml-1" style="font-size:.72rem;">
                {{ $rows->count() }}
            </span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light"
                       style="font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                    <tr>
                        <th class="pl-3">Destinatario</th>
                        <th>Sucursal</th>
                        @if($rows->whereNotNull('modality')->isNotEmpty())
                            <th>Modalidad</th>
                        @endif
                        <th class="text-center">Activos</th>
                        <th>Última asignación</th>
                        <th class="text-center" style="width:110px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $row)
                    @php
                        $mod = $row['modality'] ?? null;
                        $modClass = match($mod) {
                            'remoto'  => 'badge-info',
                            'hibrido' => 'badge-warning text-dark',
                            default   => 'badge-success',
                        };
                        $modLabel = match($mod) {
                            'remoto'  => 'Remoto',
                            'hibrido' => 'Híbrido',
                            null      => null,
                            default   => 'Presencial',
                        };
                    @endphp
                    <tr>
                        {{-- Nombre --}}
                        <td class="pl-3 align-middle py-2">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center
                                            text-white mr-2 flex-shrink-0"
                                     style="width:32px;height:32px;font-size:.78rem;background:{{ $color }};">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-weight-bold" style="font-size:.88rem;">
                                        {{ $row['name'] }}
                                    </div>
                                    <small class="text-muted">{{ $row['sub'] }}</small>
                                </div>
                            </div>
                        </td>

                        {{-- Sucursal --}}
                        <td class="align-middle">
                            <small>{{ $row['branch'] }}</small>
                        </td>

                        {{-- Modalidad (solo si aplica) --}}
                        @if($rows->whereNotNull('modality')->isNotEmpty())
                            <td class="align-middle">
                                @if($modLabel)
                                    <span class="badge {{ $modClass }}" style="font-size:.7rem;">
                                        {{ $modLabel }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif

                        {{-- Activos --}}
                        <td class="align-middle text-center">
                            <span class="{{ $badgeClass }} badge" style="font-size:.78rem;">
                                {{ $row['assets_count'] }}
                            </span>
                        </td>

                        {{-- Última asignación --}}
                        <td class="align-middle">
                            @if($row['latest'])
                                <small>{{ $row['latest']->assignment_date?->format('d/m/Y') ?? '—' }}</small>
                                <br><small class="text-muted">#{{ $row['latest']->id }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="align-middle text-center" style="white-space:nowrap;">
                            <a href="{{ $row['route'] }}"
                               class="btn btn-sm btn-primary"
                               title="Ver activos del destinatario">
                                <i class="fas fa-eye mr-1"></i> Ver
                            </a>
                            @can('assets.assign')
                                <a href="{{ $row['create_route'] }}"
                                   class="btn btn-sm btn-outline-success ml-1"
                                   title="Nueva asignación">
                                    <i class="fas fa-plus"></i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
