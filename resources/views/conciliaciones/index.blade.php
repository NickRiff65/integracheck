@extends('layouts.app')

@section('title', 'Conciliaciones')

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="mb-1">Conciliaciones</h1>
            <p>Cruce entre lo que venía en la fuente y lo que quedó registrado en el sistema destino.</p>
        </div>
        <a href="{{ route('conciliaciones.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Registrar conciliación
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-noche"><i class="bi bi-clipboard-check"></i></div>
                    <div class="kpi-value">{{ number_format($resumen['total'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Conciliaciones</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-verde"><i class="bi bi-check2-circle"></i></div>
                    <div class="kpi-value">{{ number_format($resumen['porcentaje_cuadran'], 1, ',', '.') }}%</div>
                    <div class="kpi-label">Que cuadran</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-azul"><i class="bi bi-clock-history"></i></div>
                    @if ($resumen['ultima'])
                        <div class="kpi-value" style="font-size: 1.25rem;">
                            {{ $resumen['ultima']->fecha_conciliacion->format('d/m/Y H:i') }}
                        </div>
                        <div class="kpi-label">
                            Última:
                            @include('partials.badge-conciliacion', ['conciliacion' => $resumen['ultima']])
                            <span class="d-block mt-1">{{ $resumen['ultima']->integracion->nombre }}</span>
                        </div>
                    @else
                        <div class="kpi-value">—</div>
                        <div class="kpi-label">Sin conciliaciones</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ic">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Integración</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Esperados</th>
                            <th>Registrados</th>
                            <th>Faltantes</th>
                            <th>Duplicados</th>
                            <th>Diferencia</th>
                            <th>Archivo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($conciliaciones as $conciliacion)
                            <tr>
                                <td>
                                    <a href="{{ route('integraciones.show', $conciliacion->integracion) }}">
                                        {{ $conciliacion->integracion->nombre }}
                                    </a>
                                </td>
                                <td>
                                    @include('partials.badge-conciliacion', ['conciliacion' => $conciliacion])
                                </td>
                                <td>
                                    {{ $conciliacion->fecha_conciliacion->format('d/m/Y H:i') }}
                                    <div class="small text-muted">{{ $conciliacion->fecha_conciliacion->locale('es')->diffForHumans() }}</div>
                                </td>
                                <td>{{ number_format($conciliacion->registros_esperados, 0, ',', '.') }}</td>
                                <td>{{ number_format($conciliacion->registros_registrados, 0, ',', '.') }}</td>
                                <td>{{ number_format($conciliacion->registros_faltantes, 0, ',', '.') }}</td>
                                <td>{{ number_format($conciliacion->registros_duplicados, 0, ',', '.') }}</td>
                                <td class="fw-semibold">{{ number_format($conciliacion->diferencia(), 0, ',', '.') }}</td>
                                <td class="small">{{ $conciliacion->archivo_origen ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('conciliaciones.show', $conciliacion) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-muted text-center py-4">Aún no hay conciliaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $conciliaciones->links() }}</div>
        </div>
    </div>
@endsection
