<?php

namespace App\Http\Controllers;

use App\Enums\EstadoIncidente;
use App\Enums\GravedadIncidente;
use App\Models\Incidente;
use App\Models\Integracion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidenteController extends Controller
{
    public function index(Request $request): View
    {
        $estado = EstadoIncidente::tryFrom((string) $request->string('estado'));
        $gravedad = GravedadIncidente::tryFrom((string) $request->string('gravedad'));
        $integracionId = $request->integer('integracion_id') ?: null;

        $incidentes = Incidente::query()
            ->with('integracion')
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->when($gravedad, fn ($query) => $query->where('gravedad', $gravedad))
            ->when($integracionId, fn ($query) => $query->where('integracion_id', $integracionId))
            ->latest('fecha_apertura')
            ->paginate(15)
            ->withQueryString();

        $conteos = Incidente::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return view('incidentes.index', [
            'incidentes' => $incidentes,
            'integraciones' => Integracion::query()->orderBy('nombre')->get(),
            'estados' => EstadoIncidente::cases(),
            'gravedades' => GravedadIncidente::cases(),
            'conteos' => [
                'abierto' => (int) $conteos->get(EstadoIncidente::Abierto->value, 0),
                'en_revision' => (int) $conteos->get(EstadoIncidente::EnRevision->value, 0),
                'resuelto' => (int) $conteos->get(EstadoIncidente::Resuelto->value, 0),
            ],
            'filtros' => [
                'estado' => $estado?->value,
                'gravedad' => $gravedad?->value,
                'integracion_id' => $integracionId,
            ],
        ]);
    }

    public function show(Incidente $incidente): View
    {
        $incidente->load('integracion');

        return view('incidentes.show', [
            'incidente' => $incidente,
            'estados' => EstadoIncidente::cases(),
        ]);
    }

    public function update(Request $request, Incidente $incidente): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoIncidente::class)],
            'responsable' => ['nullable', 'string', 'max:255'],
            'comentario_resolucion' => ['nullable', 'string'],
        ]);

        $estado = EstadoIncidente::from($datos['estado']);
        $incidente->estado = $estado;
        $incidente->responsable = filled($datos['responsable'] ?? null) ? trim($datos['responsable']) : null;
        $incidente->comentario_resolucion = filled($datos['comentario_resolucion'] ?? null)
            ? trim($datos['comentario_resolucion'])
            : null;

        if ($estado === EstadoIncidente::Resuelto) {
            $incidente->fecha_resolucion = now();
        }

        $incidente->save();

        return back()->with('exito', 'Incidente actualizado.');
    }
}
