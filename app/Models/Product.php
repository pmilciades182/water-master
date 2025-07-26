<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'description',
        'sku',
        'price',
        'stock',
        'min_stock',
        'attributes',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->min_stock;
    }

    public function getAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
        
        $attribute = $this->productAttributes()->where('name', $key)->first();
        return $attribute ? $attribute->value : null;
    }
}
