@extends('layouts.app')

@section('title', 'Historial de envíos')

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <h1 class="mb-1">Historial de envíos</h1>
            <p>Lotes de patentes enviados a sistemas destino en Chile, con filtro por integración y estado.</p>
        </div>
        <span class="text-muted small">{{ number_format($envios->total(), 0, ',', '.') }} registros</span>
    </div>

    <div class="card card-ic mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="{{ route('envios.index') }}">
                <div class="col-md-4">
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
                <div class="col-md-5 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="{{ route('envios.index') }}">Limpiar</a>
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
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Integración</th>
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
                                    <a href="{{ route('envios.show', $envio) }}" class="text-decoration-none">
                                        {{ $envio->fecha_inicio->format('d/m/Y H:i') }}
                                    </a>
                                    <div class="small text-muted">{{ $envio->fecha_inicio->locale('es')->diffForHumans() }}</div>
                                </td>
                                <td>{{ $envio->fecha_fin?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('integraciones.show', $envio->integracion) }}">
                                        {{ $envio->integracion->nombre }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-estado {{ $envio->estado->value }}">{{ $envio->estado->etiqueta() }}</span>
                                    @if ($envio->conciliacion)
                                        <a href="{{ route('conciliaciones.show', $envio->conciliacion) }}" class="d-inline-block mt-1 text-decoration-none">
                                            @include('partials.badge-conciliacion', ['conciliacion' => $envio->conciliacion])
                                        </a>
                                    @endif
                                </td>
                                <td>{{ $envio->registros_ok }}/{{ $envio->total_registros }} ok · {{ $envio->registros_error }} err.</td>
                                <td>{{ $envio->http_code ?? '—' }}</td>
                                <td>{{ $envio->duracion_seg !== null ? $envio->duracion_seg.' s' : '—' }}</td>
                                <td class="small text-muted">{{ $envio->mensaje_error ? \Illuminate\Support\Str::limit($envio->mensaje_error, 70) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted">No hay envíos con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $envios->links() }}</div>
        </div>
    </div>
@endsection
