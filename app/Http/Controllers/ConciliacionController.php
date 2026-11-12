<?php

namespace App\Http\Controllers;

use App\Enums\TipoDetalleConciliacion;
use App\Models\Conciliacion;
use App\Models\Integracion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConciliacionController extends Controller
{
    public function index(): View
    {
        $conciliaciones = Conciliacion::query()
            ->with('integracion')
            ->latest('fecha_conciliacion')
            ->paginate(15);

        $total = Conciliacion::query()->count();
        $queCuadran = Conciliacion::query()->get()->filter(
            fn (Conciliacion $conciliacion) => $conciliacion->estado() === 'cuadra'
        )->count();
        $ultima = Conciliacion::query()->with('integracion')->latest('fecha_conciliacion')->first();

        return view('conciliaciones.index', [
            'conciliaciones' => $conciliaciones,
            'resumen' => [
                'total' => $total,
                'porcentaje_cuadran' => $total > 0 ? round(($queCuadran / $total) * 100, 1) : 0.0,
                'ultima' => $ultima,
            ],
        ]);
    }

    public function create(): View
    {
        $integraciones = Integracion::query()
            ->with(['envios' => fn ($query) => $query->latest('fecha_inicio')->limit(20)])
            ->orderBy('nombre')
            ->get()
            ->map(function (Integracion $integracion) {
                $integracion->setAttribute('envios_opciones', $integracion->envios->map(fn ($envio) => [
                    'id' => $envio->id,
                    'etiqueta' => '#'.$envio->id.' · '.$envio->fecha_inicio->format('d/m/Y H:i').' · '.$envio->estado->etiqueta(),
                ])->values());

                return $integracion;
            });

        $tiposDetalle = collect(TipoDetalleConciliacion::cases())
            ->map(fn (TipoDetalleConciliacion $tipo) => [
                'value' => $tipo->value,
                'etiqueta' => $tipo->etiqueta(),
            ])
            ->values();

        return view('conciliaciones.create', [
            'integraciones' => $integraciones,
            'tiposDetalle' => $tiposDetalle,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mensajes = [
            'required' => 'El campo :attribute es obligatorio.',
            'integer' => ':attribute debe ser un número entero.',
            'min' => ':attribute debe ser un entero mayor o igual a 0.',
            'exists' => 'El valor seleccionado en :attribute no es válido.',
            'date' => ':attribute no es una fecha válida.',
            'max' => ':attribute no puede superar :max caracteres.',
        ];

        $atributos = [
            'integracion_id' => 'integración',
            'envio_id' => 'envío',
            'archivo_origen' => 'archivo de origen',
            'destino' => 'destino',
            'registros_esperados' => 'registros esperados',
            'registros_registrados' => 'registros registrados',
            'registros_nuevos' => 'registros nuevos',
            'registros_existentes' => 'registros existentes',
            'registros_faltantes' => 'registros faltantes',
            'registros_duplicados' => 'registros duplicados',
            'total_activos' => 'total de activos',
            'fecha_conciliacion' => 'fecha de conciliación',
            'detalles.*.tipo' => 'tipo de detalle',
            'detalles.*.origen' => 'origen',
            'detalles.*.cantidad' => 'cantidad',
        ];

        $datos = $request->validate([
            'integracion_id' => ['required', 'integer', 'exists:integraciones,id'],
            'envio_id' => [
                'nullable',
                'integer',
                Rule::exists('envios', 'id')->where('integracion_id', $request->integer('integracion_id')),
            ],
            'archivo_origen' => ['nullable', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
            'registros_esperados' => ['required', 'integer', 'min:0'],
            'registros_registrados' => ['required', 'integer', 'min:0'],
            'registros_nuevos' => ['required', 'integer', 'min:0'],
            'registros_existentes' => ['required', 'integer', 'min:0'],
            'registros_faltantes' => ['required', 'integer', 'min:0'],
            'registros_duplicados' => ['required', 'integer', 'min:0'],
            'total_activos' => ['nullable', 'integer', 'min:0'],
            'fecha_conciliacion' => ['required', 'date'],
            'detalles' => ['nullable', 'array'],
            'detalles.*.tipo' => ['nullable', Rule::enum(TipoDetalleConciliacion::class)],
            'detalles.*.origen' => ['nullable', 'string', 'max:255'],
            'detalles.*.cantidad' => ['nullable', 'integer', 'min:0'],
        ], $mensajes, $atributos);

        $detalles = collect($datos['detalles'] ?? [])
            ->filter(function (array $detalle) {
                return filled($detalle['origen'] ?? null)
                    && isset($detalle['tipo'])
                    && $detalle['tipo'] !== ''
                    && ($detalle['cantidad'] ?? '') !== '';
            })
            ->values();

        foreach ($detalles as $indice => $detalle) {
            $request->validate([
                "detalles.$indice.tipo" => ['required', Rule::enum(TipoDetalleConciliacion::class)],
                "detalles.$indice.origen" => ['required', 'string', 'max:255'],
                "detalles.$indice.cantidad" => ['required', 'integer', 'min:0'],
            ], $mensajes, $atributos);
        }

        $conciliacion = DB::transaction(function () use ($datos, $detalles) {
            $conciliacion = Conciliacion::query()->create([
                'integracion_id' => $datos['integracion_id'],
                'envio_id' => $datos['envio_id'] ?: null,
                'archivo_origen' => $datos['archivo_origen'] ?: null,
                'destino' => $datos['destino'] ?: null,
                'registros_esperados' => $datos['registros_esperados'],
                'registros_registrados' => $datos['registros_registrados'],
                'registros_nuevos' => $datos['registros_nuevos'],
                'registros_existentes' => $datos['registros_existentes'],
                'registros_faltantes' => $datos['registros_faltantes'],
                'registros_duplicados' => $datos['registros_duplicados'],
                'total_activos' => $datos['total_activos'] !== null && $datos['total_activos'] !== ''
                    ? $datos['total_activos']
                    : null,
                'fecha_conciliacion' => $datos['fecha_conciliacion'],
            ]);

            foreach ($detalles as $detalle) {
                $conciliacion->detalles()->create([
                    'tipo' => $detalle['tipo'],
                    'origen' => $detalle['origen'],
                    'cantidad' => $detalle['cantidad'],
                ]);
            }

            return $conciliacion;
        });

        return redirect()
            ->route('conciliaciones.show', $conciliacion)
            ->with('exito', 'Conciliación registrada.');
    }

    public function show(Conciliacion $conciliacion): View
    {
        $conciliacion->load(['integracion', 'envio', 'detalles']);

        return view('conciliaciones.show', [
            'conciliacion' => $conciliacion,
            'detallesCarga' => $conciliacion->detallesCarga(),
            'detallesTotal' => $conciliacion->detallesTotalActivo(),
        ]);
    }
}
