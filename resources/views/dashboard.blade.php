<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Water Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-tint text-white"></i>
                            </div>
                            <span class="ml-2 text-xl font-semibold text-gray-900 hidden sm:block">Water Master</span>
                        </div>
                        
                        <!-- Company info -->
                        <div class="ml-6 border-l border-gray-200 pl-6 hidden md:block">
                            <p class="text-sm font-medium text-gray-900">{{ auth()->user()->company->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->user()->company->subdomain }}.watermaster.com</p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <!-- User menu -->
                        <div class="relative">
                            <div class="flex items-center space-x-3">
                                @if(auth()->user()->avatar_url)
                                    <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}">
                                @else
                                    <div class="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-600 text-sm"></i>
                                    </div>
                                @endif
                                <div class="hidden md:block">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Welcome message -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">
                        ¡Bienvenido, {{ auth()->user()->name }}!
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Panel de control de {{ auth()->user()->company->name }}
                    </p>
                </div>

                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-300 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    {{ session('success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Stats overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users text-2xl text-blue-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Clientes
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            0
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clipboard-list text-2xl text-green-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Servicios
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            0
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-file-invoice text-2xl text-yellow-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Facturas
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            0
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-boxes text-2xl text-red-500"></i>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Productos
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900">
                                            0
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Acciones rápidas
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <button class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-user-plus text-2xl text-blue-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900">Nuevo Cliente</span>
                            </button>
                            
                            <button class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-tools text-2xl text-green-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900">Nuevo Servicio</span>
                            </button>
                            
                            <button class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-file-invoice-dollar text-2xl text-yellow-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900">Nueva Factura</span>
                            </button>
                            
                            <button class="flex flex-col items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-box text-2xl text-red-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900">Nuevo Producto</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Trial info -->
                @if(auth()->user()->company->isOnTrial())
                    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Tu período de prueba expira el {{ auth()->user()->company->trial_ends_at->format('d/m/Y') }}.
                                    <a href="#" class="font-medium underline hover:text-blue-600">
                                        Configura tu suscripción aquí
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>

    <div id="app"></div>
</body>
</html>