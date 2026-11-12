<?php

namespace App\Models;

use App\Enums\EstadoEnvio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Envio extends Model
{
    protected $table = 'envios';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'integracion_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'total_registros',
        'registros_ok',
        'registros_error',
        'http_code',
        'mensaje_error',
        'duracion_seg',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'estado' => EstadoEnvio::class,
            'total_registros' => 'integer',
            'registros_ok' => 'integer',
            'registros_error' => 'integer',
            'http_code' => 'integer',
            'duracion_seg' => 'integer',
        ];
    }

    public function integracion(): BelongsTo
    {
        return $this->belongsTo(Integracion::class);
    }

    public function patentes(): HasMany
    {
        return $this->hasMany(Patente::class, 'ultimo_envio_id');
    }

    public function conciliacion(): HasOne
    {
        return $this->hasOne(Conciliacion::class);
    }
}
