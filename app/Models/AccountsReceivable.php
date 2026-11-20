<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountsReceivable extends Model
{
    protected $table = 'accounts_receivable';

    protected $fillable = ['sales_invoice_id', 'branch_id', 'original_amount', 'balance_due', 'status'];

    protected $casts = [
        'original_amount' => 'float',
        'balance_due' => 'float',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(ArInstallment::class);
    }
}
