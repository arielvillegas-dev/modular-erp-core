<?php

namespace App\Services\Erp;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryKardexService
{
    public function adjustStock(
        Branch $branch,
        Product $product,
        int $deltaBase,
        string $type,
        string $reference
    ): BranchStock {
        return DB::transaction(function () use ($branch, $product, $deltaBase, $type, $reference) {
            $stock = BranchStock::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = BranchStock::query()->create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'quantity_base' => 0,
                ]);
            }

            $nextBalance = $stock->quantity_base + $deltaBase;
            if ($nextBalance < 0) {
                throw new InvalidArgumentException("Insufficient stock on branch {$branch->code} for {$product->sku}.");
            }

            $stock->update(['quantity_base' => $nextBalance]);

            StockMovement::query()->create([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'type' => $type,
                'delta_base' => $deltaBase,
                'balance_after' => $nextBalance,
                'reference' => $reference,
            ]);

            return $stock;
        });
    }

    public function transferBetweenBranches(
        Branch $fromBranch,
        Branch $toBranch,
        Product $product,
        int $quantityBase,
        string $reference
    ): void {
        if ($fromBranch->id === $toBranch->id) {
            throw new InvalidArgumentException('Origin and destination branches must differ.');
        }
        if ($quantityBase <= 0) {
            throw new InvalidArgumentException('Transfer quantity must be positive.');
        }

        DB::transaction(function () use ($fromBranch, $toBranch, $product, $quantityBase, $reference) {
            $this->adjustStock($fromBranch, $product, -$quantityBase, 'transfer_out', $reference);
            $this->adjustStock($toBranch, $product, $quantityBase, 'transfer_in', $reference);
        });
    }
}
