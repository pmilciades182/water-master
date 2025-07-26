<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('module', 50);
            $table->string('action', 50);
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['module', 'action']);
            $table->unique(['module', 'action']);
        });
        
        // Insert default permissions for the water utility system
        $this->insertDefaultPermissions();
    }
    
    /**
     * Insert default permissions for the water utility management system
     */
    private function insertDefaultPermissions(): void
    {
        $modules = [
            'users' => 'Gestión de usuarios del sistema',
            'products' => 'Gestión de productos y servicios de agua',
            'clients' => 'Gestión de clientes y abonados',
            'services' => 'Gestión de servicios de agua y alcantarillado',
            'invoices' => 'Gestión de facturación y cobros',
            'reports' => 'Generación de reportes y estadísticas',
            'settings' => 'Configuración del sistema y empresa'
        ];
        
        $actions = [
            'view' => 'Ver/Consultar información',
            'create' => 'Crear nuevos registros',
            'edit' => 'Editar registros existentes',
            'delete' => 'Eliminar registros',
            'export' => 'Exportar datos a diferentes formatos',
            'manage' => 'Gestión completa del módulo'
        ];
        
        $permissions = [];
        
        foreach ($modules as $moduleKey => $moduleDescription) {
            foreach ($actions as $actionKey => $actionDescription) {
                $permissions[] = [
                    'name' => $moduleKey . '.' . $actionKey,
                    'description' => $actionDescription . ' en ' . $moduleDescription,
                    'module' => $moduleKey,
                    'action' => $actionKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // Additional specific permissions for water utility operations
        $specificPermissions = [
            [
                'name' => 'meters.read',
                'description' => 'Realizar lecturas de medidores',
                'module' => 'services',
                'action' => 'read'
            ],
            [
                'name' => 'payments.process',
                'description' => 'Procesar pagos de clientes',
                'module' => 'invoices',
                'action' => 'process'
            ],
            [
                'name' => 'maintenance.schedule',
                'description' => 'Programar mantenimientos',
                'module' => 'services',
                'action' => 'schedule'
            ],
            [
                'name' => 'dashboard.view',
                'description' => 'Ver dashboard principal',
                'module' => 'reports',
                'action' => 'dashboard'
            ]
        ];
        
        foreach ($specificPermissions as $permission) {
            $permissions[] = array_merge($permission, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        \DB::table('permissions')->insert($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
