<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('tax_id')->nullable(); // RUC para Paraguay
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable(); // Configuraciones específicas de la empresa
            $table->json('billing_config')->nullable(); // Configuración de facturación
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};