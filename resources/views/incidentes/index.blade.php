@extends('layouts.app')

@section('title', 'Incidentes')

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="mb-1">Incidentes</h1>
            <p>Seguimiento de alertas amarillas y rojas. La resolución es manual: no se cierran al volver a verde.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-rojo"><i class="bi bi-exclamation-octagon"></i></div>
                    <div class="kpi-value">{{ $conteos['abierto'] }}</div>
                    <div class="kpi-label">Abiertos</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-amarillo"><i class="bi bi-hourglass-split"></i></div>
                    <div class="kpi-value">{{ $conteos['en_revision'] }}</div>
                    <div class="kpi-label">En revisión</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-verde"><i class="bi bi-check2-circle"></i></div>
                    <div class="kpi-value">{{ $conteos['resuelto'] }}</div>
                    <div class="kpi-label">Resueltos</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ic mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="{{ route('incidentes.index') }}">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estados as $estado)
                            <option value="{{ $estado->value }}" @selected($filtros['estado'] === $estado->value)>
                                {{ $estado->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Gravedad</label>
                    <select name="gravedad" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($gravedades as $gravedad)
                            <option value="{{ $gravedad->value }}" @selected($filtros['gravedad'] === $gravedad->value)>
                                {{ $gravedad->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Integración</label>
                    <select name="integracion_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($integraciones as $integracion)
                            <option value="{{ $integracion->id }}" @selected($filtros['integracion_id'] === $integracion->id)>
                                {{ $integracion->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('incidentes.index') }}">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-ic">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Apertura</th>
                            <th>Integración</th>
                            <th>Título</th>
                            <th>Gravedad</th>
                            <th>Estado</th>
                            <th>Responsable</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($incidentes as $incidente)
                            <tr>
                                <td>
                                    {{ $incidente->fecha_apertura->format('d/m/Y H:i') }}
                                    <div class="small text-muted">{{ $incidente->fecha_apertura->locale('es')->diffForHumans() }}</div>
                                </td>
                                <td>
                                    <a href="{{ route('integraciones.show', $incidente->integracion) }}">
                                        {{ $incidente->integracion->nombre }}
                                    </a>
                                </td>
                                <td>{{ $incidente->titulo }}</td>
                                <td>
                                    <span class="badge-estado {{ $incidente->gravedad->value }}">{{ $incidente->gravedad->etiqueta() }}</span>
                                </td>
                                <td>
                                    <span class="badge-estado {{ $incidente->estado->value }}">{{ $incidente->estado->etiqueta() }}</span>
                                </td>
                                <td>{{ $incidente->responsable ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('incidentes.show', $incidente) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted text-center py-4">No hay incidentes con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $incidentes->links() }}
            </div>
        </div>
    </div>
@endsection
