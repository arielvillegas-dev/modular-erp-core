<?php

namespace App\Services\Erp;

use App\Models\AccountsReceivable;
use App\Models\ArInstallment;
use App\Models\Branch;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SalesAndReceivablesService
{
    public function __construct(
        private readonly InventoryKardexService $kardex,
    ) {}

    public function createSale(
        Branch $branch,
        Product $product,
        int $quantityBase,
        string $customerName,
        string $priceTier = 'retail',
        string $paymentTerm = 'cash'
    ): SalesInvoice {
        if (! in_array($priceTier, ['retail', 'wholesale'], true)) {
            throw new InvalidArgumentException("Invalid price tier: {$priceTier}");
        }
        if (! in_array($paymentTerm, ['cash', 'credit'], true)) {
            throw new InvalidArgumentException("Invalid payment term: {$paymentTerm}");
        }

        return DB::transaction(function () use ($branch, $product, $quantityBase, $customerName, $priceTier, $paymentTerm) {
            $invoiceNumber = 'INV-'.strtoupper(Str::random(8));
            $unitPrice = $priceTier === 'wholesale' ? $product->wholesale_price : $product->retail_price;
            $total = round($unitPrice * $quantityBase, 2);

            $this->kardex->adjustStock($branch, $product, -$quantityBase, 'sale_out', $invoiceNumber);

            $invoice = SalesInvoice::query()->create([
                'branch_id' => $branch->id,
                'invoice_number' => $invoiceNumber,
                'customer_name' => $customerName,
                'price_tier' => $priceTier,
                'payment_term' => $paymentTerm,
                'total_amount' => $total,
            ]);

            if ($paymentTerm === 'credit') {
                AccountsReceivable::query()->create([
                    'sales_invoice_id' => $invoice->id,
                    'branch_id' => $branch->id,
                    'original_amount' => $total,
                    'balance_due' => $total,
                    'status' => 'open',
                ]);
            }

            return $invoice->load('receivable');
        });
    }

    public function recordInstallment(
        AccountsReceivable $receivable,
        float $amountPaid,
        string $paymentMethod = 'transfer',
        ?string $reference = null
    ): AccountsReceivable {
        if ($amountPaid <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($receivable, $amountPaid, $paymentMethod, $reference) {
            $locked = AccountsReceivable::query()
                ->where('id', $receivable->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === 'paid' || $locked->balance_due <= 0) {
                throw new InvalidArgumentException('Account receivable is already settled.');
            }

            if (round($amountPaid, 2) > round($locked->balance_due, 2)) {
                throw new InvalidArgumentException('Payment exceeds remaining balance due.');
            }

            ArInstallment::query()->create([
                'accounts_receivable_id' => $locked->id,
                'amount_paid' => round($amountPaid, 2),
                'payment_method' => $paymentMethod,
                'reference' => $reference,
            ]);

            $newBalance = round($locked->balance_due - $amountPaid, 2);
            $locked->update([
                'balance_due' => max(0.0, $newBalance),
                'status' => $newBalance <= 0 ? 'paid' : 'partial',
            ]);

            return $locked->load('installments');
        });
    }
}
