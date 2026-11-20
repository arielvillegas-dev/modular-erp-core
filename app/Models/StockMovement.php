<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'type', 'delta_base', 'balance_after', 'reference'];
}
