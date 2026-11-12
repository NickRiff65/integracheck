<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIncidente;
use App\Models\Envio;
use App\Models\Incidente;
use App\Models\Integracion;
use App\Models\Patente;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InformeController extends Controller
{
    public function index(Request $request): View
    {
        $dias = (int) $request->query('dias', 30);

        if (! in_array($dias, [7, 30, 90], true)) {
            $dias = 30;
        }

        $desde = now()->subDays($dias - 1)->startOfDay();
        $hasta = now();

        $enviosPeriodo = Envio::query()->where('fecha_inicio', '>=', $desde);
        $totalEnvios = (clone $enviosPeriodo)->count();
        $finalizados = (clone $enviosPeriodo)->where('estado', '!=', EstadoEnvio::EnCurso)->count();
        $exitosos = (clone $enviosPeriodo)->where('estado', EstadoEnvio::Exitoso)->count();
        $registrosProcesados = (int) (clone $enviosPeriodo)->sum('total_registros');
        $registrosError = (int) (clone $enviosPeriodo)->sum('registros_error');

        $porcentajeExito = $finalizados > 0
            ? round(($exitosos / $finalizados) * 100, 1)
            : 0.0;

        $tarjetas = [
            'envios' => $totalEnvios,
            'exito_global' => $porcentajeExito,
            'registros_procesados' => $registrosProcesados,
            'registros_error' => $registrosError,
            'patentes' => Patente::query()->where('fecha_registro', '>=', $desde->toDateString())->count(),
            'incidentes_abiertos' => Incidente::query()
                ->where('fecha_apertura', '>=', $desde)
                ->whereIn('estado', [EstadoIncidente::Abierto, EstadoIncidente::EnRevision])
                ->count(),
            'incidentes_resueltos' => Incidente::query()
                ->where('estado', EstadoIncidente::Resuelto)
                ->where(function ($query) use ($desde) {
                    $query->where('fecha_resolucion', '>=', $desde)
                        ->orWhere(function ($interno) use ($desde) {
                            $interno->whereNull('fecha_resolucion')
                                ->where('fecha_apertura', '>=', $desde);
                        });
                })
                ->count(),
        ];

        $integraciones = Integracion::query()->orderBy('nombre')->get();
        $rankings = $this->rankings($integraciones, $desde);

        return view('informes.index', [
            'dias' => $dias,
            'desde' => $desde,
            'hasta' => $hasta,
            'tarjetas' => $tarjetas,
            'grafico' => $this->serieDiaria($desde, $dias),
            'masErrores' => $rankings['errores'],
            'masConfiables' => $rankings['confiables'],
        ]);
    }

    /**
     * @return array{labels: list<string>, exitosos: list<int>, problemas: list<int>}
     */
    private function serieDiaria(CarbonInterface $desde, int $dias): array
    {
        $filas = Envio::query()
            ->selectRaw('DATE(fecha_inicio) as dia, estado, COUNT(*) as total')
            ->where('fecha_inicio', '>=', $desde)
            ->groupByRaw('DATE(fecha_inicio), estado')
            ->get()
            ->groupBy('dia');

        $labels = [];
        $exitosos = [];
        $problemas = [];

        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = now()->subDays($i)->startOfDay();
            $delDia = $filas->get($dia->toDateString(), collect());

            $labels[] = $dia->format('d/m');
            $exitosos[] = $this->totalEstado($delDia, EstadoEnvio::Exitoso);
            $problemas[] = $this->totalEstado($delDia, EstadoEnvio::Parcial)
                + $this->totalEstado($delDia, EstadoEnvio::Fallido);
        }

        return [
            'labels' => $labels,
            'exitosos' => $exitosos,
            'problemas' => $problemas,
        ];
    }

    /**
     * @param  Collection<int, Integracion>  $integraciones
     * @return array{errores: Collection<int, array<string, mixed>>, confiables: Collection<int, array<string, mixed>>}
     */
    private function rankings(Collection $integraciones, CarbonInterface $desde): array
    {
        $stats = Envio::query()
            ->selectRaw("
                integracion_id,
                COALESCE(SUM(registros_error), 0) as total_errores,
                COALESCE(SUM(total_registros), 0) as total_registros,
                SUM(CASE WHEN estado = 'exitoso' THEN 1 ELSE 0 END) as envios_exitosos,
                SUM(CASE WHEN estado <> 'en_curso' THEN 1 ELSE 0 END) as envios_finalizados
            ")
            ->where('fecha_inicio', '>=', $desde)
            ->groupBy('integracion_id')
            ->get()
            ->keyBy('integracion_id');

        $filas = $integraciones->map(function (Integracion $integracion) use ($stats) {
            $fila = $stats->get($integracion->id);
            $errores = (int) ($fila->total_errores ?? 0);
            $registros = (int) ($fila->total_registros ?? 0);
            $exitosos = (int) ($fila->envios_exitosos ?? 0);
            $finalizados = (int) ($fila->envios_finalizados ?? 0);

            return [
                'integracion' => $integracion,
                'total_errores' => $errores,
                'tasa_error' => $registros > 0 ? round(($errores / $registros) * 100, 1) : 0.0,
                'disponibilidad' => $finalizados > 0 ? round(($exitosos / $finalizados) * 100, 1) : 0.0,
                'envios_finalizados' => $finalizados,
            ];
        });

        return [
            'errores' => $filas->sortByDesc('total_errores')->values(),
            'confiables' => $filas->sortByDesc('disponibilidad')->values(),
        ];
    }

    /**
     * @param  Collection<int, Envio>  $filas
     */
    private function totalEstado(Collection $filas, EstadoEnvio $estado): int
    {
        $fila = $filas->first(function (Envio $envio) use ($estado) {
            $valor = $envio->estado instanceof EstadoEnvio
                ? $envio->estado
                : EstadoEnvio::tryFrom((string) $envio->estado);

            return $valor === $estado;
        });

        return (int) ($fila->total ?? 0);
    }
}
