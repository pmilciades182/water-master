<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'description' => 'Ver dashboard principal', 'module' => 'dashboard'],
            
            // Users
            ['name' => 'users.view', 'description' => 'Ver y listar usuarios', 'module' => 'users'],
            ['name' => 'users.create', 'description' => 'Crear nuevos usuarios', 'module' => 'users'],
            ['name' => 'users.edit', 'description' => 'Editar usuarios existentes', 'module' => 'users'],
            ['name' => 'users.delete', 'description' => 'Eliminar usuarios', 'module' => 'users'],
            ['name' => 'users.manage_roles', 'description' => 'Gestionar roles de usuarios', 'module' => 'users'],
            
            // Roles
            ['name' => 'roles.view', 'description' => 'Ver y listar roles', 'module' => 'roles'],
            ['name' => 'roles.create', 'description' => 'Crear nuevos roles', 'module' => 'roles'],
            ['name' => 'roles.edit', 'description' => 'Editar roles existentes', 'module' => 'roles'],
            ['name' => 'roles.delete', 'description' => 'Eliminar roles', 'module' => 'roles'],
            ['name' => 'roles.manage_permissions', 'description' => 'Gestionar permisos de roles', 'module' => 'roles'],
            ['name' => 'roles.clone', 'description' => 'Clonar roles existentes', 'module' => 'roles'],
            
            // Permissions
            ['name' => 'permissions.view', 'description' => 'Ver y listar permisos', 'module' => 'permissions'],
            ['name' => 'permissions.create', 'description' => 'Crear nuevos permisos', 'module' => 'permissions'],
            ['name' => 'permissions.edit', 'description' => 'Editar permisos existentes', 'module' => 'permissions'],
            ['name' => 'permissions.delete', 'description' => 'Eliminar permisos', 'module' => 'permissions'],
            ['name' => 'permissions.create_crud', 'description' => 'Crear permisos CRUD automáticamente', 'module' => 'permissions'],
            
            // Products
            ['name' => 'products.view', 'description' => 'Ver y listar productos', 'module' => 'products'],
            ['name' => 'products.create', 'description' => 'Crear nuevos productos', 'module' => 'products'],
            ['name' => 'products.edit', 'description' => 'Editar productos existentes', 'module' => 'products'],
            ['name' => 'products.delete', 'description' => 'Eliminar productos', 'module' => 'products'],
            ['name' => 'products.import', 'description' => 'Importar productos desde archivos', 'module' => 'products'],
            ['name' => 'products.export', 'description' => 'Exportar productos a archivos', 'module' => 'products'],
            
            // Product Categories
            ['name' => 'product_categories.view', 'description' => 'Ver y listar categorías de productos', 'module' => 'product_categories'],
            ['name' => 'product_categories.create', 'description' => 'Crear nuevas categorías', 'module' => 'product_categories'],
            ['name' => 'product_categories.edit', 'description' => 'Editar categorías existentes', 'module' => 'product_categories'],
            ['name' => 'product_categories.delete', 'description' => 'Eliminar categorías', 'module' => 'product_categories'],
            
            // Settings
            ['name' => 'settings.view', 'description' => 'Ver configuraciones del sistema', 'module' => 'settings'],
            ['name' => 'settings.edit', 'description' => 'Editar configuraciones del sistema', 'module' => 'settings'],
            
            // Reports
            ['name' => 'reports.view', 'description' => 'Ver reportes del sistema', 'module' => 'reports'],
            ['name' => 'reports.export', 'description' => 'Exportar reportes', 'module' => 'reports'],
            
            // Company
            ['name' => 'company.view', 'description' => 'Ver información de la empresa', 'module' => 'company'],
            ['name' => 'company.edit', 'description' => 'Editar información de la empresa', 'module' => 'company'],
            
            // Future modules
            ['name' => 'clients.view', 'description' => 'Ver y listar clientes', 'module' => 'clients'],
            ['name' => 'clients.create', 'description' => 'Crear nuevos clientes', 'module' => 'clients'],
            ['name' => 'clients.edit', 'description' => 'Editar clientes existentes', 'module' => 'clients'],
            ['name' => 'clients.delete', 'description' => 'Eliminar clientes', 'module' => 'clients'],
            
            ['name' => 'services.view', 'description' => 'Ver y listar servicios', 'module' => 'services'],
            ['name' => 'services.create', 'description' => 'Crear nuevos servicios', 'module' => 'services'],
            ['name' => 'services.edit', 'description' => 'Editar servicios existentes', 'module' => 'services'],
            ['name' => 'services.delete', 'description' => 'Eliminar servicios', 'module' => 'services'],
            ['name' => 'services.assign', 'description' => 'Asignar servicios a técnicos', 'module' => 'services'],
            
            ['name' => 'invoices.view', 'description' => 'Ver y listar facturas', 'module' => 'invoices'],
            ['name' => 'invoices.create', 'description' => 'Crear nuevas facturas', 'module' => 'invoices'],
            ['name' => 'invoices.edit', 'description' => 'Editar facturas existentes', 'module' => 'invoices'],
            ['name' => 'invoices.delete', 'description' => 'Eliminar facturas', 'module' => 'invoices'],
            ['name' => 'invoices.print', 'description' => 'Imprimir facturas', 'module' => 'invoices'],
            ['name' => 'invoices.send', 'description' => 'Enviar facturas por email', 'module' => 'invoices'],
        ];

        foreach ($permissions as $permission) {
            // Extract action from permission name (e.g., 'users.create' -> 'create')
            $action = explode('.', $permission['name'])[1] ?? 'view';
            
            Permission::firstOrCreate([
                'name' => $permission['name']
            ], [
                'description' => $permission['description'],
                'module' => $permission['module'],
                'action' => $action
            ]);
        }
    }
}