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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint for role name per company (multi-tenant)
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
        
        // Insert default roles for all existing companies
        $this->insertDefaultRoles();
    }
    
    /**
     * Insert default roles for the water utility system
     */
    private function insertDefaultRoles(): void
    {
        $defaultRoles = [
            [
                'name' => 'SuperAdmin',
                'description' => 'Administrador del sistema con acceso completo a todas las funcionalidades'
            ],
            [
                'name' => 'TenantAdmin',
                'description' => 'Administrador de la empresa con acceso completo dentro de su organización'
            ],
            [
                'name' => 'Manager',
                'description' => 'Gerente con acceso a reportes y gestión de clientes y servicios'
            ],
            [
                'name' => 'Technician',
                'description' => 'Técnico con acceso a servicios, lecturas de medidores y mantenimiento'
            ],
            [
                'name' => 'Customer',
                'description' => 'Cliente con acceso limitado a su información y facturas'
            ]
        ];
        
        // Get all companies to create roles for each
        $companies = \DB::table('companies')->get();
        
        foreach ($companies as $company) {
            foreach ($defaultRoles as $role) {
                \DB::table('roles')->insert([
                    'company_id' => $company->id,
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
