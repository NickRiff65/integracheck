<?php

namespace App\Models;

use App\Enums\EstadoPatente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patente extends Model
{
    protected $table = 'patentes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'numero_patente',
        'titular',
        'tipo',
        'estado',
        'integracion_id',
        'ultimo_envio_id',
        'fecha_registro',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoPatente::class,
            'fecha_registro' => 'date',
        ];
    }

    public function integracion(): BelongsTo
    {
        return $this->belongsTo(Integracion::class);
    }

    public function ultimoEnvio(): BelongsTo
    {
        return $this->belongsTo(Envio::class, 'ultimo_envio_id');
    }
}
