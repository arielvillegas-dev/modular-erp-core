<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'base_unit', 'pack_factor', 'retail_price', 'wholesale_price'];

    protected $casts = [
        'pack_factor' => 'integer',
        'retail_price' => 'float',
        'wholesale_price' => 'float',
    ];
}
