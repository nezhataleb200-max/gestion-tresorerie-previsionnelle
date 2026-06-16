<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KRS Tresorerie') }} - @yield('title')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                KRS Trésorerie
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('clients.index') }}">Clients</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('factures.index') }}">Factures</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('charges.index') }}">Charges</a>
                        </li>
                        <!-- ===== LIEN IMPORT EXCEL ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('import.index') }}">
                                <i class="bi bi-file-earmark-excel me-1"></i> Import Excel
                            </a>
                        </li>
                        <!-- ===== LIEN PLAN TRÉSORERIE ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('plan.index') }}">
                                <i class="bi bi-graph-up me-1"></i> Plan trésorerie
                            </a>
                        </li>
                        <!-- ===== LIEN ALERTES ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('alertes.index') }}">
                                <i class="bi bi-bell me-1"></i> Alertes
                            </a>
                        </li>
                        <!-- ===== LIEN PRÉVISIONNEL ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('previsionnel.index') }}">
                                <i class="bi bi-calendar2-check me-1"></i> Prévisionnel
                            </a>
                        </li>
                        <!-- ===== LIEN DÉCISIONS AUTO ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('decisions.index') }}">
                                <i class="bi bi-lightning-charge me-1"></i> Décisions auto
                            </a>
                        </li>
                        <!-- ============================== -->
                        <!-- ===== LIEN SIMULATION ===== -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('simulation.index') }}">
                                <i class="bi bi-sliders me-1"></i> Simulation
                            </a>
                        </li>
                        <!-- ============================ -->
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="btn btn-link nav-link" type="submit">Déconnexion</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">Inscription</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <header class="bg-white shadow-sm border-bottom">
        <div class="container py-3">
            <h1 class="h3 mb-0">@yield('page-title', 'Dashboard')</h1>
        </div>
    </header>

    <!-- Page Content -->
    <main class="py-4">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @yield('content')
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>