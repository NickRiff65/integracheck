<?php

namespace App\Enums;

enum TipoDetalleConciliacion: string
{
    case Carga = 'carga';
    case TotalActivo = 'total_activo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Carga => 'Carga',
            self::TotalActivo => 'Total activo',
        };
    }
}
