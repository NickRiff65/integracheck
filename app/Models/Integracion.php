<?php

namespace App\Models;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoPatente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Integracion extends Model
{
    protected $table = 'integraciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'sistema_origen',
        'sistema_destino',
        'url_base',
        'frecuencia_minutos',
        'activa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frecuencia_minutos' => 'integer',
            'activa' => 'boolean',
        ];
    }

    public function envios(): HasMany
    {
        return $this->hasMany(Envio::class);
    }

    public function ultimoEnvio(): HasOne
    {
        return $this->hasOne(Envio::class)->latestOfMany('fecha_inicio');
    }

    public function patentes(): HasMany
    {
        return $this->hasMany(Patente::class);
    }

    public function chequeosSalud(): HasMany
    {
        return $this->hasMany(ChequeoSalud::class);
    }

    public function incidentes(): HasMany
    {
        return $this->hasMany(Incidente::class);
    }

    public function conciliaciones(): HasMany
    {
        return $this->hasMany(Conciliacion::class);
    }

    public function ultimaConciliacion(): HasOne
    {
        return $this->hasOne(Conciliacion::class)->latestOfMany('fecha_conciliacion');
    }

    public function disponibilidad30Dias(): float
    {
        $desde = now()->subDays(30);

        $total = $this->envios()
            ->where('fecha_inicio', '>=', $desde)
            ->where('estado', '!=', EstadoEnvio::EnCurso)
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $exitosos = $this->envios()
            ->where('fecha_inicio', '>=', $desde)
            ->where('estado', EstadoEnvio::Exitoso)
            ->count();

        return round(($exitosos / $total) * 100, 1);
    }

    public function frecuenciaHumana(): string
    {
        $minutos = $this->frecuencia_minutos;

        if ($minutos % 1440 === 0) {
            $dias = intdiv($minutos, 1440);

            return $dias === 1 ? 'cada 24 horas' : "cada {$dias} días";
        }

        if ($minutos % 60 === 0) {
            $horas = intdiv($minutos, 60);

            return $horas === 1 ? 'cada 1 hora' : "cada {$horas} horas";
        }

        return "cada {$minutos} minutos";
    }

    /**
     * @return array<string, int>
     */
    public function conteoPatentesPorEstado(): array
    {
        $conteos = $this->patentes()
            ->toBase()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return collect(EstadoPatente::cases())
            ->mapWithKeys(fn (EstadoPatente $estado) => [
                $estado->value => (int) $conteos->get($estado->value, 0),
            ])
            ->all();
    }
}
