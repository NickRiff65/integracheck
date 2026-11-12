<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPatente;
use App\Models\Envio;
use App\Models\Integracion;
use App\Models\Patente;
use App\Services\HealthStatusService;
use App\Services\SimuladorEnviosService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(HealthStatusService $healthStatusService): View
    {
        $integraciones = Integracion::query()
            ->with('ultimoEnvio')
            ->withCount([
                'patentes as patentes_totales',
                'patentes as patentes_enviadas' => fn ($query) => $query->where('estado', EstadoPatente::Enviada),
                'patentes as patentes_con_error' => fn ($query) => $query->where('estado', EstadoPatente::Rechazada),
                'patentes as patentes_pendientes' => fn ($query) => $query->where('estado', EstadoPatente::Pendiente),
            ])
            ->orderBy('nombre')
            ->get();

        $series = $this->seriesUltimos7Dias($integraciones);

        $tarjetas = $integraciones->map(function (Integracion $integracion) use ($healthStatusService, $series) {
            return [
                'integracion' => $integracion,
                'salud' => $healthStatusService->evaluar($integracion),
                'disponibilidad' => $integracion->disponibilidad30Dias(),
                'patentes' => [
                    'totales' => $integracion->patentes_totales,
                    'enviadas' => $integracion->patentes_enviadas,
                    'con_error' => $integracion->patentes_con_error,
                    'pendientes' => $integracion->patentes_pendientes,
                ],
                'grafico' => $series->get($integracion->id),
            ];
        });

        $saludables = $tarjetas->where('salud.estado', 'verde')->count();
        $alertas = $tarjetas->filter(
            fn (array $tarjeta) => in_array($tarjeta['salud']['estado'], ['amarillo', 'rojo'], true)
        )->values();

        return view('dashboard', [
            'resumen' => [
                'total_integraciones' => $integraciones->count(),
                'saludables' => $saludables,
                'con_problemas' => $integraciones->count() - $saludables,
                'patentes_registradas' => Patente::query()->count(),
            ],
            'tarjetas' => $tarjetas,
            'alertas' => $alertas,
        ]);
    }

    public function simular(SimuladorEnviosService $simulador): RedirectResponse
    {
        $cantidad = $simulador->ejecutar();

        return redirect()
            ->route('dashboard')
            ->with('exito', "Simulación ejecutada: se generaron {$cantidad} envíos nuevos");
    }

    /**
     * @param  Collection<int, Integracion>  $integraciones
     * @return Collection<int, array{labels: list<string>, exitosos: list<int>, parciales: list<int>, fallidos: list<int>}>
     */
    private function seriesUltimos7Dias(Collection $integraciones): Collection
    {
        $desde = now()->subDays(6)->startOfDay();

        $filas = Envio::query()
            ->selectRaw('integracion_id, DATE(fecha_inicio) as dia, estado, COUNT(*) as total')
            ->where('fecha_inicio', '>=', $desde)
            ->whereIn('integracion_id', $integraciones->modelKeys())
            ->groupByRaw('integracion_id, DATE(fecha_inicio), estado')
            ->get();

        $porIntegracion = $filas->groupBy('integracion_id');
        $dias = collect(range(6, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());

        return $integraciones->mapWithKeys(function (Integracion $integracion) use ($porIntegracion, $dias) {
            $porDia = $porIntegracion->get($integracion->id, collect())->groupBy('dia');

            $labels = [];
            $exitosos = [];
            $parciales = [];
            $fallidos = [];

            foreach ($dias as $dia) {
                $delDia = $porDia->get($dia->toDateString(), collect());
                $labels[] = $dia->locale('es')->isoFormat('dd');
                $exitosos[] = $this->totalEstado($delDia, EstadoEnvio::Exitoso);
                $parciales[] = $this->totalEstado($delDia, EstadoEnvio::Parcial);
                $fallidos[] = $this->totalEstado($delDia, EstadoEnvio::Fallido);
            }

            return [
                $integracion->id => [
                    'labels' => $labels,
                    'exitosos' => $exitosos,
                    'parciales' => $parciales,
                    'fallidos' => $fallidos,
                ],
            ];
        });
    }

    /**
     * @param  Collection<int, Envio>  $filas
     */
    private function totalEstado(Collection $filas, EstadoEnvio $estado): int
    {
        $fila = $filas->first(function (Envio $envio) use ($estado) {
            $valor = $envio->estado instanceof EstadoEnvio ? $envio->estado : EstadoEnvio::tryFrom((string) $envio->estado);

            return $valor === $estado;
        });

        return (int) ($fila->total ?? 0);
    }
}
