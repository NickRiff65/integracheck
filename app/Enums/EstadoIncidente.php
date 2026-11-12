<?php

namespace App\Enums;

enum EstadoIncidente: string
{
    case Abierto = 'abierto';
    case EnRevision = 'en_revision';
    case Resuelto = 'resuelto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::EnRevision => 'En revisión',
            self::Resuelto => 'Resuelto',
        };
    }
}
