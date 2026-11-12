<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPatente;
use App\Models\ChequeoSalud;
use App\Models\Envio;
use App\Models\Integracion;
use App\Services\HealthStatusService;
use App\Services\IncidenteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IntegracionController extends Controller
{
    public function show(Request $request, Integracion $integracion, HealthStatusService $healthStatusService): View
    {
        $integracion->load(['ultimoEnvio', 'ultimaConciliacion']);

        $envios = $integracion->envios()
            ->latest('fecha_inicio')
            ->paginate(10, ['*'], 'envios_page')
            ->fragment('envios')
            ->withQueryString();

        $busqueda = $request->string('q')->trim()->toString();
        $estadoPatente = EstadoPatente::tryFrom($request->string('estado')->toString());

        $patentes = $integracion->patentes()
            ->with('ultimoEnvio')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $termino = '%'.addcslashes($busqueda, '%_\\').'%';

                $query->where(function ($interno) use ($termino) {
                    $interno->where('numero_patente', 'like', $termino)
                        ->orWhere('titular', 'like', $termino);
                });
            })
            ->when($estadoPatente, fn ($query) => $query->where('estado', $estadoPatente))
            ->latest('fecha_registro')
            ->paginate(12, ['*'], 'patentes_page')
            ->fragment('patentes')
            ->withQueryString();

        $chequeos = $integracion->chequeosSalud()
            ->latest()
            ->limit(8)
            ->get();

        return view('integraciones.show', [
            'integracion' => $integracion,
            'salud' => $healthStatusService->evaluar($integracion),
            'conteos' => $integracion->conteoPatentesPorEstado(),
            'envios' => $envios,
            'patentes' => $patentes,
            'chequeos' => $chequeos,
            'estadosPatente' => EstadoPatente::cases(),
            'filtros' => [
                'q' => $busqueda,
                'estado' => $estadoPatente?->value,
            ],
        ]);
    }

    public function verificar(Integracion $integracion, IncidenteService $incidenteService): RedirectResponse
    {
        $integracion->load('ultimoEnvio');

        [$resultado, $detalle] = $this->resultadoChequeoSimulado($integracion);

        ChequeoSalud::query()->create([
            'integracion_id' => $integracion->id,
            'tipo' => 'manual',
            'resultado' => $resultado,
            'latencia_ms' => random_int(120, 650),
            'detalle' => $detalle,
        ]);

        $incidenteService->revisar($integracion);

        return back()->with(
            'exito',
            "Verificación realizada: {$integracion->nombre} — {$detalle}"
        );
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function resultadoChequeoSimulado(Integracion $integracion): array
    {
        $ultimo = $integracion->ultimoEnvio;

        if ($ultimo === null || $ultimo->estado === EstadoEnvio::Fallido || $this->estaAtrasada($integracion, $ultimo)) {
            return [false, 'Sin respuesta / envío atrasado'];
        }

        if ($ultimo->estado === EstadoEnvio::Parcial) {
            return [true, 'El servicio respondió con advertencias: el último envío fue parcial.'];
        }

        return [true, 'Respuesta correcta del servicio'];
    }

    private function estaAtrasada(Integracion $integracion, Envio $ultimo): bool
    {
        $referencia = $ultimo->fecha_fin ?? $ultimo->fecha_inicio;
        $atraso = (int) round($referencia->diffInMinutes(now(), true));

        return $atraso > max(1, $integracion->frecuencia_minutos);
    }
}
