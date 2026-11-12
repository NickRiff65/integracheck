<?php

namespace App\Models;

use App\Enums\TipoDetalleConciliacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConciliacionDetalle extends Model
{
    protected $table = 'conciliacion_detalles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'conciliacion_id',
        'tipo',
        'origen',
        'cantidad',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoDetalleConciliacion::class,
            'cantidad' => 'integer',
        ];
    }

    public function conciliacion(): BelongsTo
    {
        return $this->belongsTo(Conciliacion::class);
    }
}
