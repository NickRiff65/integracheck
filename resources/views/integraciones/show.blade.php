@extends('layouts.app')

@section('title', $integracion->nombre)

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <a href="{{ route('dashboard') }}" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Volver al dashboard
            </a>
            <h1 class="mt-2 mb-1">{{ $integracion->nombre }}</h1>
            <p class="flujo mb-0">
                {{ $integracion->sistema_origen }}
                <i class="bi bi-arrow-right mx-1"></i>
                {{ $integracion->sistema_destino }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @include('partials.badge-salud', ['estado' => $salud['estado']])
            @include('partials.form-verificar', ['integracion' => $integracion])
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Datos de la integración</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Nombre</dt>
                        <dd class="col-sm-8">{{ $integracion->nombre }}</dd>
                        <dt class="col-sm-4 text-muted">Origen</dt>
                        <dd class="col-sm-8">{{ $integracion->sistema_origen }}</dd>
                        <dt class="col-sm-4 text-muted">Destino</dt>
                        <dd class="col-sm-8">{{ $integracion->sistema_destino }}</dd>
                        <dt class="col-sm-4 text-muted">URL base</dt>
                        <dd class="col-sm-8">{{ $integracion->url_base ?? 'No informada' }}</dd>
                        <dt class="col-sm-4 text-muted">Frecuencia</dt>
                        <dd class="col-sm-8">{{ $integracion->frecuenciaHumana() }} ({{ $integracion->frecuencia_minutos }} min)</dd>
                        <dt class="col-sm-4 text-muted">Activa</dt>
                        <dd class="col-sm-8">{{ $integracion->activa ? 'Sí' : 'No' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ic card-integracion salud-{{ $salud['estado'] }} h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Estado de salud actual</h2>
                    <p class="mb-3">{{ $salud['motivo'] }}</p>
                    @php
                        $disponibilidad = $integracion->disponibilidad30Dias();
                        $claseDisp = $disponibilidad >= 99 ? 'disp-verde' : ($disponibilidad >= 95 ? 'disp-amarillo' : 'disp-rojo');
                    @endphp
                    <p class="fw-bold {{ $claseDisp }} mb-3">
                        Disponibilidad 30 días: {{ number_format($disponibilidad, 1, ',', '.') }}%
                    </p>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Último envío</dt>
                        <dd class="col-sm-8">
                            @if ($integracion->ultimoEnvio)
                                {{ $integracion->ultimoEnvio->fecha_inicio->format('d/m/Y H:i') }}
                                <div class="text-muted">
                                    {{ $integracion->ultimoEnvio->fecha_inicio->locale('es')->diffForHumans() }}
                                    · {{ $integracion->ultimoEnvio->estado->etiqueta() }}
                                </div>
                            @else
                                Sin envíos registrados
                            @endif
                        </dd>
                        <dt class="col-sm-4 text-muted">HTTP</dt>
                        <dd class="col-sm-8">{{ $integracion->ultimoEnvio?->http_code ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Registros</dt>
                        <dd class="col-sm-8">
                            @if ($integracion->ultimoEnvio)
                                {{ $integracion->ultimoEnvio->registros_ok }}/{{ $integracion->ultimoEnvio->total_registros }} ok
                                · {{ $integracion->ultimoEnvio->registros_error }} error
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-ic mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h2 class="section-title mb-2">Última conciliación</h2>
                    @if ($integracion->ultimaConciliacion)
                        <div class="mb-2">
                            @include('partials.badge-conciliacion', ['conciliacion' => $integracion->ultimaConciliacion])
                        </div>
                        <p class="mb-1">{{ $integracion->ultimaConciliacion->fraseResumen() }}</p>
                        <p class="small text-muted mb-0">
                            {{ $integracion->ultimaConciliacion->fecha_conciliacion->format('d/m/Y H:i') }}
                            · {{ $integracion->ultimaConciliacion->fecha_conciliacion->locale('es')->diffForHumans() }}
                        </p>
                    @else
                        <p class="text-muted mb-0">Aún no hay conciliaciones para esta integración.</p>
                    @endif
                </div>
                @if ($integracion->ultimaConciliacion)
                    <a href="{{ route('conciliaciones.show', $integracion->ultimaConciliacion) }}" class="btn btn-sm btn-outline-primary">
                        Ver conciliación
                    </a>
                @endif
            </div>
        </div>
    </div>

    <h2 class="section-title mb-3">Patentes por estado</h2>
    <div class="row g-3 mb-4">
        @foreach ($conteos as $estado => $total)
            @php
                $enum = \App\Enums\EstadoPatente::from($estado);
                $iconos = [
                    'registrada' => ['bi-journal-text', 'kpi-azul'],
                    'enviada' => ['bi-send-check', 'kpi-verde'],
                    'rechazada' => ['bi-x-circle', 'kpi-rojo'],
                    'pendiente' => ['bi-hourglass-split', 'kpi-amarillo'],
                ];
                [$icono, $color] = $iconos[$estado] ?? ['bi-circle', 'kpi-noche'];
            @endphp
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card card-kpi h-100">
                    <div class="card-body">
                        <div class="kpi-icon {{ $color }}"><i class="bi {{ $icono }}"></i></div>
                        <div class="kpi-value">{{ $total }}</div>
                        <div class="kpi-label">{{ $enum->etiqueta() }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card card-ic mb-4" id="envios">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="section-title mb-0">Historial de envíos</h2>
                <a class="small" href="{{ route('envios.index', ['integracion_id' => $integracion->id]) }}">Ver en historial general</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                            <th>Registros</th>
                            <th>HTTP</th>
                            <th>Duración</th>
                            <th>Mensaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($envios as $envio)
                            <tr>
                                <td>
                                    {{ $envio->fecha_inicio->format('d/m/Y H:i') }}
                                    <div class="small text-muted">{{ $envio->fecha_inicio->locale('es')->diffForHumans() }}</div>
                                </td>
                                <td>{{ $envio->fecha_fin?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <span class="badge-estado {{ $envio->estado->value }}">{{ $envio->estado->etiqueta() }}</span>
                                </td>
                                <td>{{ $envio->registros_ok }}/{{ $envio->total_registros }} ok · {{ $envio->registros_error }} error</td>
                                <td>{{ $envio->http_code ?? '—' }}</td>
                                <td>{{ $envio->duracion_seg !== null ? $envio->duracion_seg.' s' : '—' }}</td>
                                <td class="small text-muted">{{ $envio->mensaje_error ? \Illuminate\Support\Str::limit($envio->mensaje_error, 60) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No hay envíos para esta integración.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $envios->links() }}</div>
        </div>
    </div>

    <div class="card card-ic mb-4" id="patentes">
        <div class="card-body">
            <h2 class="section-title mb-3">Patentes</h2>
            <form class="row g-2 align-items-end mb-3" method="get" action="{{ route('integraciones.show', $integracion) }}">
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Buscar</label>
                    <input
                        type="search"
                        name="q"
                        value="{{ $filtros['q'] }}"
                        class="form-control"
                        placeholder="Número de patente o titular"
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($estadosPatente as $estado)
                            <option value="{{ $estado->value }}" @selected($filtros['estado'] === $estado->value)>
                                {{ $estado->etiqueta() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Buscar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('integraciones.show', $integracion) }}#patentes">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Patente</th>
                            <th>Titular</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha registro</th>
                            <th>Último envío</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($patentes as $patente)
                            <tr>
                                <td class="fw-semibold">{{ $patente->numero_patente }}</td>
                                <td>{{ $patente->titular }}</td>
                                <td>{{ $patente->tipo }}</td>
                                <td>
                                    <span class="badge-estado {{ $patente->estado->value }}">{{ $patente->estado->etiqueta() }}</span>
                                </td>
                                <td>{{ $patente->fecha_registro?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    @if ($patente->ultimoEnvio)
                                        {{ $patente->ultimoEnvio->fecha_inicio->format('d/m/Y H:i') }}
                                        <div class="small text-muted">{{ $patente->ultimoEnvio->estado->etiqueta() }}</div>
                                    @else
                                        <span class="text-muted">Sin envío</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No se encontraron patentes con esos criterios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $patentes->links() }}</div>
        </div>
    </div>

    <div class="card card-ic">
        <div class="card-body">
            <h2 class="section-title mb-3">Últimos chequeos de salud</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Resultado</th>
                            <th>Latencia</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($chequeos as $chequeo)
                            <tr>
                                <td>
                                    {{ $chequeo->created_at->format('d/m/Y H:i') }}
                                    <div class="small text-muted">{{ $chequeo->created_at->locale('es')->diffForHumans() }}</div>
                                </td>
                                <td class="text-capitalize">{{ $chequeo->tipo }}</td>
                                <td>
                                    <span class="badge-estado {{ $chequeo->resultado ? 'ok' : 'fail' }}">
                                        {{ $chequeo->resultado ? 'Correcto' : 'Fallido' }}
                                    </span>
                                </td>
                                <td>{{ $chequeo->latencia_ms !== null ? $chequeo->latencia_ms.' ms' : '—' }}</td>
                                <td class="small text-muted">{{ $chequeo->detalle ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Sin chequeos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
