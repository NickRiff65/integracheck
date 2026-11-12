<?php

namespace App\Enums;

enum GravedadIncidente: string
{
    case Amarilla = 'amarilla';
    case Roja = 'roja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Amarilla => 'Amarilla',
            self::Roja => 'Roja',
        };
    }
}
