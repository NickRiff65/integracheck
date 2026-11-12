<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChequeoSalud extends Model
{
    protected $table = 'chequeos_salud';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'integracion_id',
        'tipo',
        'resultado',
        'latencia_ms',
        'detalle',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resultado' => 'boolean',
            'latencia_ms' => 'integer',
        ];
    }

    public function integracion(): BelongsTo
    {
        return $this->belongsTo(Integracion::class);
    }
}
