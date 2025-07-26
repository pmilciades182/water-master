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
        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate role-permission assignments
            $table->unique(['role_id', 'permission_id']);
            
            // Indexes for better query performance
            $table->index('role_id');
            $table->index('permission_id');
        });
        
        // Assign default permissions to roles
        $this->assignDefaultPermissions();
    }
    
    /**
     * Assign default permissions to each role type
     */
    private function assignDefaultPermissions(): void
    {
        // Define permission sets for each role
        $rolePermissions = [
            'SuperAdmin' => [
                // Full access to all modules and actions
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.export', 'users.manage',
                'products.view', 'products.create', 'products.edit', 'products.delete', 'products.export', 'products.manage',
                'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.export', 'clients.manage',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.export', 'services.manage',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.export', 'invoices.manage',
                'reports.view', 'reports.create', 'reports.edit', 'reports.delete', 'reports.export', 'reports.manage',
                'settings.view', 'settings.create', 'settings.edit', 'settings.delete', 'settings.export', 'settings.manage',
                'meters.read', 'payments.process', 'maintenance.schedule', 'dashboard.view'
            ],
            'TenantAdmin' => [
                // Full access within company context
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.export', 'users.manage',
                'products.view', 'products.create', 'products.edit', 'products.delete', 'products.export', 'products.manage',
                'clients.view', 'clients.create', 'clients.edit', 'clients.delete', 'clients.export', 'clients.manage',
                'services.view', 'services.create', 'services.edit', 'services.delete', 'services.export', 'services.manage',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.export', 'invoices.manage',
                'reports.view', 'reports.create', 'reports.edit', 'reports.export', 'reports.manage',
                'settings.view', 'settings.edit', 'settings.manage',
                'meters.read', 'payments.process', 'maintenance.schedule', 'dashboard.view'
            ],
            'Manager' => [
                // Management level access
                'users.view', 'users.create', 'users.edit',
                'products.view', 'products.create', 'products.edit',
                'clients.view', 'clients.create', 'clients.edit', 'clients.export', 'clients.manage',
                'services.view', 'services.create', 'services.edit', 'services.export', 'services.manage',
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.export', 'invoices.manage',
                'reports.view', 'reports.export', 'reports.manage',
                'settings.view',
                'payments.process', 'maintenance.schedule', 'dashboard.view'
            ],
            'Technician' => [
                // Technical operations access
                'clients.view',
                'services.view', 'services.edit', 'services.manage',
                'invoices.view',
                'reports.view',
                'meters.read', 'maintenance.schedule', 'dashboard.view'
            ],
            'Customer' => [
                // Limited customer access
                'invoices.view',
                'services.view',
                'dashboard.view'
            ]
        ];
        
        // Get all roles and permissions
        $roles = \DB::table('roles')->get();
        $permissions = \DB::table('permissions')->pluck('id', 'name');
        
        $rolePermissionInserts = [];
        
        foreach ($roles as $role) {
            if (isset($rolePermissions[$role->name])) {
                foreach ($rolePermissions[$role->name] as $permissionName) {
                    if (isset($permissions[$permissionName])) {
                        $rolePermissionInserts[] = [
                            'role_id' => $role->id,
                            'permission_id' => $permissions[$permissionName],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }
        
        if (!empty($rolePermissionInserts)) {
            \DB::table('role_permission')->insert($rolePermissionInserts);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permission');
    }
};
