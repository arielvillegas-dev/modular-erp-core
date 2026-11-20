<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArInstallment extends Model
{
    protected $fillable = ['accounts_receivable_id', 'amount_paid', 'payment_method', 'reference'];

    protected $casts = [
        'amount_paid' => 'float',
    ];
}
