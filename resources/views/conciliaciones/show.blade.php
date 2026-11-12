@extends('layouts.app')

@section('title', 'Conciliación')

@php
    $estado = $conciliacion->estado();
    $sumaCarga = $detallesCarga->sum('cantidad');
    $sumaTotal = $detallesTotal->sum('cantidad');
    $cargaCuadra = $sumaCarga === $conciliacion->registros_esperados;
    $totalCuadra = $conciliacion->total_activos === null || $sumaTotal === $conciliacion->total_activos;
@endphp

@section('content')
    <div class="page-header mb-3">
        <a href="{{ route('conciliaciones.index') }}" class="small text-decoration-none text-muted">
            <i class="bi bi-arrow-left"></i> Volver a conciliaciones
        </a>
    </div>

    <div class="card card-ic card-integracion salud-{{ $estado === 'cuadra' ? 'verde' : ($estado === 'diferencia_menor' ? 'amarillo' : 'rojo') }} mb-4">
        <div class="card-body py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="mb-2">
                        @include('partials.badge-conciliacion', ['conciliacion' => $conciliacion])
                    </div>
                    <h1 class="mb-2">{{ $conciliacion->etiquetaEstado() }}</h1>
                    <p class="mb-0" style="font-size: 1.05rem;">{{ $conciliacion->fraseResumen() }}</p>
                </div>
                <div class="text-md-end small text-muted">
                    <div>{{ $conciliacion->fecha_conciliacion->format('d/m/Y H:i') }}</div>
                    <div>{{ $conciliacion->integracion->nombre }}</div>
                    @if ($conciliacion->archivo_origen)
                        <div>{{ $conciliacion->archivo_origen }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-label">Esperados (fuente)</div>
                    <div class="cifra-cruce">{{ number_format($conciliacion->registros_esperados, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-label">Registrados (destino)</div>
                    <div class="cifra-cruce">{{ number_format($conciliacion->registros_registrados, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-kpi h-100">
                <div class="card-body">
                    <div class="kpi-label">Diferencia</div>
                    <div class="cifra-cruce {{ $estado }}">{{ number_format($conciliacion->diferencia(), 0, ',', '.') }}</div>
                    <div class="small text-muted mt-2">esperados − registrados</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-mini">
                <div class="etiqueta">Nuevos</div>
                <div class="valor">{{ number_format($conciliacion->registros_nuevos, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-mini">
                <div class="etiqueta">Existentes</div>
                <div class="valor">{{ number_format($conciliacion->registros_existentes, 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-mini">
                <div class="etiqueta">Faltantes</div>
                <div class="valor {{ $conciliacion->registros_faltantes > 0 ? 'rojo' : 'verde' }}">
                    {{ number_format($conciliacion->registros_faltantes, 0, ',', '.') }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-mini">
                <div class="etiqueta">Duplicados</div>
                <div class="valor {{ $conciliacion->registros_duplicados > 0 ? 'rojo' : 'verde' }}">
                    {{ number_format($conciliacion->registros_duplicados, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Desglose de la carga</h2>
                    <p class="small text-muted">Cuánto vino de cada origen en esta carga. La suma debe igualar los esperados.</p>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Origen</th>
                                    <th class="text-end">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($detallesCarga as $detalle)
                                    <tr>
                                        <td>{{ $detalle->origen }}</td>
                                        <td class="text-end">{{ number_format($detalle->cantidad, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Sin desglose de carga.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Suma</th>
                                    <th class="text-end">{{ number_format($sumaCarga, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="small mt-3 mb-0 {{ $cargaCuadra ? 'disp-verde' : 'disp-rojo' }}">
                        @if ($cargaCuadra)
                            La suma del desglose iguala los {{ number_format($conciliacion->registros_esperados, 0, ',', '.') }} esperados.
                        @else
                            La suma no iguala los {{ number_format($conciliacion->registros_esperados, 0, ',', '.') }} esperados.
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Composición del total activo</h2>
                    <p class="small text-muted">
                        Cómo se compone el total registrado
                        @if ($conciliacion->total_activos !== null)
                            ({{ number_format($conciliacion->total_activos, 0, ',', '.') }} activos).
                        @else
                            en el destino.
                        @endif
                    </p>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Origen</th>
                                    <th class="text-end">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($detallesTotal as $detalle)
                                    <tr>
                                        <td>{{ $detalle->origen }}</td>
                                        <td class="text-end">{{ number_format($detalle->cantidad, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">Sin desglose de total activo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Suma</th>
                                    <th class="text-end">{{ number_format($sumaTotal, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @if ($conciliacion->total_activos !== null)
                        <p class="small mt-3 mb-0 {{ $totalCuadra ? 'disp-verde' : 'disp-rojo' }}">
                            @if ($totalCuadra)
                                La suma iguala el total de activos ({{ number_format($conciliacion->total_activos, 0, ',', '.') }}).
                            @else
                                La suma no iguala el total de activos ({{ number_format($conciliacion->total_activos, 0, ',', '.') }}).
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ic">
        <div class="card-body">
            <h2 class="section-title mb-3">Referencias</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-3 text-muted">Integración</dt>
                <dd class="col-sm-9">
                    <a href="{{ route('integraciones.show', $conciliacion->integracion) }}">
                        {{ $conciliacion->integracion->nombre }}
                    </a>
                </dd>
                <dt class="col-sm-3 text-muted">Envío asociado</dt>
                <dd class="col-sm-9">
                    @if ($conciliacion->envio)
                        <a href="{{ route('envios.show', $conciliacion->envio) }}">
                            Envío #{{ $conciliacion->envio->id }}
                            · {{ $conciliacion->envio->fecha_inicio->format('d/m/Y H:i') }}
                            · {{ $conciliacion->envio->estado->etiqueta() }}
                        </a>
                    @else
                        Sin envío asociado
                    @endif
                </dd>
                <dt class="col-sm-3 text-muted">Destino</dt>
                <dd class="col-sm-9">{{ $conciliacion->destino ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">Archivo origen</dt>
                <dd class="col-sm-9">{{ $conciliacion->archivo_origen ?? '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
