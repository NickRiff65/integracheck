<?php

namespace App\Enums;

enum EstadoPatente: string
{
    case Registrada = 'registrada';
    case Enviada = 'enviada';
    case Rechazada = 'rechazada';
    case Pendiente = 'pendiente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Registrada => 'Registrada',
            self::Enviada => 'Enviada',
            self::Rechazada => 'Rechazada',
            self::Pendiente => 'Pendiente',
        };
    }

    public function claseBadge(): string
    {
        return match ($this) {
            self::Registrada => 'text-bg-primary',
            self::Enviada => 'text-bg-success',
            self::Rechazada => 'text-bg-danger',
            self::Pendiente => 'text-bg-warning',
        };
    }
}
