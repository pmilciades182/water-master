<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'value',
        'type'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedValueAttribute()
    {
        switch ($this->type) {
            case 'number':
                return (float) $this->value;
            case 'boolean':
                return (bool) $this->value;
            case 'date':
                return \Carbon\Carbon::parse($this->value);
            default:
                return $this->value;
        }
    }
}
