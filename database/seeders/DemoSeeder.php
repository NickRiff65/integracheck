<?php

namespace Database\Seeders;

use App\Enums\EstadoEnvio;
use App\Enums\EstadoIncidente;
use App\Enums\EstadoPatente;
use App\Enums\GravedadIncidente;
use App\Enums\TipoDetalleConciliacion;
use App\Models\ChequeoSalud;
use App\Models\Conciliacion;
use App\Models\ConciliacionDetalle;
use App\Models\Envio;
use App\Models\Incidente;
use App\Models\Integracion;
use App\Models\Patente;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('es_ES');
        $faker->seed(112026);

        $ahora = now();

        $perfiles = $this->crearIntegraciones();
        $enviosPorIntegracion = [];

        foreach ($perfiles as $perfil => $integracion) {
            $enviosPorIntegracion[$integracion->id] = $this->crearEnvios(
                $integracion,
                $perfil,
                $ahora,
                $faker,
            );
        }

        $this->crearPatentes($perfiles, $enviosPorIntegracion, $faker, $ahora);
        $this->crearChequeosSalud($perfiles, $ahora, $faker);
        $this->crearIncidentes($perfiles, $ahora);
        $this->crearConciliaciones($perfiles, $ahora);
    }

    /**
     * @return array<string, Integracion>
     */
    private function crearIntegraciones(): array
    {
        $definiciones = [
            'sana' => [
                'nombre' => 'Patentes → Registro Nacional',
                'sistema_origen' => 'ERP Municipal de Patentes',
                'sistema_destino' => 'Registro Nacional de Vehículos',
                'url_base' => 'https://api.registro-nacional.example/v1/patentes',
                'frecuencia_minutos' => 60,
                'activa' => true,
            ],
            'parciales' => [
                'nombre' => 'Patentes → Tesorería Comunal',
                'sistema_origen' => 'Sistema de Patentes Municipales',
                'sistema_destino' => 'Portal de Tesorería Comunal',
                'url_base' => 'https://tesoreria-comunal.example/api/v2',
                'frecuencia_minutos' => 360,
                'activa' => true,
            ],
            'atrasada' => [
                'nombre' => 'Patentes → Plataforma Interregional',
                'sistema_origen' => 'Sistema de Control Vehicular',
                'sistema_destino' => 'Plataforma Interregional de Patentes',
                'url_base' => 'https://interregional.example/servicios/patentes',
                'frecuencia_minutos' => 1440,
                'activa' => true,
            ],
        ];

        $integraciones = [];

        foreach ($definiciones as $perfil => $datos) {
            $integraciones[$perfil] = Integracion::query()->create($datos);
        }

        return $integraciones;
    }

    /**
     * @return Collection<int, Envio>
     */
    private function crearEnvios(Integracion $integracion, string $perfil, Carbon $ahora, Generator $faker): Collection
    {
        $frecuencia = $integracion->frecuencia_minutos;

        $finSerie = match ($perfil) {
            'sana' => $ahora->copy()->subMinutes(22),
            'parciales' => $ahora->copy()->subHours(2),
            'atrasada' => $ahora->copy()->subDays(3)->subHours(5),
        };

        $inicioSerie = $ahora->copy()->subDays(30);
        $fechas = [];

        for ($cursor = $finSerie->copy(); $cursor->gte($inicioSerie); $cursor->subMinutes($frecuencia)) {
            $fechas[] = $cursor->copy();
        }

        $fechas = array_reverse($fechas);

        $totalEnvios = count($fechas);
        $filas = [];

        foreach ($fechas as $indice => $fechaInicio) {
            $estado = $this->estadoEnvio($perfil, $indice, $totalEnvios, $faker);
            $totalRegistros = $this->totalRegistros($frecuencia, $faker);
            [$registrosOk, $registrosError] = $this->conteoRegistros($estado, $totalRegistros, $faker);
            [$httpCode, $mensajeError] = $this->detalleHttp($estado, $registrosError, $faker);
            $duracion = $this->duracionSegundos($estado, $httpCode, $faker);

            $filas[] = [
                'integracion_id' => $integracion->id,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaInicio->copy()->addSeconds($duracion),
                'estado' => $estado->value,
                'total_registros' => $totalRegistros,
                'registros_ok' => $registrosOk,
                'registros_error' => $registrosError,
                'http_code' => $httpCode,
                'mensaje_error' => $mensajeError,
                'duracion_seg' => $duracion,
                'created_at' => $fechaInicio,
                'updated_at' => $fechaInicio->copy()->addSeconds($duracion),
            ];
        }

        foreach (array_chunk($filas, 100) as $chunk) {
            Envio::query()->insert($chunk);
        }

        return Envio::query()
            ->where('integracion_id', $integracion->id)
            ->orderBy('fecha_inicio')
            ->get();
    }

    /**
     * @param  array<string, Integracion>  $perfiles
     * @param  array<int, Collection<int, Envio>>  $enviosPorIntegracion
     */
    private function crearPatentes(array $perfiles, array $enviosPorIntegracion, Generator $faker, Carbon $ahora): void
    {
        $cupos = [
            'sana' => 130,
            'parciales' => 100,
            'atrasada' => 70,
        ];

        $usadas = [];
        $filas = [];

        foreach ($cupos as $perfil => $cantidad) {
            $integracion = $perfiles[$perfil];
            $envios = $enviosPorIntegracion[$integracion->id];
            $exitosos = $envios->filter(fn (Envio $envio) => $envio->estado === EstadoEnvio::Exitoso)->values();
            $problematicos = $envios
                ->filter(fn (Envio $envio) => in_array($envio->estado, [EstadoEnvio::Parcial, EstadoEnvio::Fallido], true))
                ->values();

            for ($i = 0; $i < $cantidad; $i++) {
                $estado = $this->estadoPatente($perfil, $faker);
                $envio = $this->envioParaPatente($estado, $exitosos, $problematicos);
                $fechaRegistro = $this->fechaRegistro($envio, $ahora, $faker);

                $filas[] = [
                    'numero_patente' => $this->numeroPatenteUnico($usadas),
                    'titular' => $this->titular($faker),
                    'tipo' => $faker->randomElement(['Particular', 'Comercial', 'Taxi', 'Carga', 'Motocicleta', 'Fiscal']),
                    'estado' => $estado->value,
                    'integracion_id' => $integracion->id,
                    'ultimo_envio_id' => $envio?->id,
                    'fecha_registro' => $fechaRegistro->toDateString(),
                    'created_at' => $fechaRegistro,
                    'updated_at' => $envio?->fecha_fin ?? $fechaRegistro,
                ];
            }
        }

        foreach (array_chunk($filas, 100) as $chunk) {
            Patente::query()->insert($chunk);
        }
    }

    /**
     * @param  array<string, Integracion>  $perfiles
     */
    private function crearChequeosSalud(array $perfiles, Carbon $ahora, Generator $faker): void
    {
        $filas = [];

        foreach ($this->definicionChequeos($ahora) as $perfil => $chequeos) {
            $integracion = $perfiles[$perfil];

            foreach ($chequeos as $chequeo) {
                $ok = $chequeo['ok'];
                $tipo = $chequeo['tipo'];
                $cuando = $chequeo['cuando'];

                $filas[] = [
                    'integracion_id' => $integracion->id,
                    'tipo' => $tipo,
                    'resultado' => $ok,
                    'latencia_ms' => $ok
                        ? $this->latenciaOk($perfil, $faker)
                        : ($chequeo['timeout'] ? null : $faker->numberBetween(1800, 4800)),
                    'detalle' => $this->detalleChequeo($ok, $tipo, $chequeo['timeout'] ?? false),
                    'created_at' => $cuando,
                    'updated_at' => $cuando,
                ];
            }
        }

        ChequeoSalud::query()->insert($filas);
    }

    /**
     * @param  array<string, Integracion>  $perfiles
     */
    private function crearIncidentes(array $perfiles, Carbon $ahora): void
    {
        Incidente::query()->create([
            'integracion_id' => $perfiles['atrasada']->id,
            'titulo' => 'Caída del destino interregional',
            'motivo' => 'El último envío falló (HTTP 422).',
            'gravedad' => GravedadIncidente::Roja,
            'estado' => EstadoIncidente::Abierto,
            'fecha_apertura' => $ahora->copy()->subDays(2),
        ]);

        Incidente::query()->create([
            'integracion_id' => $perfiles['parciales']->id,
            'titulo' => 'Errores de validación en Tesorería Comunal',
            'motivo' => 'El último envío fue parcial.',
            'gravedad' => GravedadIncidente::Amarilla,
            'estado' => EstadoIncidente::EnRevision,
            'responsable' => 'Ana Soto',
            'fecha_apertura' => $ahora->copy()->subDay(),
        ]);
    }

    /**
     * @param  array<string, Integracion>  $perfiles
     */
    private function crearConciliaciones(array $perfiles, Carbon $ahora): void
    {
        $saesa = Integracion::query()->create([
            'nombre' => 'Carga de Patentes SAESA',
            'sistema_origen' => 'Excel / plantilla de carga',
            'sistema_destino' => 'Sistema de patentes',
            'url_base' => null,
            'frecuencia_minutos' => 1440,
            'activa' => true,
        ]);

        $inicio = $ahora->copy()->subMinutes(18);
        $envio = Envio::query()->create([
            'integracion_id' => $saesa->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $inicio->copy()->addSeconds(42),
            'estado' => EstadoEnvio::Exitoso,
            'total_registros' => 313,
            'registros_ok' => 313,
            'registros_error' => 0,
            'http_code' => 200,
            'mensaje_error' => null,
            'duracion_seg' => 42,
        ]);

        $cuadra = Conciliacion::query()->create([
            'integracion_id' => $saesa->id,
            'envio_id' => $envio->id,
            'archivo_origen' => 'plantilla_carga_patentes_nicolas (v2).xlsx',
            'destino' => 'patentes_saesa',
            'registros_esperados' => 313,
            'registros_registrados' => 313,
            'registros_nuevos' => 170,
            'registros_existentes' => 143,
            'registros_faltantes' => 0,
            'registros_duplicados' => 0,
            'total_activos' => 1851,
            'fecha_conciliacion' => $ahora,
        ]);

        $detallesCuadra = [
            [TipoDetalleConciliacion::Carga, 'redlich (nuevas)', 170],
            [TipoDetalleConciliacion::Carga, 'listado_control (ya estaban)', 135],
            [TipoDetalleConciliacion::Carga, 'karen (ya estaban)', 8],
            [TipoDetalleConciliacion::TotalActivo, 'Base', 1532],
            [TipoDetalleConciliacion::TotalActivo, 'Karen', 129],
            [TipoDetalleConciliacion::TotalActivo, 'Redlich', 170],
            [TipoDetalleConciliacion::TotalActivo, 'Altas manuales/patente', 20],
        ];

        foreach ($detallesCuadra as [$tipo, $origen, $cantidad]) {
            ConciliacionDetalle::query()->create([
                'conciliacion_id' => $cuadra->id,
                'tipo' => $tipo,
                'origen' => $origen,
                'cantidad' => $cantidad,
            ]);
        }

        $noCuadra = Conciliacion::query()->create([
            'integracion_id' => $perfiles['parciales']->id,
            'envio_id' => null,
            'archivo_origen' => 'carga_tesoreria_octubre.xlsx',
            'destino' => 'padron_comunal',
            'registros_esperados' => 500,
            'registros_registrados' => 492,
            'registros_nuevos' => 410,
            'registros_existentes' => 82,
            'registros_faltantes' => 8,
            'registros_duplicados' => 3,
            'total_activos' => 492,
            'fecha_conciliacion' => $ahora->copy()->subDays(2),
        ]);

        foreach ([
            [TipoDetalleConciliacion::Carga, 'lote_norte', 280],
            [TipoDetalleConciliacion::Carga, 'lote_sur', 220],
            [TipoDetalleConciliacion::TotalActivo, 'Padrón vigente', 492],
        ] as [$tipo, $origen, $cantidad]) {
            ConciliacionDetalle::query()->create([
                'conciliacion_id' => $noCuadra->id,
                'tipo' => $tipo,
                'origen' => $origen,
                'cantidad' => $cantidad,
            ]);
        }
    }

    /**
     * @return array<string, list<array{ok: bool, tipo: string, cuando: Carbon, timeout?: bool}>>
     */
    private function definicionChequeos(Carbon $ahora): array
    {
        $tipos = ['conectividad', 'autenticación', 'disponibilidad'];

        $sana = [];
        for ($i = 11; $i >= 0; $i--) {
            $sana[] = [
                'ok' => true,
                'tipo' => $tipos[$i % 3],
                'cuando' => $ahora->copy()->subMinutes(18 + ($i * 55)),
            ];
        }

        $parciales = [];
        for ($i = 7; $i >= 0; $i--) {
            $parciales[] = [
                'ok' => ! in_array($i, [0, 2], true),
                'tipo' => $tipos[$i % 3],
                'cuando' => $ahora->copy()->subHours($i * 3)->subMinutes(12),
                'timeout' => false,
            ];
        }

        $atrasada = [];
        for ($i = 5; $i >= 0; $i--) {
            $esTimeout = $i <= 2;
            $atrasada[] = [
                'ok' => $i === 5,
                'tipo' => $tipos[$i % 3],
                'cuando' => $ahora->copy()->subHours(4 + ($i * 6)),
                'timeout' => $esTimeout,
            ];
        }

        return [
            'sana' => $sana,
            'parciales' => $parciales,
            'atrasada' => $atrasada,
        ];
    }

    private function estadoEnvio(string $perfil, int $indice, int $total, Generator $faker): EstadoEnvio
    {
        $cola = $total - $indice;

        if ($perfil === 'sana' && $cola <= 24) {
            return EstadoEnvio::Exitoso;
        }

        if ($perfil === 'atrasada' && $cola <= 4) {
            return EstadoEnvio::Fallido;
        }

        $pesos = match ($perfil) {
            'sana' => [
                EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso,
                EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso,
                EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso,
                EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso,
                EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Parcial, EstadoEnvio::Fallido,
            ],
            'parciales' => [EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Parcial, EstadoEnvio::Parcial, EstadoEnvio::Parcial, EstadoEnvio::Parcial, EstadoEnvio::Fallido],
            'atrasada' => [EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Exitoso, EstadoEnvio::Parcial, EstadoEnvio::Fallido, EstadoEnvio::Fallido],
        };

        return $faker->randomElement($pesos);
    }

    private function totalRegistros(int $frecuencia, Generator $faker): int
    {
        return match ($frecuencia) {
            60 => $faker->numberBetween(8, 28),
            360 => $faker->numberBetween(35, 90),
            default => $faker->numberBetween(90, 180),
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function conteoRegistros(EstadoEnvio $estado, int $total, Generator $faker): array
    {
        return match ($estado) {
            EstadoEnvio::Exitoso => [$total, 0],
            EstadoEnvio::Parcial => (static function () use ($total, $faker) {
                $error = $faker->numberBetween(1, max(1, (int) floor($total * 0.22)));
                return [$total - $error, $error];
            })(),
            EstadoEnvio::Fallido => [0, $total],
            EstadoEnvio::EnCurso => [0, 0],
        };
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function detalleHttp(EstadoEnvio $estado, int $registrosError, Generator $faker): array
    {
        return match ($estado) {
            EstadoEnvio::Exitoso => [200, null],
            EstadoEnvio::Parcial => [422, "Validación parcial: {$registrosError} registros rechazados por formato de patente o titular incompleto (HTTP 422)."],
            EstadoEnvio::Fallido => $faker->randomElement([
                [null, 'Tiempo de espera agotado al conectar con el servicio destino (timeout de 30 segundos).'],
                [500, 'Error interno del servidor destino (HTTP 500). No se pudo procesar el lote.'],
                [422, 'El lote completo fue rechazado: esquema de payload inválido (HTTP 422 Unprocessable Entity).'],
            ]),
            EstadoEnvio::EnCurso => [null, null],
        };
    }

    private function duracionSegundos(EstadoEnvio $estado, ?int $httpCode, Generator $faker): int
    {
        if ($estado === EstadoEnvio::Fallido && $httpCode === null) {
            return 30;
        }

        return match ($estado) {
            EstadoEnvio::Exitoso => $faker->numberBetween(4, 18),
            EstadoEnvio::Parcial => $faker->numberBetween(9, 36),
            EstadoEnvio::Fallido => $faker->numberBetween(2, 12),
            EstadoEnvio::EnCurso => $faker->numberBetween(1, 5),
        };
    }

    private function estadoPatente(string $perfil, Generator $faker): EstadoPatente
    {
        $pesos = match ($perfil) {
            'sana' => [
                EstadoPatente::Enviada, EstadoPatente::Enviada, EstadoPatente::Enviada, EstadoPatente::Enviada, EstadoPatente::Enviada,
                EstadoPatente::Registrada, EstadoPatente::Registrada, EstadoPatente::Registrada,
                EstadoPatente::Pendiente,
                EstadoPatente::Rechazada,
            ],
            'parciales' => [
                EstadoPatente::Enviada, EstadoPatente::Enviada, EstadoPatente::Enviada,
                EstadoPatente::Registrada, EstadoPatente::Registrada,
                EstadoPatente::Pendiente, EstadoPatente::Pendiente,
                EstadoPatente::Rechazada, EstadoPatente::Rechazada, EstadoPatente::Rechazada,
            ],
            'atrasada' => [
                EstadoPatente::Enviada, EstadoPatente::Enviada,
                EstadoPatente::Registrada,
                EstadoPatente::Pendiente, EstadoPatente::Pendiente, EstadoPatente::Pendiente, EstadoPatente::Pendiente,
                EstadoPatente::Rechazada, EstadoPatente::Rechazada,
            ],
        };

        return $faker->randomElement($pesos);
    }

    private function envioParaPatente(
        EstadoPatente $estado,
        Collection $exitosos,
        Collection $problematicos,
    ): ?Envio {
        return match ($estado) {
            EstadoPatente::Pendiente => null,
            EstadoPatente::Registrada => $exitosos->isEmpty() ? null : $exitosos->random(),
            EstadoPatente::Enviada => $exitosos->isEmpty() ? null : $exitosos->random(),
            EstadoPatente::Rechazada => $problematicos->isEmpty()
                ? ($exitosos->isEmpty() ? null : $exitosos->random())
                : $problematicos->random(),
        };
    }

    private function fechaRegistro(?Envio $envio, Carbon $ahora, Generator $faker): Carbon
    {
        $hasta = $envio?->fecha_inicio?->copy()->subDay() ?? $ahora->copy()->subDay();
        $desde = $hasta->copy()->subDays(80);

        if ($desde->gte($hasta)) {
            return $hasta;
        }

        return Carbon::instance($faker->dateTimeBetween($desde, $hasta));
    }

    /**
     * @param  array<string, true>  $usadas
     */
    private function numeroPatenteUnico(array &$usadas): string
    {
        $consonantes = 'BCDFGHJKLPRSTVWXYZ';

        do {
            $numero = '';
            for ($i = 0; $i < 4; $i++) {
                $numero .= $consonantes[random_int(0, strlen($consonantes) - 1)];
            }
            $numero .= str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT);
        } while (isset($usadas[$numero]));

        $usadas[$numero] = true;

        return $numero;
    }

    private function titular(Generator $faker): string
    {
        if ($faker->boolean(18)) {
            $giros = ['Transportes', 'Logística', 'Rent a Car', 'Distribuidora', 'Comercial'];

            return $faker->randomElement($giros).' '.$faker->lastName().' SpA';
        }

        return $faker->name();
    }

    private function latenciaOk(string $perfil, Generator $faker): int
    {
        return match ($perfil) {
            'sana' => $faker->numberBetween(42, 130),
            'parciales' => $faker->numberBetween(210, 780),
            default => $faker->numberBetween(400, 1200),
        };
    }

    private function detalleChequeo(bool $ok, string $tipo, bool $timeout): string
    {
        if ($ok) {
            return match ($tipo) {
                'conectividad' => 'Handshake TLS y ping al endpoint destino respondieron correctamente.',
                'autenticación' => 'Token de servicio renovado. Credenciales vigentes.',
                default => 'El endpoint de salud del destino respondió 200 OK.',
            };
        }

        if ($timeout) {
            return 'Timeout al consultar el endpoint de salud: sin respuesta en 30 segundos.';
        }

        return match ($tipo) {
            'autenticación' => 'El destino rechazó la autenticación (HTTP 422): token expirado o payload inválido.',
            'conectividad' => 'Error interno al establecer la conexión con el destino (HTTP 500).',
            default => 'El endpoint de salud devolvió HTTP 500. Servicio destino inestable.',
        };
    }
}
