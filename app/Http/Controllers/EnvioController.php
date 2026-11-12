<?php

namespace App\Http\Controllers;

use App\Enums\EstadoEnvio;
use App\Models\Envio;
use App\Models\Integracion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EnvioController extends Controller
{
    public function index(Request $request): View
    {
        $envios = Envio::query()
            ->with(['integracion', 'conciliacion'])
            ->when($request->integer('integracion_id'), fn ($query, $id) => $query->where('integracion_id', $id))
            ->when($request->filled('estado'), function ($query) use ($request) {
                $estado = EstadoEnvio::tryFrom((string) $request->string('estado'));

                if ($estado !== null) {
                    $query->where('estado', $estado);
                }
            })
            ->latest('fecha_inicio')
            ->paginate(20)
            ->withQueryString();

        return view('envios.index', [
            'envios' => $envios,
            'integraciones' => Integracion::query()->orderBy('nombre')->get(['id', 'nombre']),
            'estados' => EstadoEnvio::cases(),
            'filtros' => [
                'integracion_id' => $request->integer('integracion_id') ?: null,
                'estado' => $request->string('estado')->toString() ?: null,
            ],
        ]);
    }

    public function show(Envio $envio): View
    {
        $envio->load(['integracion', 'conciliacion']);

        return view('envios.show', [
            'envio' => $envio,
        ]);
    }
}
