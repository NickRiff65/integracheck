<?php

namespace App\Models;

use App\Enums\TipoDetalleConciliacion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Conciliacion extends Model
{
    protected $table = 'conciliaciones';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'integracion_id',
        'envio_id',
        'archivo_origen',
        'destino',
        'registros_esperados',
        'registros_registrados',
        'registros_nuevos',
        'registros_existentes',
        'registros_faltantes',
        'registros_duplicados',
        'total_activos',
        'fecha_conciliacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registros_esperados' => 'integer',
            'registros_registrados' => 'integer',
            'registros_nuevos' => 'integer',
            'registros_existentes' => 'integer',
            'registros_faltantes' => 'integer',
            'registros_duplicados' => 'integer',
            'total_activos' => 'integer',
            'fecha_conciliacion' => 'datetime',
        ];
    }

    public function integracion(): BelongsTo
    {
        return $this->belongsTo(Integracion::class);
    }

    public function envio(): BelongsTo
    {
        return $this->belongsTo(Envio::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ConciliacionDetalle::class);
    }

    public function diferencia(): int
    {
        return $this->registros_esperados - $this->registros_registrados;
    }

    public function estado(): string
    {
        if ($this->diferencia() === 0 && $this->registros_faltantes === 0 && $this->registros_duplicados === 0) {
            return 'cuadra';
        }

        $umbral = $this->registros_esperados * 0.01;

        if ($this->registros_faltantes <= $umbral && $this->registros_duplicados <= $umbral) {
            return 'diferencia_menor';
        }

        return 'no_cuadra';
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado()) {
            'cuadra' => 'Cuadra',
            'diferencia_menor' => 'Diferencia menor',
            default => 'No cuadra',
        };
    }

    public function fraseResumen(): string
    {
        if ($this->estado() === 'cuadra') {
            return 'El cruce contra el destino cuadra bien: están registradas todas las patentes de la fuente, no falta ninguna.';
        }

        $detalle = $this->fraseDiferencias();

        if ($this->estado() === 'diferencia_menor') {
            return "El cruce muestra una diferencia menor: {$detalle}.";
        }

        return "El cruce no cuadra: {$detalle}.";
    }

    /**
     * @return Collection<int, ConciliacionDetalle>
     */
    public function detallesCarga(): Collection
    {
        return $this->detalles
            ->where('tipo', TipoDetalleConciliacion::Carga)
            ->values();
    }

    /**
     * @return Collection<int, ConciliacionDetalle>
     */
    public function detallesTotalActivo(): Collection
    {
        return $this->detalles
            ->where('tipo', TipoDetalleConciliacion::TotalActivo)
            ->values();
    }

    private function fraseDiferencias(): string
    {
        $partes = [];
        $diferencia = $this->diferencia();

        if ($diferencia > 0) {
            $partes[] = 'faltan '.$this->cantidadTexto($diferencia).' respecto de la fuente';
        } elseif ($diferencia < 0) {
            $partes[] = 'sobran '.$this->cantidadTexto(abs($diferencia)).' respecto de la fuente';
        }

        if ($this->registros_faltantes > 0) {
            $partes[] = $this->cantidadTexto($this->registros_faltantes).' de la fuente no aparecen en el destino';
        }

        if ($this->registros_duplicados > 0) {
            $partes[] = 'hay '.$this->cantidadTexto($this->registros_duplicados).' duplicada'.($this->registros_duplicados === 1 ? '' : 's');
        }

        if ($partes === []) {
            return 'los totales no coinciden con el desglose esperado';
        }

        return implode(', ', $partes);
    }

    private function cantidadTexto(int $cantidad): string
    {
        $numero = number_format($cantidad, 0, ',', '.');

        return $cantidad === 1 ? "{$numero} patente" : "{$numero} patentes";
    }
}
