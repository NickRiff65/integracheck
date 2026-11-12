<?php

namespace App\Models;

use App\Enums\EstadoIncidente;
use App\Enums\GravedadIncidente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidente extends Model
{
    protected $table = 'incidentes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'integracion_id',
        'titulo',
        'motivo',
        'gravedad',
        'estado',
        'responsable',
        'comentario_resolucion',
        'fecha_apertura',
        'fecha_resolucion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gravedad' => GravedadIncidente::class,
            'estado' => EstadoIncidente::class,
            'fecha_apertura' => 'datetime',
            'fecha_resolucion' => 'datetime',
        ];
    }

    public function integracion(): BelongsTo
    {
        return $this->belongsTo(Integracion::class);
    }

    public function estaAbierto(): bool
    {
        return in_array($this->estado, [
            EstadoIncidente::Abierto,
            EstadoIncidente::EnRevision,
        ], true);
    }
}
