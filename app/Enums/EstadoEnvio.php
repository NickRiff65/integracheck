<?php

namespace App\Enums;

enum EstadoEnvio: string
{
    case Exitoso = 'exitoso';
    case Parcial = 'parcial';
    case Fallido = 'fallido';
    case EnCurso = 'en_curso';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Exitoso => 'Exitoso',
            self::Parcial => 'Parcial',
            self::Fallido => 'Fallido',
            self::EnCurso => 'En curso',
        };
    }

    public function claseBadge(): string
    {
        return match ($this) {
            self::Exitoso => 'text-bg-success',
            self::Parcial => 'text-bg-warning',
            self::Fallido => 'text-bg-danger',
            self::EnCurso => 'text-bg-secondary',
        };
    }
}
