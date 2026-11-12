@extends('layouts.app')

@section('title', 'Registrar conciliación')

@section('content')
    <div class="page-header mb-4">
        <a href="{{ route('conciliaciones.index') }}" class="small text-decoration-none text-muted">
            <i class="bi bi-arrow-left"></i> Volver a conciliaciones
        </a>
        <h1 class="mt-2 mb-1">Registrar conciliación</h1>
        <p>Cruce manual entre la fuente de carga y lo registrado en el destino.</p>
    </div>

    @if ($errors->any())
        <div class="flash-error mb-4" role="alert">
            <div class="fw-semibold mb-1">Revisa los datos del formulario</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('conciliaciones.store') }}" id="form-conciliacion">
        @csrf

        <div class="card card-ic mb-3">
            <div class="card-body">
                <h2 class="section-title mb-3">Datos del cruce</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="integracion_id">Integración</label>
                        <select name="integracion_id" id="integracion_id" class="form-select" required>
                            <option value="">Selecciona una integración</option>
                            @foreach ($integraciones as $integracion)
                                <option
                                    value="{{ $integracion->id }}"
                                    @selected(old('integracion_id') == $integracion->id)
                                    data-envios='@json($integracion->envios_opciones)'
                                >
                                    {{ $integracion->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="envio_id">Envío asociado (opcional)</label>
                        <select name="envio_id" id="envio_id" class="form-select">
                            <option value="">Sin envío</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="archivo_origen">Archivo de origen</label>
                        <input type="text" name="archivo_origen" id="archivo_origen" class="form-control" maxlength="255" value="{{ old('archivo_origen') }}" placeholder="plantilla_carga.xlsx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="destino">Destino</label>
                        <input type="text" name="destino" id="destino" class="form-control" maxlength="255" value="{{ old('destino') }}" placeholder="nombre de la tabla destino">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="fecha_conciliacion">Fecha de conciliación</label>
                        <input type="datetime-local" name="fecha_conciliacion" id="fecha_conciliacion" class="form-control" required value="{{ old('fecha_conciliacion', now()->format('Y-m-d\\TH:i')) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-ic mb-3">
            <div class="card-body">
                <h2 class="section-title mb-3">Conteos</h2>
                <p class="small text-muted">Todos los conteos son enteros mayores o iguales a 0.</p>
                <div class="row g-3">
                    @foreach ([
                        'registros_esperados' => 'Esperados',
                        'registros_registrados' => 'Registrados',
                        'registros_nuevos' => 'Nuevos',
                        'registros_existentes' => 'Existentes',
                        'registros_faltantes' => 'Faltantes',
                        'registros_duplicados' => 'Duplicados',
                    ] as $campo => $etiqueta)
                        <div class="col-6 col-md-4">
                            <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                            <input type="number" min="0" step="1" name="{{ $campo }}" id="{{ $campo }}" class="form-control" required value="{{ old($campo, 0) }}">
                        </div>
                    @endforeach
                    <div class="col-6 col-md-4">
                        <label class="form-label" for="total_activos">Total de activos</label>
                        <input type="number" min="0" step="1" name="total_activos" id="total_activos" class="form-control" value="{{ old('total_activos') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-ic mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="section-title mb-0">Detalles de desglose</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="agregar-detalle">
                        <i class="bi bi-plus-lg me-1"></i> Agregar detalle
                    </button>
                </div>
                <p class="small text-muted">En carga, cuánto vino de cada origen. En total activo, cómo se compone el total registrado.</p>
                <div id="filas-detalle"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar conciliación</button>
        <a href="{{ route('conciliaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
@endsection

@push('scripts')
    <script>
        const selectIntegracion = document.getElementById('integracion_id');
        const selectEnvio = document.getElementById('envio_id');
        const envioSeleccionado = @json(old('envio_id'));

        const actualizarEnvios = () => {
            const option = selectIntegracion.options[selectIntegracion.selectedIndex];
            const envios = option && option.dataset.envios ? JSON.parse(option.dataset.envios) : [];
            selectEnvio.innerHTML = '<option value="">Sin envío</option>';
            envios.forEach((envio) => {
                const item = document.createElement('option');
                item.value = envio.id;
                item.textContent = envio.etiqueta;
                if (String(envioSeleccionado) === String(envio.id)) {
                    item.selected = true;
                }
                selectEnvio.appendChild(item);
            });
        };

        selectIntegracion.addEventListener('change', actualizarEnvios);
        actualizarEnvios();

        const contenedor = document.getElementById('filas-detalle');
        const tipos = @json($tiposDetalle);
        const viejos = @json(old('detalles', []));
        let indiceDetalle = 0;

        const filaDetalle = (detalle = {}) => {
            const i = indiceDetalle++;
            const wrap = document.createElement('div');
            wrap.className = 'row g-2 align-items-end mb-2 js-fila-detalle';
            wrap.innerHTML = `
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tipo</label>
                    <select name="detalles[${i}][tipo]" class="form-select">
                        ${tipos.map((tipo) => `<option value="${tipo.value}" ${detalle.tipo === tipo.value ? 'selected' : ''}>${tipo.etiqueta}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Origen</label>
                    <input type="text" name="detalles[${i}][origen]" class="form-control" maxlength="255" value="${detalle.origen ?? ''}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Cantidad</label>
                    <input type="number" min="0" step="1" name="detalles[${i}][cantidad]" class="form-control" value="${detalle.cantidad ?? ''}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary w-100 js-quitar" title="Quitar">&times;</button>
                </div>
            `;
            wrap.querySelector('.js-quitar').addEventListener('click', () => wrap.remove());
            return wrap;
        };

        (viejos.length ? viejos : [{ tipo: 'carga', origen: '', cantidad: '' }]).forEach((detalle) => contenedor.appendChild(filaDetalle(detalle)));

        document.getElementById('agregar-detalle').addEventListener('click', () => {
            contenedor.appendChild(filaDetalle({ tipo: 'carga' }));
        });
    </script>
@endpush
