<?php

namespace App\Services;

use App\Enums\EstadoEnvio;
use App\Models\Envio;
use App\Models\Integracion;

class HealthStatusService
{
    /**
     * @return array{estado: 'verde'|'amarillo'|'rojo', motivo: string}
     */
    public function evaluar(Integracion $integracion): array
    {
        $ultimo = $integracion->ultimoEnvio;

        if ($ultimo === null) {
            return [
                'estado' => 'rojo',
                'motivo' => 'No hay envíos registrados.',
            ];
        }

        $frecuencia = max(1, $integracion->frecuencia_minutos);
        $atrasoMinutos = $this->atrasoMinutos($ultimo);
        $tasaError = $this->tasaErrorPorcentaje($ultimo);

        if ($ultimo->estado === EstadoEnvio::Fallido) {
            return [
                'estado' => 'rojo',
                'motivo' => $this->motivoFallido($ultimo),
            ];
        }

        if ($atrasoMinutos > ($frecuencia * 2)) {
            return [
                'estado' => 'rojo',
                'motivo' => "Atraso de {$atrasoMinutos} min, mayor al doble de la frecuencia ({$frecuencia} min).",
            ];
        }

        if ($tasaError > 10) {
            return [
                'estado' => 'rojo',
                'motivo' => "La tasa de error del último envío es {$tasaError}%.",
            ];
        }

        if ($ultimo->estado === EstadoEnvio::Parcial) {
            return [
                'estado' => 'amarillo',
                'motivo' => 'El último envío fue parcial.',
            ];
        }

        if ($atrasoMinutos > $frecuencia) {
            return [
                'estado' => 'amarillo',
                'motivo' => "Atraso de {$atrasoMinutos} min, superior a la frecuencia de {$frecuencia} min.",
            ];
        }

        if ($tasaError >= 2) {
            return [
                'estado' => 'amarillo',
                'motivo' => "La tasa de error del último envío es {$tasaError}%.",
            ];
        }

        return [
            'estado' => 'verde',
            'motivo' => 'Último envío exitoso y dentro de la frecuencia programada.',
        ];
    }

    private function atrasoMinutos(Envio $envio): int
    {
        $referencia = $envio->fecha_fin ?? $envio->fecha_inicio;

        return (int) round($referencia->diffInMinutes(now(), true));
    }

    private function tasaErrorPorcentaje(Envio $envio): float
    {
        if ($envio->total_registros <= 0) {
            return 0.0;
        }

        return round(($envio->registros_error / $envio->total_registros) * 100, 2);
    }

    private function motivoFallido(Envio $envio): string
    {
        if ($envio->http_code === null) {
            return 'El último envío falló por timeout.';
        }

        return "El último envío falló (HTTP {$envio->http_code}).";
    }
}
