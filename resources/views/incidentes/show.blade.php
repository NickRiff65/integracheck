@extends('layouts.app')

@section('title', $incidente->titulo)

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
        <div>
            <a href="{{ route('incidentes.index') }}" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left"></i> Volver a incidentes
            </a>
            <h1 class="mt-2 mb-1">{{ $incidente->titulo }}</h1>
            <p class="flujo mb-0">
                {{ $incidente->integracion->nombre }}
                · abierto {{ $incidente->fecha_apertura->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge-estado {{ $incidente->gravedad->value }}">{{ $incidente->gravedad->etiqueta() }}</span>
            <span class="badge-estado {{ $incidente->estado->value }}">{{ $incidente->estado->etiqueta() }}</span>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Detalle</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4 text-muted">Integración</dt>
                        <dd class="col-sm-8">
                            <a href="{{ route('integraciones.show', $incidente->integracion) }}">
                                {{ $incidente->integracion->nombre }}
                            </a>
                        </dd>
                        <dt class="col-sm-4 text-muted">Motivo</dt>
                        <dd class="col-sm-8">{{ $incidente->motivo }}</dd>
                        <dt class="col-sm-4 text-muted">Gravedad</dt>
                        <dd class="col-sm-8">{{ $incidente->gravedad->etiqueta() }}</dd>
                        <dt class="col-sm-4 text-muted">Estado</dt>
                        <dd class="col-sm-8">{{ $incidente->estado->etiqueta() }}</dd>
                        <dt class="col-sm-4 text-muted">Apertura</dt>
                        <dd class="col-sm-8">{{ $incidente->fecha_apertura->format('d/m/Y H:i') }}</dd>
                        <dt class="col-sm-4 text-muted">Resolución</dt>
                        <dd class="col-sm-8">{{ $incidente->fecha_resolucion?->format('d/m/Y H:i') ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Responsable</dt>
                        <dd class="col-sm-8">{{ $incidente->responsable ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Comentario</dt>
                        <dd class="col-sm-8">{{ $incidente->comentario_resolucion ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-ic h-100">
                <div class="card-body">
                    <h2 class="section-title mb-3">Actualizar incidente</h2>
                    <form method="POST" action="{{ route('incidentes.update', $incidente) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="estado">Estado</label>
                            <select name="estado" id="estado" class="form-select" required>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->value }}" @selected(old('estado', $incidente->estado->value) === $estado->value)>
                                        {{ $estado->etiqueta() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="responsable">Responsable</label>
                            <input
                                type="text"
                                name="responsable"
                                id="responsable"
                                class="form-control"
                                maxlength="255"
                                value="{{ old('responsable', $incidente->responsable) }}"
                            >
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="comentario_resolucion">Comentario de resolución</label>
                            <textarea
                                name="comentario_resolucion"
                                id="comentario_resolucion"
                                class="form-control"
                                rows="4"
                            >{{ old('comentario_resolucion', $incidente->comentario_resolucion) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
