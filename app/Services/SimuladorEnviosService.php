<?php

namespace App\Services;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPatente;
use App\Models\Envio;
use App\Models\Integracion;
use App\Models\Patente;
use Illuminate\Support\Facades\DB;

class SimuladorEnviosService
{
    /**
     * @var list<string>
     */
    private const MENSAJES_PARCIAL = [
        'Validación parcial: algunas patentes fueron rechazadas por formato BBCL12 inválido.',
        'El destino aceptó el lote con observaciones: titular incompleto en parte de los registros.',
        'HTTP 422: el esquema de algunos vehículos no coincide con el catálogo comunal.',
        'Registros observados: comuna de origen no informada o dígito verificador inconsistente.',
        'El lote se procesó con advertencias: duplicados detectados en el padrón municipal.',
    ];

    /**
     * @var list<string>
     */
    private const MENSAJES_FALLIDO_HTTP = [
        'Error interno del servidor destino (HTTP 500). No se pudo procesar el lote.',
        'El servicio destino no está disponible (HTTP 503 Service Unavailable).',
        'Falla al persistir el lote en el sistema receptor. Reintente más tarde.',
        'El destino rechazó la conexión de servicio por saturación del canal.',
    ];

    /**
     * @var list<string>
     */
    private const MENSAJES_TIMEOUT = [
        'Tiempo de espera agotado al conectar con el servicio destino (timeout de 30 segundos).',
        'Sin respuesta del endpoint de recepción de patentes. Se abortó el envío por timeout.',
        'Timeout de red al establecer el canal TLS con el sistema destino.',
    ];

    public function __construct(
        private readonly HealthStatusService $healthStatusService,
        private readonly IncidenteService $incidenteService,
    ) {}

    public function ejecutar(): int
    {
        $integraciones = Integracion::query()
            ->with('ultimoEnvio')
            ->where('activa', true)
            ->get();

        $generados = 0;

        foreach ($integraciones as $integracion) {
            $this->incidenteService->revisar($integracion);

            $this->generarEnvio($integracion);
            $generados++;

            $integracion->unsetRelation('ultimoEnvio');
            $integracion->load('ultimoEnvio');
            $this->incidenteService->revisar($integracion);
        }

        return $generados;
    }

    private function generarEnvio(Integracion $integracion): Envio
    {
        $penalizado = $this->debePenalizar($integracion);
        $estado = $this->elegirEstado($penalizado);
        $total = random_int(5, 80);
        [$ok, $error] = $this->conteoRegistros($estado, $total);
        [$httpCode, $mensaje] = $this->detalleHttp($estado);
        $duracion = random_int(5, 90);
        $fechaFin = now();
        $fechaInicio = $fechaFin->copy()->subSeconds($duracion);

        return DB::transaction(function () use ($integracion, $estado, $total, $ok, $error, $httpCode, $mensaje, $duracion, $fechaInicio, $fechaFin) {
            $envio = Envio::query()->create([
                'integracion_id' => $integracion->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => $estado,
                'total_registros' => $total,
                'registros_ok' => $ok,
                'registros_error' => $error,
                'http_code' => $httpCode,
                'mensaje_error' => $mensaje,
                'duracion_seg' => $duracion,
            ]);

            $this->moverPatentes($integracion, $envio, $ok, $error);

            return $envio;
        });
    }

    private function debePenalizar(Integracion $integracion): bool
    {
        $ultimo = $integracion->ultimoEnvio;

        if ($ultimo?->estado === EstadoEnvio::Fallido) {
            return true;
        }

        return $this->healthStatusService->evaluar($integracion)['estado'] === 'rojo';
    }

    private function elegirEstado(bool $penalizado): EstadoEnvio
    {
        $roll = random_int(1, 100);

        if ($penalizado) {
            if ($roll <= 45) {
                return EstadoEnvio::Exitoso;
            }

            if ($roll <= 70) {
                return EstadoEnvio::Parcial;
            }

            return EstadoEnvio::Fallido;
        }

        if ($roll <= 78) {
            return EstadoEnvio::Exitoso;
        }

        if ($roll <= 92) {
            return EstadoEnvio::Parcial;
        }

        return EstadoEnvio::Fallido;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function conteoRegistros(EstadoEnvio $estado, int $total): array
    {
        return match ($estado) {
            EstadoEnvio::Exitoso => [$total, 0],
            EstadoEnvio::Parcial => (static function () use ($total) {
                $minimo = max(1, (int) ceil($total * 0.01));
                $maximo = max($minimo, (int) floor($total * 0.15));

                $error = random_int($minimo, $maximo);

                return [$total - $error, $error];
            })(),
            EstadoEnvio::Fallido => [0, $total],
            EstadoEnvio::EnCurso => [0, 0],
        };
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function detalleHttp(EstadoEnvio $estado): array
    {
        return match ($estado) {
            EstadoEnvio::Exitoso => [200, null],
            EstadoEnvio::Parcial => [
                random_int(0, 1) === 0 ? 422 : 500,
                self::MENSAJES_PARCIAL[array_rand(self::MENSAJES_PARCIAL)],
            ],
            EstadoEnvio::Fallido => $this->detalleFallido(),
            EstadoEnvio::EnCurso => [null, null],
        };
    }

    /**
     * @return array{0: int|null, 1: string}
     */
    private function detalleFallido(): array
    {
        $variante = random_int(1, 3);

        if ($variante === 1) {
            return [null, self::MENSAJES_TIMEOUT[array_rand(self::MENSAJES_TIMEOUT)]];
        }

        $codigo = $variante === 2 ? 500 : 503;

        return [$codigo, self::MENSAJES_FALLIDO_HTTP[array_rand(self::MENSAJES_FALLIDO_HTTP)]];
    }

    private function moverPatentes(Integracion $integracion, Envio $envio, int $ok, int $error): void
    {
        $necesarias = $ok + $error;

        if ($necesarias <= 0) {
            return;
        }

        $pendientes = Patente::query()
            ->where('integracion_id', $integracion->id)
            ->where('estado', EstadoPatente::Pendiente)
            ->orderBy('id')
            ->limit($necesarias)
            ->get();

        $okIds = $pendientes->take($ok)->pluck('id');
        $errorIds = $pendientes->skip($ok)->take($error)->pluck('id');

        if ($okIds->isNotEmpty()) {
            Patente::query()->whereIn('id', $okIds)->update([
                'estado' => EstadoPatente::Enviada,
                'ultimo_envio_id' => $envio->id,
            ]);
        }

        if ($errorIds->isNotEmpty()) {
            Patente::query()->whereIn('id', $errorIds)->update([
                'estado' => EstadoPatente::Rechazada,
                'ultimo_envio_id' => $envio->id,
            ]);
        }
    }
}
