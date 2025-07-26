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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->json('attributes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
