@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @if ($alertas->isEmpty())
        <div class="banner-ic ok mb-4">
            <i class="bi bi-check-circle me-1"></i> Todas las integraciones están operativas
            @if (($incidentesAbiertos ?? 0) > 0)
                <div class="mt-2">
                    <a href="{{ route('incidentes.index', ['estado' => 'abierto']) }}">
                        Incidentes abiertos: {{ $incidentesAbiertos }}
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="banner-ic alerta {{ $alertas->contains(fn ($tarjeta) => $tarjeta['salud']['estado'] === 'rojo') ? 'critica' : '' }} mb-4">
            <div class="fw-bold mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i> Hay integraciones con problemas
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($alertas as $alerta)
                    <li class="mb-1">
                        <a href="{{ route('integraciones.show', $alerta['integracion']) }}">{{ $alerta['integracion']->nombre }}</a>
                        — {{ $alerta['salud']['motivo'] }}
                    </li>
                @endforeach
            </ul>
            <div class="mt-2">
                <a href="{{ route('incidentes.index', ['estado' => 'abierto']) }}">
                    Incidentes abiertos: {{ $incidentesAbiertos ?? 0 }}
                </a>
            </div>
        </div>
    @endif

    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="mb-1">Panel de integraciones</h1>
            <p>Salud operativa de los envíos de patentes vehiculares en Chile, en los últimos minutos y días.</p>
            <p class="small mt-2 mb-0" id="auto-refresh-info">
                Última actualización: <span id="hora-actualizacion"></span>
                · Se actualiza en <span id="cuenta-regresiva">60</span> s
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="POST" action="{{ route('simular') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-play-circle me-1"></i> Simular envío ahora
                </button>
            </form>
            <a href="{{ route('envios.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list-ul me-1"></i> Ver todos los envíos
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-noche"><i class="bi bi-diagram-3"></i></div>
                    <div class="kpi-value">{{ $resumen['total_integraciones'] }}</div>
                    <div class="kpi-label">Integraciones</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-verde"><i class="bi bi-check2-circle"></i></div>
                    <div class="kpi-value">{{ $resumen['saludables'] }}</div>
                    <div class="kpi-label">Saludables</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-rojo"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="kpi-value">{{ $resumen['con_problemas'] }}</div>
                    <div class="kpi-label">Con problemas</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-icon kpi-azul"><i class="bi bi-card-list"></i></div>
                    <div class="kpi-value">{{ number_format($resumen['patentes_registradas'], 0, ',', '.') }}</div>
                    <div class="kpi-label">Patentes registradas</div>
                </div>
            </div>
        </div>
    </div>

    <h2 class="section-title mb-3">Integraciones</h2>

    <div class="row g-3">
        @foreach ($tarjetas as $tarjeta)
            @php
                $integracion = $tarjeta['integracion'];
                $salud = $tarjeta['salud'];
                $ultimo = $integracion->ultimoEnvio;
                $disponibilidad = $tarjeta['disponibilidad'];
                $claseDisp = $disponibilidad >= 99 ? 'disp-verde' : ($disponibilidad >= 95 ? 'disp-amarillo' : 'disp-rojo');
            @endphp
            <div class="col-12 col-lg-4">
                <article class="card card-integracion salud-{{ $salud['estado'] }}">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <h3 class="h6 mb-0 fw-bold">{{ $integracion->nombre }}</h3>
                            @include('partials.badge-salud', ['estado' => $salud['estado']])
                        </div>
                        <p class="flujo mb-2">
                            {{ $integracion->sistema_origen }}
                            <i class="bi bi-arrow-right mx-1"></i>
                            {{ $integracion->sistema_destino }}
                        </p>
                        <p class="small mb-3">
                            <span class="text-muted">{{ $salud['motivo'] }}</span>
                        </p>

                        <div class="small mb-3">
                            @if ($ultimo)
                                <div class="fw-semibold">Último envío</div>
                                <div>
                                    {{ $ultimo->fecha_inicio->format('d/m/Y H:i') }}
                                    <span class="text-muted">· {{ $ultimo->fecha_inicio->locale('es')->diffForHumans() }}</span>
                                </div>
                                <div class="text-muted">
                                    {{ $ultimo->estado->etiqueta() }}
                                    · {{ $integracion->frecuenciaHumana() }}
                                </div>
                                <div class="fw-semibold mt-2 {{ $claseDisp }}">
                                    Disponibilidad 30 días: {{ number_format($disponibilidad, 1, ',', '.') }}%
                                </div>
                            @else
                                <span class="text-muted">Sin envíos registrados</span>
                                <div class="fw-semibold mt-2 {{ $claseDisp }}">
                                    Disponibilidad 30 días: {{ number_format($disponibilidad, 1, ',', '.') }}%
                                </div>
                            @endif
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="stat-mini">
                                    <div class="etiqueta">Totales</div>
                                    <div class="valor">{{ $tarjeta['patentes']['totales'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini">
                                    <div class="etiqueta">Enviadas</div>
                                    <div class="valor verde">{{ $tarjeta['patentes']['enviadas'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini">
                                    <div class="etiqueta">Con error</div>
                                    <div class="valor rojo">{{ $tarjeta['patentes']['con_error'] }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini">
                                    <div class="etiqueta">Pendientes</div>
                                    <div class="valor amarillo">{{ $tarjeta['patentes']['pendientes'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="chart-mini mb-3">
                            <canvas
                                class="js-envios-chart"
                                data-chart='@json($tarjeta['grafico'])'
                            ></canvas>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-auto">
                            <a href="{{ route('integraciones.show', $integracion) }}" class="btn btn-sm btn-outline-primary">
                                Ver detalle
                            </a>
                            @include('partials.form-verificar', ['integracion' => $integracion])
                        </div>
                    </div>
                </article>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = 'Inter, sans-serif';

        document.querySelectorAll('.js-envios-chart').forEach((canvas) => {
            const data = JSON.parse(canvas.getAttribute('data-chart'));

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Exitosos', data: data.exitosos, backgroundColor: '#16a34a', borderRadius: 4, stack: 'envios' },
                        { label: 'Parciales', data: data.parciales, backgroundColor: '#f59e0b', borderRadius: 4, stack: 'envios' },
                        { label: 'Fallidos', data: data.fallidos, backgroundColor: '#dc2626', borderRadius: 4, stack: 'envios' },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => items.length ? `Envíos · ${items[0].label}` : '',
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 }, color: '#64748b' } },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0, font: { size: 11 }, color: '#64748b' }, grid: { color: 'rgba(37,99,235,.08)' } },
                    },
                },
            });
        });

        const horaEl = document.getElementById('hora-actualizacion');
        const cuentaEl = document.getElementById('cuenta-regresiva');
        let restante = 60;

        const formatearHora = () => new Date().toLocaleTimeString('es-CL', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });

        if (horaEl) {
            horaEl.textContent = formatearHora();
        }

        setInterval(() => {
            restante -= 1;
            if (cuentaEl) {
                cuentaEl.textContent = String(restante);
            }
            if (restante <= 0) {
                window.location.reload();
            }
        }, 1000);
    </script>
@endpush
