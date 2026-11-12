@extends('layouts.app')

@section('title', 'Envío #'.$envio->id)

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <a href="{{ route('envios.index') }}" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Volver al historial
            </a>
            <h1 class="mt-2 mb-1">Envío #{{ $envio->id }}</h1>
            <p class="flujo mb-0">
                {{ $envio->integracion->nombre }}
                · {{ $envio->fecha_inicio->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge-estado {{ $envio->estado->value }}">{{ $envio->estado->etiqueta() }}</span>
            @if ($envio->conciliacion)
                <a href="{{ route('conciliaciones.show', $envio->conciliacion) }}" class="text-decoration-none">
                    @include('partials.badge-conciliacion', ['conciliacion' => $envio->conciliacion])
                </a>
            @endif
        </div>
    </div>

    <div class="card card-ic">
        <div class="card-body">
            <h2 class="section-title mb-3">Datos del envío</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-3 text-muted">Integración</dt>
                <dd class="col-sm-9">
                    <a href="{{ route('integraciones.show', $envio->integracion) }}">{{ $envio->integracion->nombre }}</a>
                </dd>
                <dt class="col-sm-3 text-muted">Inicio</dt>
                <dd class="col-sm-9">{{ $envio->fecha_inicio->format('d/m/Y H:i') }}</dd>
                <dt class="col-sm-3 text-muted">Fin</dt>
                <dd class="col-sm-9">{{ $envio->fecha_fin?->format('d/m/Y H:i') ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">Registros</dt>
                <dd class="col-sm-9">{{ $envio->registros_ok }}/{{ $envio->total_registros }} ok · {{ $envio->registros_error }} error</dd>
                <dt class="col-sm-3 text-muted">HTTP</dt>
                <dd class="col-sm-9">{{ $envio->http_code ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">Duración</dt>
                <dd class="col-sm-9">{{ $envio->duracion_seg !== null ? $envio->duracion_seg.' s' : '—' }}</dd>
                <dt class="col-sm-3 text-muted">Mensaje</dt>
                <dd class="col-sm-9">{{ $envio->mensaje_error ?? '—' }}</dd>
                <dt class="col-sm-3 text-muted">Conciliación</dt>
                <dd class="col-sm-9">
                    @if ($envio->conciliacion)
                        <a href="{{ route('conciliaciones.show', $envio->conciliacion) }}">
                            @include('partials.badge-conciliacion', ['conciliacion' => $envio->conciliacion])
                            <span class="ms-1">{{ $envio->conciliacion->fecha_conciliacion->format('d/m/Y H:i') }}</span>
                        </a>
                    @else
                        Sin conciliación asociada
                    @endif
                </dd>
            </dl>
        </div>
    </div>
@endsection
