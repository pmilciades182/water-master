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
        Schema::create('user_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate user-role assignments per company
            $table->unique(['user_id', 'role_id', 'company_id']);
            
            // Indexes for better query performance
            $table->index(['user_id', 'company_id']);
            $table->index(['role_id', 'company_id']);
            $table->index('assigned_by');
            
            // Ensure multi-tenancy: user, role, and company must all belong to the same company
            // This will be enforced at the application level, but we add these indexes to support it
            $table->index(['company_id', 'user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
