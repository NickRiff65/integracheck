<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · IntegraCheck</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ic-bg: #f1f5f9;
            --ic-night: #0f2040;
            --ic-blue: #2563eb;
            --ic-blue-deep: #1d4ed8;
            --ic-verde: #16a34a;
            --ic-amarillo: #f59e0b;
            --ic-rojo: #dc2626;
            --ic-text: #0f172a;
            --ic-muted: #64748b;
            --ic-radius: 16px;
        }

        body {
            font-family: Inter, "Segoe UI", sans-serif;
            background: var(--ic-bg);
            color: var(--ic-text);
            min-height: 100vh;
        }

        .navbar-ic {
            background: linear-gradient(105deg, #0f2040 0%, #163a7a 55%, #1d4ed8 100%);
            box-shadow: 0 8px 24px rgba(15, 32, 64, .22);
        }

        .navbar-ic .navbar-brand {
            color: #fff;
            font-weight: 800;
            letter-spacing: -.02em;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .brand-mark {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .14);
            font-size: 1.1rem;
        }

        .navbar-ic .nav-link {
            color: rgba(255, 255, 255, .78);
            font-weight: 600;
            border-radius: 999px;
            padding: .4rem .9rem;
        }

        .navbar-ic .nav-link:hover,
        .navbar-ic .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .12);
        }

        .page-header h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .page-header p,
        .flujo {
            color: var(--ic-muted);
            margin-bottom: 0;
        }

        .section-title {
            font-size: .95rem;
            font-weight: 700;
            color: var(--ic-text);
        }

        .card-ic,
        .card-soft,
        .card-kpi,
        .card-integracion {
            background: #fff;
            border: 0;
            border-radius: var(--ic-radius);
            box-shadow: 0 8px 24px rgba(15, 32, 64, .06);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .card-ic:hover,
        .card-soft:hover,
        .card-kpi:hover,
        .card-integracion:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(15, 32, 64, .1);
        }

        .card-kpi .card-body {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .7rem;
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            color: #fff;
        }

        .kpi-azul { background: var(--ic-blue); }
        .kpi-verde { background: var(--ic-verde); }
        .kpi-amarillo { background: var(--ic-amarillo); }
        .kpi-rojo { background: var(--ic-rojo); }
        .kpi-noche { background: var(--ic-night); }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.04em;
            color: var(--ic-text);
        }

        .kpi-label {
            color: var(--ic-muted);
            font-size: .88rem;
            font-weight: 500;
        }

        .card-integracion {
            border-left: 5px solid #cbd5e1;
            height: 100%;
        }

        .card-integracion.salud-verde { border-left-color: var(--ic-verde); }
        .card-integracion.salud-amarillo { border-left-color: var(--ic-amarillo); }
        .card-integracion.salud-rojo { border-left-color: var(--ic-rojo); }

        .stat-mini {
            background: #f8fafc;
            border-radius: 12px;
            padding: .7rem .75rem;
        }

        .stat-mini .valor {
            font-weight: 800;
            font-size: 1.1rem;
        }

        .stat-mini .etiqueta {
            color: var(--ic-muted);
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .stat-mini .valor.verde { color: var(--ic-verde); }
        .stat-mini .valor.amarillo { color: var(--ic-amarillo); }
        .stat-mini .valor.rojo { color: var(--ic-rojo); }

        .badge-salud {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            letter-spacing: .01em;
            padding: .4rem .8rem;
            border-radius: 999px;
            font-size: .8rem;
        }

        .badge-salud .punto {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-salud.verde { background: #dcfce7; color: #166534; }
        .badge-salud.verde .punto { background: var(--ic-verde); }
        .badge-salud.amarillo { background: #fef3c7; color: #92400e; }
        .badge-salud.amarillo .punto { background: var(--ic-amarillo); }
        .badge-salud.rojo { background: #fee2e2; color: #991b1b; }
        .badge-salud.rojo .punto { background: var(--ic-rojo); }

        .badge-estado {
            border-radius: 999px;
            font-weight: 600;
            padding: .35em .75em;
            font-size: .75rem;
        }

        .badge-estado.exitoso,
        .badge-estado.enviada,
        .badge-estado.ok { background: #dcfce7; color: #166534; }
        .badge-estado.parcial,
        .badge-estado.pendiente { background: #fef3c7; color: #92400e; }
        .badge-estado.fallido,
        .badge-estado.rechazada,
        .badge-estado.fail { background: #fee2e2; color: #991b1b; }
        .badge-estado.en_curso,
        .badge-estado.registrada { background: #dbeafe; color: #1d4ed8; }

        .chart-mini {
            height: 140px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f1f5f9;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--ic-muted);
            font-weight: 700;
            border-bottom: 0;
            padding: .85rem .75rem;
        }

        .table tbody td {
            padding: .85rem .75rem;
            border-color: #eef2f7;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border-color: #e2e8f0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .15);
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--ic-blue);
            border-color: var(--ic-blue);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--ic-blue-deep);
            border-color: var(--ic-blue-deep);
        }

        .btn-outline-primary {
            color: var(--ic-blue);
            border-color: #bfdbfe;
        }

        .btn-outline-primary:hover {
            background: var(--ic-blue);
            border-color: var(--ic-blue);
        }

        a {
            color: var(--ic-blue);
        }

        .pagination {
            --bs-pagination-border-radius: 10px;
            --bs-pagination-color: var(--ic-night);
            --bs-pagination-hover-color: var(--ic-blue);
            --bs-pagination-active-bg: var(--ic-blue);
            --bs-pagination-active-border-color: var(--ic-blue);
        }

        .footer-ic {
            color: var(--ic-muted);
            font-size: .85rem;
        }

        dl dt {
            font-weight: 600;
        }

        .banner-ic {
            border: 0;
            border-radius: var(--ic-radius);
            padding: 1rem 1.15rem;
            box-shadow: 0 8px 24px rgba(15, 32, 64, .06);
        }

        .banner-ic.ok {
            background: #dcfce7;
            color: #166534;
        }

        .banner-ic.alerta {
            background: #fff7ed;
            color: #9a3412;
            border-left: 5px solid var(--ic-amarillo);
        }

        .banner-ic.alerta.critica {
            background: #fef2f2;
            color: #991b1b;
            border-left-color: var(--ic-rojo);
        }

        .banner-ic a {
            font-weight: 600;
        }

        .disp-verde { color: var(--ic-verde); }
        .disp-amarillo { color: var(--ic-amarillo); }
        .disp-rojo { color: var(--ic-rojo); }

        .flash-exito {
            background: #dcfce7;
            color: #166534;
            border: 0;
            border-radius: var(--ic-radius);
            padding: .9rem 1.1rem;
            box-shadow: 0 8px 24px rgba(15, 32, 64, .06);
        }

        .nav-link .nav-badge {
            background: #dc2626;
            color: #fff;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            padding: .12rem .45rem;
            margin-left: .25rem;
            vertical-align: middle;
        }

        .badge-estado.abierto { background: #fee2e2; color: #991b1b; }
        .badge-estado.en_revision { background: #fef3c7; color: #92400e; }
        .badge-estado.resuelto { background: #dcfce7; color: #166534; }
        .badge-estado.amarilla { background: #fef3c7; color: #92400e; }
        .badge-estado.roja { background: #fee2e2; color: #991b1b; }
        .badge-estado.cuadra { background: #dcfce7; color: #166534; }
        .badge-estado.diferencia_menor { background: #fef3c7; color: #92400e; }
        .badge-estado.no_cuadra { background: #fee2e2; color: #991b1b; }

        .cifra-cruce {
            font-size: 2.35rem;
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1;
        }

        .cifra-cruce.cuadra { color: var(--ic-verde); }
        .cifra-cruce.diferencia_menor { color: var(--ic-amarillo); }
        .cifra-cruce.no_cuadra { color: var(--ic-rojo); }

        .flash-error {
            background: #fee2e2;
            color: #991b1b;
            border: 0;
            border-radius: var(--ic-radius);
            padding: .9rem 1.1rem;
            box-shadow: 0 8px 24px rgba(15, 32, 64, .06);
        }

        .chart-informe {
            height: 280px;
        }

        @media print {
            .navbar-ic,
            .footer-ic,
            .no-print,
            .flash-exito {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .card-ic:hover,
            .card-kpi:hover,
            .card-soft:hover,
            .card-integracion:hover {
                transform: none;
                box-shadow: none;
            }

            .card-ic,
            .card-kpi {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-ic">
        <div class="container">
            <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <span class="brand-mark"><i class="bi bi-hdd-network"></i></span>
                IntegraCheck
            </a>
            <div class="collapse navbar-collapse" id="menuPrincipal">
                <ul class="navbar-nav ms-auto gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('envios.*') ? 'active' : '' }}" href="{{ route('envios.index') }}">
                            <i class="bi bi-arrow-left-right me-1"></i> Envíos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('incidentes.*') ? 'active' : '' }}" href="{{ route('incidentes.index') }}">
                            <i class="bi bi-exclamation-octagon me-1"></i> Incidentes
                            @if (($incidentesAbiertos ?? 0) > 0)
                                <span class="nav-badge">{{ $incidentesAbiertos }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('conciliaciones.*') ? 'active' : '' }}" href="{{ route('conciliaciones.index') }}">
                            <i class="bi bi-clipboard-check me-1"></i> Conciliaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('informes.*') ? 'active' : '' }}" href="{{ route('informes.index') }}">
                            <i class="bi bi-bar-chart me-1"></i> Informes
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @if (session('exito'))
            <div class="flash-exito mb-4" role="alert">
                <i class="bi bi-check-circle me-1"></i> {{ session('exito') }}
            </div>
        @endif
        @yield('content')
    </main>

    <footer class="container pb-4 footer-ic">
        Maqueta de demostración para Chile · datos ficticios · sin conexiones reales a APIs
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
