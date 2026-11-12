<?php

namespace App\Console\Commands;

use App\Services\SimuladorEnviosService;
use Illuminate\Console\Command;

class SimularEnviosCommand extends Command
{
    protected $signature = 'simular:envios';

    protected $description = 'Genera un envío simulado por cada integración activa';

    public function handle(SimuladorEnviosService $simulador): int
    {
        $cantidad = $simulador->ejecutar();

        $this->info("Simulación ejecutada: se generaron {$cantidad} envíos nuevos");

        return self::SUCCESS;
    }
}
