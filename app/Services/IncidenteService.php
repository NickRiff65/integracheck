<?php

namespace App\Services;

use App\Enums\EstadoIncidente;
use App\Enums\GravedadIncidente;
use App\Models\Incidente;
use App\Models\Integracion;

class IncidenteService
{
    public function __construct(
        private readonly HealthStatusService $healthStatusService,
    ) {}

    public function revisar(Integracion $integracion): ?Incidente
    {
        $integracion->loadMissing('ultimoEnvio');
        $salud = $this->healthStatusService->evaluar($integracion);

        if (! in_array($salud['estado'], ['amarillo', 'rojo'], true)) {
            return null;
        }

        $tieneAbierto = Incidente::query()
            ->where('integracion_id', $integracion->id)
            ->whereIn('estado', [
                EstadoIncidente::Abierto,
                EstadoIncidente::EnRevision,
            ])
            ->exists();

        if ($tieneAbierto) {
            return null;
        }

        $gravedad = $salud['estado'] === 'rojo'
            ? GravedadIncidente::Roja
            : GravedadIncidente::Amarilla;

        $titulo = $gravedad === GravedadIncidente::Roja
            ? "Incidente crítico: {$integracion->nombre}"
            : "Alerta: {$integracion->nombre}";

        return Incidente::query()->create([
            'integracion_id' => $integracion->id,
            'titulo' => $titulo,
            'motivo' => $salud['motivo'],
            'gravedad' => $gravedad,
            'estado' => EstadoIncidente::Abierto,
            'fecha_apertura' => now(),
        ]);
    }
}
