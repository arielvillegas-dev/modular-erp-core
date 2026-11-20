<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesInvoice extends Model
{
    protected $fillable = ['branch_id', 'invoice_number', 'customer_name', 'price_tier', 'payment_term', 'total_amount'];

    protected $casts = [
        'total_amount' => 'float',
    ];

    public function receivable(): HasOne
    {
        return $this->hasOne(AccountsReceivable::class);
    }
}
