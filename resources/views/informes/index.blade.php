@extends('layouts.app')

@section('title', 'Informes')

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="mb-1">Informe operativo</h1>
            <p>
                Periodo de {{ $dias }} días
                ({{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}).
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <form method="get" action="{{ route('informes.index') }}" class="d-flex gap-2 align-items-center">
                <label class="small text-muted mb-0" for="dias">Periodo</label>
                <select name="dias" id="dias" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="7" @selected($dias === 7)>7 días</option>
                    <option value="30" @selected($dias === 30)>30 días</option>
                    <option value="90" @selected($dias === 90)>90 días</option>
                </select>
            </form>
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-noche"><i class="bi bi-arrow-left-right"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['envios'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Envíos del periodo</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-verde"><i class="bi bi-graph-up"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['exito_global'], 1, ',', '.') }}%</div>
                    <div class="kpi-label">Éxito global</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-azul"><i class="bi bi-database"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['registros_procesados'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Registros procesados</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-rojo"><i class="bi bi-x-octagon"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['registros_error'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Registros con error</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-azul"><i class="bi bi-card-list"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['patentes'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Patentes registradas en el periodo</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-amarillo"><i class="bi bi-exclamation-octagon"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['incidentes_abiertos'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Incidentes abiertos del periodo</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-verde"><i class="bi bi-check2-circle"></i></div>
                    <div class="kpi-value">{{ number_format($tarjetas['incidentes_resueltos'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Incidentes resueltos del periodo</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ic mb-4">
        <div class="card-body">
            <h2 class="section-title mb-3">Envíos por día</h2>
            <div class="chart-informe">
                <canvas id="grafico-informe" data-chart='@json($grafico)'></canvas>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Integraciones con más errores</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Integración</th>
                                    <th>Errores</th>
                                    <th>Tasa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($masErrores as $fila)
                                    <tr>
                                        <td>
                                            <a href="{{ route('integraciones.show', $fila['integracion']) }}">
                                                {{ $fila['integracion']->nombre }}
                                            </a>
                                        </td>
                                        <td>{{ number_format($fila['total_errores'], 0, ',', '.') }}</td>
                                        <td>{{ number_format($fila['tasa_error'], 1, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Integraciones más confiables</h2>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Integración</th>
                                    <th>Disponibilidad</th>
                                    <th>Envíos</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($masConfiables as $fila)
                                    <tr>
                                        <td>
                                            <a href="{{ route('integraciones.show', $fila['integracion']) }}">
                                                {{ $fila['integracion']->nombre }}
                                            </a>
                                        </td>
                                        <td>{{ number_format($fila['disponibilidad'], 1, ',', '.') }}%</td>
                                        <td>{{ number_format($fila['envios_finalizados'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = 'Inter, sans-serif';

        const canvas = document.getElementById('grafico-informe');
        if (canvas) {
            const data = JSON.parse(canvas.getAttribute('data-chart'));

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Exitosos', data: data.exitosos, backgroundColor: '#16a34a', borderRadius: 4, stack: 'envios' },
                        { label: 'Parciales y fallidos', data: data.problemas, backgroundColor: '#dc2626', borderRadius: 4, stack: 'envios' },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b', maxRotation: 0 } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 11 }, color: '#64748b' }, grid: { color: 'rgba(37,99,235,.08)' } },
                    },
                },
            });
        }
    </script>
@endpush
